<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    /**
     * Pastikan tabel database dan Data Seeder sudah terisi otomatis
     */
    public static function ensureSeeded(): void
    {
        try {
            if (!Schema::hasTable('users') || User::count() === 0) {
                Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            }
        } catch (\Throwable $e) {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        }
    }

    public function showLogin(Request $request)
    {
        self::ensureSeeded();

        // Dukungan klik cepat akun seeder untuk kemudahan demo
        if ($request->filled('quick_email')) {
            $user = User::where('email', $request->query('quick_email'))->first();
            if ($user && $user->status === 'active') {
                Auth::login($user);
                AuditLog::create([
                    'user_id'    => $user->id,
                    'action'     => "LOGIN_SEEDER ({$user->role})",
                    'entity'     => 'USERS',
                    'entity_id'  => $user->id,
                    'created_at' => now(),
                ]);
                return redirect()->route('role.' . $user->role)
                    ->with('success', "Berhasil masuk sebagai {$user->full_name} (Role: " . strtoupper($user->role) . ").");
            }
        }

        if (Auth::check()) {
            return redirect()->route('role.' . Auth::user()->role);
        }

        $seededAccounts = User::orderBy('id')->get();
        return view('auth.login', compact('seededAccounts'));
    }

    public function login(Request $request)
    {
        self::ensureSeeded();

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password_hash)) {
            return back()->withInput()->with('error', 'Email atau kata sandi tidak sesuai dengan data akun seeder.');
        }

        if ($user->status !== 'active') {
            return back()->with('error', 'Akun Anda sedang dinonaktifkan oleh Admin.');
        }

        Auth::login($user);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => "LOGIN_SUCCESS ({$user->role})",
            'entity'     => 'USERS',
            'entity_id'  => $user->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.' . $user->role)
            ->with('success', "Selamat datang, {$user->full_name}!");
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'LOGOUT',
                'entity'     => 'USERS',
                'entity_id'  => Auth::id(),
                'created_at' => now(),
            ]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }

    /**
     * Normalisasi nomor HP (08xxx / +628xxx / 628xxx -> 08xxx) untuk pencocokan keamanan
     */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }

    /**
     * Permohonan Lupa Kata Sandi dari halaman Login (Wajib Cocok Email + Nomor HP Terdaftar)
     * BAAK dan Admin HANYA bisa mengubah kata sandi jika tiket MENUNGGU aktif dari endpoint ini.
     */
    public function requestPasswordReset(Request $request)
    {
        self::ensureSeeded();

        $request->validate([
            'email'           => 'required|email',
            'phone'           => 'required|string|max:30',
            'channel'         => 'required|in:baak,admin',
            'requester_notes' => 'nullable|string|max:255',
        ], [
            'email.required' => 'Email akun wajib diisi.',
            'phone.required' => 'Nomor HP terdaftar wajib diisi.',
        ]);

        $email = trim($request->input('email'));
        $inputPhone = $this->normalizePhone($request->input('phone'));
        $channel = $request->input('channel');

        $user = User::where('email', $email)->first();
        $registeredPhone = $user ? $this->normalizePhone($user->phone) : '';

        if (!$user || $registeredPhone === '' || $inputPhone !== $registeredPhone) {
            return redirect()->route('login')
                ->withInput()
                ->with('open_forgot_modal', true)
                ->with('forgot_error', 'Verifikasi gagal: Email atau Nomor HP tidak cocok dengan data yang terdaftar di sistem.');
        }

        if ($user->role === 'admin') {
            return redirect()->route('login')
                ->with('open_forgot_modal', true)
                ->with('forgot_error', 'Akun Administrator dikelola langsung di database dan tidak dapat direset melalui form ini.');
        }

        if ($channel === 'baak' && $user->role !== 'mahasiswa') {
            return redirect()->route('login')
                ->withInput()
                ->with('open_forgot_modal', true)
                ->with('forgot_error', 'Akun Dosen, Kalab, atau BAAK harus mengajukan reset melalui jalur 2 (Administrator via WhatsApp).');
        }

        if ($channel === 'admin' && $user->role === 'mahasiswa') {
            return redirect()->route('login')
                ->withInput()
                ->with('open_forgot_modal', true)
                ->with('forgot_error', 'Akun Mahasiswa / Organisasi harus mengajukan reset melalui jalur 1 (BAAK).');
        }

        if ($channel === 'baak' && trim((string) $request->input('requester_notes', '')) === '') {
            return redirect()->route('login')
                ->withInput()
                ->with('open_forgot_modal', true)
                ->with('forgot_error', 'Silakan sebutkan Komting Kelas berapa atau Nama Organisasi Anda untuk verifikasi BAAK.');
        }

        $targetHandler = $channel === 'baak' ? 'BAAK' : 'ADMIN';

        // Cek apakah sudah ada tiket MENUNGGU yang aktif
        $ticket = PasswordResetRequest::where('user_id', $user->id)
            ->where('target_handler', $targetHandler)
            ->where('status', 'MENUNGGU')
            ->latest('id')
            ->first();

        if (!$ticket) {
            $ticket = PasswordResetRequest::create([
                'user_id'         => $user->id,
                'requested_email' => $user->email,
                'requested_phone' => $user->phone,
                'requester_notes' => $channel === 'baak'
                    ? trim((string) $request->input('requester_notes'))
                    : 'Permohonan reset dari halaman Lupa Kata Sandi (Konfirmasi WA)',
                'target_handler'  => $targetHandler,
                'status'          => 'MENUNGGU',
                'requested_at'    => now(),
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => "REQUEST_PASSWORD_RESET (#PR-{$ticket->id} -> {$targetHandler})",
                'entity'     => 'PASSWORD_RESET_REQUESTS',
                'entity_id'  => $ticket->id,
                'created_at' => now(),
            ]);
        }

        if ($channel === 'baak') {
            return redirect()->route('login')->with(
                'success',
                "Permintaan reset kata sandi (#PR-{$ticket->id}) telah dikirim ke BAAK. Silakan menemui petugas BAAK untuk konfirmasi dan menerima kata sandi baru Anda."
            );
        }

        $waMessage = "Halo Administrator RoomReserve, saya telah mengajukan tiket reset kata sandi *#PR-{$ticket->id}* di sistem.\n"
            . "- Email: {$user->email}\n"
            . "- Nomor HP Terdaftar: {$user->phone}\n"
            . "Mohon bantuan verifikasi dan perbaikan kata sandi akun saya. Terima kasih.";
        $waUrl = 'https://wa.me/6281234567890?text=' . rawurlencode($waMessage);

        return redirect()->route('login')
            ->with('success', "Permintaan reset kata sandi (#PR-{$ticket->id}) telah tercatat di sistem Administrator. Klik tombol WhatsApp di bawah untuk mengonfirmasi ke Admin.")
            ->with('wa_reset_url', $waUrl)
            ->with('wa_ticket_code', "#PR-{$ticket->id}");
    }
}
