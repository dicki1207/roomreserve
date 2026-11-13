<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\LabManager;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoomReserveController extends Controller
{
    /**
     * Data umum yang dibutuhkan oleh komponen kalender & modal pengajuan
     */
    private function sharedViewData(): array
    {
        $allRooms = Room::with(['building', 'labManager.kalab'])
            ->orderBy('building_id')
            ->orderBy('floor')
            ->orderBy('room_code')
            ->get();

        $allSchedules = ClassSchedule::with(['room', 'lecturer'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        $allReservations = Reservation::with(['room.building', 'requester', 'approval.approver'])
            ->orderByDesc('id')
            ->get();

        return compact('allRooms', 'allSchedules', 'allReservations');
    }

    // =========================================================================
    // 1. TAMPILAN KHUSUS ROLE: MAHASISWA / ORGANISASI
    // =========================================================================
    public function mahasiswaDashboard(Request $request)
    {
        $data = $this->sharedViewData();
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['myReservations'] = Reservation::with(['room.building', 'approval.approver'])
            ->where('requester_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('roles.mahasiswa', $data);
    }

    // =========================================================================
    // 2. TAMPILAN KHUSUS ROLE: DOSEN PENGAMPU (GKT)
    // =========================================================================
    public function dosenDashboard(Request $request)
    {
        $data = $this->sharedViewData();
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['myLectures'] = ClassSchedule::with('room')
            ->where('lecturer_id', Auth::id())
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        $data['dosenApprovals'] = Reservation::with(['room.building', 'requester', 'approval.approver'])
            ->whereHas('approval', function ($q) {
                $q->where('approver_type', 'DOSEN')
                  ->where('approver_id', Auth::id());
            })
            ->orderByDesc('id')
            ->get();

        $data['myReservations'] = Reservation::with(['room.building', 'approval.approver'])
            ->where('requester_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('roles.dosen', $data);
    }

    // =========================================================================
    // 3. TAMPILAN KHUSUS ROLE: KEPALA LABORATORIUM / KALAB (9 LAB TI)
    // =========================================================================
    public function kalabDashboard(Request $request)
    {
        $data = $this->sharedViewData();
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['myManagedLabs'] = Room::with('building')
            ->whereHas('labManager', fn($q) => $q->where('user_id', Auth::id()))
            ->orderBy('room_code')
            ->get();

        $data['kalabApprovals'] = Reservation::with(['room.building', 'requester', 'approval.approver'])
            ->whereHas('approval', function ($q) {
                $q->where('approver_type', 'KALAB')
                  ->where('approver_id', Auth::id());
            })
            ->orderByDesc('id')
            ->get();

        return view('roles.kalab', $data);
    }

    // =========================================================================
    // 4. TAMPILAN KHUSUS ROLE: BAAK
    // =========================================================================
    public function baakDashboard(Request $request)
    {
        $data = $this->sharedViewData();
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['lecturers'] = User::where('role', 'dosen')->orderBy('full_name')->get();
        $data['gktRooms'] = $data['allRooms']->filter(fn($r) => $r->building->name === 'GKT');

        return view('roles.baak', $data);
    }

    // =========================================================================
    // 5. TAMPILAN KHUSUS ROLE: ADMIN SISTEM
    // =========================================================================
    public function adminDashboard(Request $request)
    {
        $data = $this->sharedViewData();
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['allUsers'] = User::orderBy('id')->get();
        $data['kalabUsers'] = User::where('role', 'kalab')->orderBy('full_name')->get();
        $data['tiLabs'] = $data['allRooms']->filter(fn($r) => $r->building->name === 'TI');
        $data['auditLogs'] = AuditLog::with('user')->orderByDesc('id')->limit(40)->get();

        return view('roles.admin', $data);
    }

    // =========================================================================
    // CRUD 1: MAHASISWA — PENGAJUAN PEMINJAMAN (CREATE, UPDATE, CANCEL/DELETE)
    // =========================================================================
    public function storeReservation(Request $request)
    {
        $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'reservation_date' => 'required|date',
            'start_time'       => 'required|string',
            'end_time'         => 'required|string',
            'purpose'          => 'required|string|max:500',
        ]);

        $roomId = (int) $request->input('room_id');
        $date = $request->input('reservation_date');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $purpose = trim($request->input('purpose'));

        if ($startTime >= $endTime) {
            return back()->with('error', 'Waktu mulai harus lebih awal daripada waktu selesai.');
        }

        $room = Room::with(['building', 'labManager.kalab'])->findOrFail($roomId);
        if ($room->status !== 'available') {
            return back()->with('error', "Ruangan {$room->room_code} sedang berstatus {$room->status} dan tidak dapat dipinjam.");
        }

        // Cek konflik dengan peminjaman berstatus DISETUJUI
        $conflict = Reservation::with('requester')
            ->where('room_id', $roomId)
            ->where('reservation_date', $date)
            ->where('status', 'DISETUJUI')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->first();

        if ($conflict) {
            return back()->with(
                'error',
                "Jadwal Bentrok! Ruangan {$room->room_code} pada {$date} ({$conflict->start_time}–{$conflict->end_time}) sudah disetujui untuk kegiatan lain."
            );
        }

        // Routing Persetujuan Cerdas
        $approverId = null;
        $approverType = 'BAAK';
        $status = 'MENUNGGU_BAAK';

        if ($room->building->name === 'TI') {
            $approverType = 'KALAB';
            $status = 'MENUNGGU_KALAB';
            $approverId = $room->labManager?->user_id ?? User::where('role', 'kalab')->value('id');
        } else {
            $classSchedule = ClassSchedule::with('lecturer')
                ->where('room_id', $roomId)
                ->where('schedule_date', $date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->first();

            if ($classSchedule) {
                $approverType = 'DOSEN';
                $status = 'MENUNGGU_DOSEN';
                $approverId = $classSchedule->lecturer_id;
            } else {
                $approverType = 'BAAK';
                $status = 'MENUNGGU_BAAK';
                $approverId = User::where('role', 'baak')->value('id');
            }
        }

        DB::transaction(function () use ($room, $date, $startTime, $endTime, $purpose, $status, $approverId, $approverType) {
            $res = Reservation::create([
                'room_id'          => $room->id,
                'requester_id'     => Auth::id(),
                'purpose'          => $purpose,
                'reservation_date' => $date,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'status'           => $status,
                'submitted_at'     => now(),
            ]);

            Approval::create([
                'reservation_id' => $res->id,
                'approver_id'    => $approverId,
                'approver_type'  => $approverType,
                'decision'       => 'MENUNGGU',
            ]);

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => "CREATE_RESERVATION ({$room->room_code} -> {$status})",
                'entity'     => 'RESERVATIONS',
                'entity_id'  => $res->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Pengajuan peminjaman {$room->room_code} berhasil dikirim.");
    }

    public function updateReservation(Request $request, $id)
    {
        $reservation = Reservation::where('id', $id)
            ->where('requester_id', Auth::id())
            ->firstOrFail();

        if (!str_starts_with($reservation->status, 'MENUNGGU')) {
            return back()->with('error', 'Hanya pengajuan dengan status MENUNGGU yang dapat diubah.');
        }

        $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'reservation_date' => 'required|date',
            'start_time'       => 'required|string',
            'end_time'         => 'required|string',
            'purpose'          => 'required|string|max:500',
        ]);

        $roomId = (int) $request->input('room_id');
        $date = $request->input('reservation_date');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $purpose = trim($request->input('purpose'));

        if ($startTime >= $endTime) {
            return back()->with('error', 'Waktu mulai harus lebih awal daripada waktu selesai.');
        }

        $conflict = Reservation::where('room_id', $roomId)
            ->where('reservation_date', $date)
            ->where('status', 'DISETUJUI')
            ->where('id', '!=', $reservation->id)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Jadwal bentrok dengan peminjaman lain yang sudah disetujui.');
        }

        $room = Room::with(['building', 'labManager.kalab'])->findOrFail($roomId);

        if ($room->building->name === 'TI') {
            $approverType = 'KALAB';
            $status = 'MENUNGGU_KALAB';
            $approverId = $room->labManager?->user_id ?? User::where('role', 'kalab')->value('id');
        } else {
            $classSchedule = ClassSchedule::where('room_id', $roomId)
                ->where('schedule_date', $date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->first();

            if ($classSchedule) {
                $approverType = 'DOSEN';
                $status = 'MENUNGGU_DOSEN';
                $approverId = $classSchedule->lecturer_id;
            } else {
                $approverType = 'BAAK';
                $status = 'MENUNGGU_BAAK';
                $approverId = User::where('role', 'baak')->value('id');
            }
        }

        DB::transaction(function () use ($reservation, $room, $date, $startTime, $endTime, $purpose, $status, $approverId, $approverType) {
            $reservation->update([
                'room_id'          => $room->id,
                'purpose'          => $purpose,
                'reservation_date' => $date,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'status'           => $status,
            ]);

            $reservation->approval()->update([
                'approver_id'   => $approverId,
                'approver_type' => $approverType,
                'decision'      => 'MENUNGGU',
                'notes'         => null,
                'decided_at'    => null,
            ]);

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => "UPDATE_RESERVATION (#RR-{$reservation->id})",
                'entity'     => 'RESERVATIONS',
                'entity_id'  => $reservation->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Pengajuan peminjaman #RR-{$reservation->id} berhasil diperbarui.");
    }

    public function destroyReservation($id)
    {
        $reservation = Reservation::where('id', $id)
            ->where('requester_id', Auth::id())
            ->firstOrFail();

        if (!str_starts_with($reservation->status, 'MENUNGGU')) {
            return back()->with('error', 'Hanya pengajuan dengan status MENUNGGU yang dapat dibatalkan.');
        }

        DB::transaction(function () use ($reservation) {
            $resId = $reservation->id;
            $reservation->approval()->delete();
            $reservation->delete();

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => "CANCEL_RESERVATION (#RR-{$resId})",
                'entity'     => 'RESERVATIONS',
                'entity_id'  => $resId,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Pengajuan peminjaman berhasil dibatalkan.");
    }

    // =========================================================================
    // CRUD 2: DOSEN, KALAB, BAAK — KEPUTUSAN PERSETUJUAN / PENOLAKAN
    // =========================================================================
    public function decideReservation(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'decision'       => 'required|in:DISETUJUI,DITOLAK',
            'notes'          => 'nullable|string',
        ]);

        $decision = $request->input('decision');
        $notes = trim((string) $request->input('notes', ''));

        if ($decision === 'DITOLAK' && $notes === '') {
            return back()->with('error', 'Validasi Gagal: Pemberi persetujuan WAJIB menulis alasan jika menolak peminjaman!');
        }

        $reservation = Reservation::with(['room', 'approval'])->findOrFail((int) $request->input('reservation_id'));
        $user = Auth::user();

        // Verifikasi RBAC
        if ($user->role === 'dosen') {
            if ($reservation->approval?->approver_type !== 'DOSEN' || (int) $reservation->approval?->approver_id !== (int) $user->id) {
                return back()->with('error', 'Akses Ditolak (RBAC): Dosen hanya dapat memproses peminjaman ruang GKT pada jadwal kuliahnya sendiri.');
            }
        } elseif ($user->role === 'kalab') {
            if ($reservation->approval?->approver_type !== 'KALAB' || (int) $reservation->approval?->approver_id !== (int) $user->id) {
                return back()->with('error', 'Akses Ditolak (RBAC): Kalab hanya dapat memproses peminjaman laboratorium yang dikelolanya.');
            }
        }

        if ($decision === 'DISETUJUI') {
            $conflict = Reservation::where('room_id', $reservation->room_id)
                ->where('reservation_date', $reservation->reservation_date)
                ->where('status', 'DISETUJUI')
                ->where('id', '!=', $reservation->id)
                ->where('start_time', '<', $reservation->end_time)
                ->where('end_time', '>', $reservation->start_time)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Tidak dapat disetujui karena ruangan sudah disetujui untuk kegiatan lain pada jam yang sama.');
            }
        }

        $finalNotes = $notes !== '' ? $notes : 'Disetujui.';

        DB::transaction(function () use ($reservation, $user, $decision, $finalNotes) {
            $reservation->update(['status' => $decision]);

            $reservation->approval()->update([
                'approver_id' => $user->id,
                'decision'    => $decision,
                'notes'       => $finalNotes,
                'decided_at'  => now(),
            ]);

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => "{$decision}_RESERVATION ({$reservation->room->room_code})",
                'entity'     => 'APPROVALS',
                'entity_id'  => $reservation->id,
                'created_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            $decision === 'DISETUJUI'
                ? "Peminjaman {$reservation->room->room_code} berhasil DISETUJUI dan masuk ke Kalender Ruangan."
                : "Peminjaman {$reservation->room->room_code} telah DITOLAK."
        );
    }

    // =========================================================================
    // CRUD 3: BAAK — JADWAL KULIAH GKT & MASTER RUANGAN
    // =========================================================================
    public function storeSchedule(Request $request)
    {
        $request->validate([
            'room_id'       => 'required|exists:rooms,id',
            'lecturer_id'   => 'required|exists:users,id',
            'course_name'   => 'required|string|max:150',
            'schedule_date' => 'required|date',
            'start_time'    => 'required|string',
            'end_time'      => 'required|string',
        ]);

        $schedule = ClassSchedule::create($request->only([
            'room_id', 'lecturer_id', 'course_name', 'schedule_date', 'start_time', 'end_time'
        ]));

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "CREATE_CLASS_SCHEDULE ({$schedule->course_name})",
            'entity'     => 'CLASS_SCHEDULES',
            'entity_id'  => $schedule->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'schedules'])
            ->with('success', "Jadwal kuliah \"{$schedule->course_name}\" berhasil ditambahkan.");
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $request->validate([
            'room_id'       => 'required|exists:rooms,id',
            'lecturer_id'   => 'required|exists:users,id',
            'course_name'   => 'required|string|max:150',
            'schedule_date' => 'required|date',
            'start_time'    => 'required|string',
            'end_time'      => 'required|string',
        ]);

        $schedule->update($request->only([
            'room_id', 'lecturer_id', 'course_name', 'schedule_date', 'start_time', 'end_time'
        ]));

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "UPDATE_CLASS_SCHEDULE ({$schedule->course_name})",
            'entity'     => 'CLASS_SCHEDULES',
            'entity_id'  => $schedule->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'schedules'])
            ->with('success', "Jadwal kuliah \"{$schedule->course_name}\" berhasil diperbarui.");
    }

    public function destroySchedule($id)
    {
        $schedule = ClassSchedule::findOrFail($id);
        $courseName = $schedule->course_name;
        $scheduleId = $schedule->id;
        $schedule->delete();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "DELETE_CLASS_SCHEDULE ({$courseName})",
            'entity'     => 'CLASS_SCHEDULES',
            'entity_id'  => $scheduleId,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'schedules'])
            ->with('success', "Jadwal kuliah \"{$courseName}\" berhasil dihapus.");
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor'       => 'required|integer|min:1|max:10',
            'room_code'   => 'required|string|max:50|unique:rooms,room_code',
            'room_name'   => 'required|string|max:150',
            'room_type'   => 'required|string|max:100',
            'capacity'    => 'required|integer|min:1',
        ]);

        $room = Room::create([
            'building_id' => $request->input('building_id'),
            'floor'       => $request->input('floor'),
            'room_code'   => strtoupper(trim($request->input('room_code'))),
            'room_name'   => trim($request->input('room_name')),
            'room_type'   => trim($request->input('room_type')),
            'capacity'    => (int) $request->input('capacity'),
            'status'      => 'available',
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "CREATE_ROOM ({$room->room_code})",
            'entity'     => 'ROOMS',
            'entity_id'  => $room->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'rooms'])
            ->with('success', "Ruangan {$room->room_code} berhasil ditambahkan.");
    }

    public function updateRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_name' => 'required|string|max:150',
            'room_type' => 'required|string|max:100',
            'capacity'  => 'required|integer|min:1',
            'status'    => 'required|in:available,maintenance',
        ]);

        $room->update($request->only(['room_name', 'room_type', 'capacity', 'status']));

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "UPDATE_ROOM ({$room->room_code})",
            'entity'     => 'ROOMS',
            'entity_id'  => $room->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'rooms'])
            ->with('success', "Data ruangan {$room->room_code} berhasil diperbarui.");
    }

    public function destroyRoom($id)
    {
        $room = Room::findOrFail($id);

        if (Reservation::where('room_id', $room->id)->exists()) {
            return redirect()->route('role.baak', ['tab' => 'rooms'])
                ->with('error', "Ruangan {$room->room_code} tidak dapat dihapus karena memiliki riwayat peminjaman.");
        }

        $code = $room->room_code;
        $roomId = $room->id;
        $room->labManager()?->delete();
        $room->classSchedules()?->delete();
        $room->delete();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "DELETE_ROOM ({$code})",
            'entity'     => 'ROOMS',
            'entity_id'  => $roomId,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'rooms'])
            ->with('success', "Ruangan {$code} berhasil dihapus.");
    }

    public function toggleRoomStatus(Request $request)
    {
        $room = Room::findOrFail((int) $request->input('room_id'));
        $newStatus = $room->status === 'available' ? 'maintenance' : 'available';
        $room->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "UPDATE_ROOM_STATUS ({$room->room_code} -> {$newStatus})",
            'entity'     => 'ROOMS',
            'entity_id'  => $room->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'rooms'])
            ->with('success', "Status ruangan {$room->room_code} diubah menjadi {$newStatus}.");
    }

    // =========================================================================
    // CRUD 4: ADMIN — KELOLA AKUN PENGGUNA (USERS) & PENUGASAN KALAB
    // =========================================================================
    public function storeUser(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:4',
            'role'      => 'required|in:mahasiswa,dosen,kalab,baak,admin',
        ]);

        $newUser = User::create([
            'full_name'     => $request->input('full_name'),
            'email'         => $request->input('email'),
            'password_hash' => Hash::make($request->input('password')),
            'role'          => $request->input('role'),
            'status'        => 'active',
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "CREATE_USER ({$newUser->email} as {$newUser->role})",
            'entity'     => 'USERS',
            'entity_id'  => $newUser->id,
            'created_at' => now(),
        ]);

        return back()->with('success', "Akun {$newUser->full_name} ({$newUser->role}) berhasil ditambahkan.");
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:mahasiswa,dosen,kalab,baak,admin',
            'status'    => 'required|in:active,inactive',
            'password'  => 'nullable|string|min:4',
        ]);

        $data = [
            'full_name' => $request->input('full_name'),
            'email'     => $request->input('email'),
            'role'      => $request->input('role'),
            'status'    => $request->input('status'),
        ];

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "UPDATE_USER ({$user->email})",
            'entity'     => 'USERS',
            'entity_id'  => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', "Akun {$user->full_name} berhasil diperbarui.");
    }

    public function destroyUser($id)
    {
        if ((int) Auth::id() === (int) $id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $user = User::findOrFail($id);
        $name = $user->full_name;
        $userId = $user->id;
        $user->delete();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "DELETE_USER ({$name})",
            'entity'     => 'USERS',
            'entity_id'  => $userId,
            'created_at' => now(),
        ]);

        return back()->with('success', "Akun {$name} berhasil dihapus.");
    }

    public function assignKalab(Request $request)
    {
        $request->validate([
            'room_id'  => 'required|exists:rooms,id',
            'kalab_id' => 'required|exists:users,id',
        ]);

        LabManager::updateOrCreate(
            ['room_id' => (int) $request->input('room_id')],
            ['user_id' => (int) $request->input('kalab_id'), 'assigned_at' => now()]
        );

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "ASSIGN_KALAB (Room #{$request->input('room_id')} -> User #{$request->input('kalab_id')})",
            'entity'     => 'LAB_MANAGERS',
            'entity_id'  => (int) $request->input('room_id'),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Penugasan Kepala Laboratorium berhasil diperbarui.');
    }

    public function resetSeeder()
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            Auth::login($admin);
        }
        return redirect()->route('role.admin')
            ->with('success', 'Database berhasil di-reset.');
    }
}
