<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
}
