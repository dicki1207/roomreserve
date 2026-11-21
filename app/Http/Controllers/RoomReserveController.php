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
     * Data umum yang dibutuhkan oleh komponen kalender, daftar ruangan (berbasis tanggal & jam WIB), & modal pengajuan
     */
    private function sharedViewData(?Request $request = null): array
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

        $today = now()->format('Y-m-d');
        $nowTime = now()->format('H:i');
        $rawDate = $request ? trim((string) $request->query('date', '')) : '';
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) ? $rawDate : $today;
        $isToday = ($selectedDate === $today);

        $roomStatuses = [];
        foreach ($allRooms as $r) {
            $dayApproved = $allReservations
                ->filter(fn($res) => (int) $res->room_id === (int) $r->id
                    && $res->status === 'DISETUJUI'
                    && $res->reservation_date === $selectedDate)
                ->sortBy('start_time')
                ->values();

            $daySchedules = $allSchedules
                ->filter(fn($cs) => (int) $cs->room_id === (int) $r->id
                    && $cs->schedule_date === $selectedDate)
                ->sortBy('start_time')
                ->values();

            $activeBookingNow = $isToday
                ? $dayApproved->first(fn($res) => $res->start_time <= $nowTime && $res->end_time > $nowTime)
                : null;

            $activeClassNow = $isToday
                ? $daySchedules->first(fn($cs) => $cs->start_time <= $nowTime && $cs->end_time > $nowTime)
                : null;

            $relevantApproved = $isToday
                ? $dayApproved->filter(fn($res) => $res->end_time > $nowTime)->values()
                : $dayApproved;

            $relevantSchedules = $isToday
                ? $daySchedules->filter(fn($cs) => $cs->end_time > $nowTime)->values()
                : $daySchedules;

            if ($r->status !== 'available') {
                $badgeClass = 'badge-rose';
                $badgeLabel = 'Perbaikan';
                $isBusyNow = true;
            } elseif ($activeBookingNow) {
                $badgeClass = 'badge-amber';
                $badgeLabel = "Dipinjam ({$activeBookingNow->start_time}–{$activeBookingNow->end_time} WIB)";
                $isBusyNow = true;
            } elseif ($activeClassNow) {
                $badgeClass = 'badge-royal';
                $badgeLabel = "Dipakai Kuliah ({$activeClassNow->start_time}–{$activeClassNow->end_time} WIB)";
                $isBusyNow = true;
            } elseif (!$isToday && $relevantApproved->isNotEmpty()) {
                $first = $relevantApproved->first();
                $badgeClass = 'badge-amber';
                $badgeLabel = $relevantApproved->count() === 1
                    ? "Dipinjam ({$first->start_time}–{$first->end_time} WIB)"
                    : "Dipinjam ({$relevantApproved->count()} jadwal)";
                $isBusyNow = false;
            } elseif (!$isToday && $relevantSchedules->isNotEmpty()) {
                $firstCs = $relevantSchedules->first();
                $badgeClass = 'badge-royal';
                $badgeLabel = $relevantSchedules->count() === 1
                    ? "Ada Kuliah ({$firstCs->start_time}–{$firstCs->end_time} WIB)"
                    : "Ada Kuliah ({$relevantSchedules->count()} jadwal)";
                $isBusyNow = false;
            } else {
                $badgeClass = 'badge-emerald';
                $badgeLabel = 'Tersedia';
                $isBusyNow = false;
            }

            $roomStatuses[$r->id] = [
                'badge_class'    => $badgeClass,
                'badge_label'    => $badgeLabel,
                'is_busy_now'    => $isBusyNow,
                'active_booking' => $activeBookingNow,
                'active_class'   => $activeClassNow,
                'approved_slots' => $relevantApproved,
                'class_slots'    => $relevantSchedules,
            ];
        }

        return compact(
            'allRooms',
            'allSchedules',
            'allReservations',
            'today',
            'nowTime',
            'selectedDate',
            'isToday',
            'roomStatuses'
        );
    }

    // =========================================================================
    // 1. TAMPILAN KHUSUS ROLE: MAHASISWA / ORGANISASI
    // =========================================================================
    public function mahasiswaDashboard(Request $request)
    {
        $data = $this->sharedViewData($request);
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
        $data = $this->sharedViewData($request);
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
    // 3. TAMPILAN KHUSUS ROLE: KEPALA LABORATORIUM / KALAB (10 LAB TI)
    // =========================================================================
    public function kalabDashboard(Request $request)
    {
        $data = $this->sharedViewData($request);
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

    /**
     * Daftar Mata Kuliah Resmi Jurusan Teknik Informatika (Pilihan Dropdown Bebas Typo)
     */
    private function courseOptions(): array
    {
        return [
            'Mata Kuliah Dasar & Umum Jurusan TI' => [
                'Algoritma dan Pemrograman',
                'Struktur Data',
                'Basis Data',
                'Pemrograman Berorientasi Objek (PBO)',
                'Jaringan Komputer',
                'Sistem Operasi',
                'Matematika Diskrit',
                'Aljabar Linear dan Matriks',
                'Statistika dan Probabilitas',
                'Interaksi Manusia dan Komputer (IMK)',
                'Metodologi Penelitian',
                'Bahasa Inggris Teknik',
                'Etika Profesi TI',
                'Kewirausahaan Teknologi',
            ],
            'D-III Teknik Informatika (TI)' => [
                'Pemrograman Web',
                'Pemrograman Web Lanjut',
                'Pemrograman Perangkat Bergerak (Mobile)',
                'Administrasi Sistem dan Jaringan',
                'Administrasi Basis Data',
                'Sistem Informasi Manajemen',
                'Desain Grafis dan Multimedia',
                'Pengolahan Citra Digital',
                'Internet of Things (IoT)',
                'Komputasi Awan (Cloud Computing)',
                'Proyek Teknik Informatika',
            ],
            'D-IV Rekayasa Perangkat Lunak (RPL)' => [
                'Rekayasa Perangkat Lunak',
                'Analisis dan Perancangan Perangkat Lunak',
                'Arsitektur dan Desain Perangkat Lunak',
                'Konstruksi dan Evolusi Perangkat Lunak',
                'Pengujian dan Penjaminan Kualitas Perangkat Lunak (Software Testing)',
                'Manajemen Proyek Perangkat Lunak',
                'Pemrograman Framework & Microservices',
                'DevOps dan Continuous Integration (CI/CD)',
                'Kecerdasan Buatan (Artificial Intelligence)',
                'Machine Learning & Data Mining',
                'Data Warehouse dan Business Intelligence',
            ],
            'D-IV Keamanan Sistem Informasi (KSI)' => [
                'Pengantar Keamanan Informasi',
                'Kriptografi Terapan',
                'Keamanan Jaringan Komputer',
                'Keamanan Aplikasi Web',
                'Keamanan Sistem Operasi & Server',
                'Keamanan Infrastruktur Cloud & Kontainer',
                'Ethical Hacking dan Penetration Testing',
                'Forensik Digital dan Investigasi Siber',
                'Manajemen Risiko dan Tata Kelola Keamanan (Audit TI)',
                'Security Operations Center (SOC) & Incident Response',
                'Keamanan Perangkat Bergerak dan IoT',
            ],
        ];
    }

    /**
     * Daftar Kelas Resmi Jurusan Teknik Informatika (Pilihan Dropdown Terpisah dari Mata Kuliah)
     */
    private function classOptions(): array
    {
        return [
            'D-III Teknik Informatika (TI)' => [
                'TI-1A', 'TI-1B', 'TI-1C',
                'TI-2A', 'TI-2B', 'TI-2C',
                'TI-3A', 'TI-3B', 'TI-3C',
                'TI-4A', 'TI-4B', 'TI-4C',
                'TI-5A', 'TI-5B', 'TI-5C',
                'TI-6A', 'TI-6B', 'TI-6C',
            ],
            'D-IV Rekayasa Perangkat Lunak (RPL)' => [
                'RPL-1A', 'RPL-1B', 'RPL-1C',
                'RPL-2A', 'RPL-2B', 'RPL-2C',
                'RPL-3A', 'RPL-3B', 'RPL-3C',
                'RPL-4A', 'RPL-4B', 'RPL-4C',
                'RPL-5A', 'RPL-5B', 'RPL-5C',
                'RPL-6A', 'RPL-6B', 'RPL-6C',
                'RPL-7A', 'RPL-7B', 'RPL-7C',
                'RPL-8A', 'RPL-8B', 'RPL-8C',
            ],
            'D-IV Keamanan Sistem Informasi (KSI)' => [
                'KSI-1A', 'KSI-1B', 'KSI-1C',
                'KSI-2A', 'KSI-2B', 'KSI-2C',
                'KSI-3A', 'KSI-3B', 'KSI-3C',
                'KSI-4A', 'KSI-4B', 'KSI-4C',
                'KSI-5A', 'KSI-5B', 'KSI-5C',
                'KSI-6A', 'KSI-6B', 'KSI-6C',
                'KSI-7A', 'KSI-7B', 'KSI-7C',
                'KSI-8A', 'KSI-8B', 'KSI-8C',
            ],
        ];
    }

    /**
     * Daftar Nama Ruangan Resmi GKT & Laboratorium Gedung TI (Pilihan Dropdown Bebas Typo)
     */
    private function roomNameOptions(): array
    {
        return [
            'Gedung GKT (Ruang Kelas & Aula Lantai 1–4)' => [
                'Ruang Kelas GKT 101',
                'Ruang Kelas GKT 102',
                'Ruang Kelas GKT 103',
                'Ruang Kelas GKT 104',
                'Ruang Kelas GKT 201',
                'Ruang Kelas GKT 202',
                'Ruang Kelas GKT 203',
                'Ruang Kelas GKT 204',
                'Ruang Diskusi GKT 203',
                'Ruang Kuliah Gabungan GKT 301',
                'Ruang Kelas GKT 302',
                'Ruang Kelas GKT 303',
                'Ruang Kelas GKT 304',
                'Aula Seminar GKT Lantai 4',
                'Ruang Kelas GKT 402',
                'Ruang Kelas GKT 403',
                'Ruang Kelas GKT 404',
            ],
            'Gedung TI (Laboratorium Jurusan TI)' => [
                'Laboratorium Artificial Intelligence',
                'Laboratorium Pengembangan Perangkat Lunak',
                'Laboratorium Keamanan Informasi',
                'Laboratorium High Performance Computing',
                'Lab Pemrograman',
                'Laboratorium Software Testing',
                'Laboratorium Internet of Things',
                'Laboratorium Multimedia',
                'Laboratorium Jaringan Komputer',
                'Laboratorium Sistem Informasi',
            ],
        ];
    }

    /**
     * Daftar Tipe Ruangan Resmi (Pilihan Dropdown Bebas Typo)
     */
    private function roomTypeOptions(): array
    {
        return [
            'Ruang Kelas',
            'Ruang Kuliah Gabungan',
            'Aula Seminar',
            'Ruang Diskusi',
            'Laboratorium TI',
            'Laboratorium Praktikum',
            'Laboratorium Riset',
        ];
    }

    /**
     * Daftar Kode Ruangan Standar (Pilihan Dropdown Bebas Typo)
     */
    private function roomCodeOptions(): array
    {
        return [
            'Kode Ruang Gedung GKT' => [
                'GKT-101', 'GKT-102', 'GKT-103', 'GKT-104',
                'GKT-201', 'GKT-202', 'GKT-203', 'GKT-204',
                'GKT-301', 'GKT-302', 'GKT-303', 'GKT-304',
                'GKT-401', 'GKT-402', 'GKT-403', 'GKT-404',
            ],
            'Kode Laboratorium Gedung TI' => [
                'LAB-TI-01', 'LAB-TI-02', 'LAB-TI-03', 'LAB-TI-04', 'LAB-TI-05',
                'LAB-TI-06', 'LAB-TI-07', 'LAB-TI-08', 'LAB-TI-09', 'LAB-TI-10',
                'LAB-TI-11', 'LAB-TI-12',
            ],
        ];
    }

    // =========================================================================
    // 4. TAMPILAN KHUSUS ROLE: BAAK
    // =========================================================================
    public function baakDashboard(Request $request)
    {
        $data = $this->sharedViewData($request);
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['lecturers'] = User::where('role', 'dosen')->orderBy('full_name')->get();
        $data['kalabUsers'] = User::where('role', 'kalab')->orderBy('full_name')->get();
        $data['students'] = User::where('role', 'mahasiswa')->orderBy('id')->get();
        $data['gktRooms'] = $data['allRooms']->filter(fn($r) => $r->building->name === 'GKT');
        $data['courseOptions'] = $this->courseOptions();
        $data['classOptions'] = $this->classOptions();
        $data['roomNameOptions'] = $this->roomNameOptions();
        $data['roomTypeOptions'] = $this->roomTypeOptions();
        $data['roomCodeOptions'] = $this->roomCodeOptions();

        return view('roles.baak', $data);
    }

    // =========================================================================
    // 5. TAMPILAN KHUSUS ROLE: ADMIN SISTEM
    // =========================================================================
    public function adminDashboard(Request $request)
    {
        $data = $this->sharedViewData($request);
        $data['tab'] = $request->query('tab', 'dashboard');
        $data['staffUsers'] = User::whereIn('role', ['dosen', 'kalab', 'baak'])->orderBy('id')->get();
        $data['adminUsers'] = User::where('role', 'admin')->orderBy('id')->get();
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
            'class_name'    => 'nullable|string|max:60',
            'schedule_date' => 'required|date',
            'start_time'    => 'required|string',
            'end_time'      => 'required|string',
        ]);

        $schedule = ClassSchedule::create([
            'room_id'       => $request->input('room_id'),
            'lecturer_id'   => $request->input('lecturer_id'),
            'course_name'   => trim($request->input('course_name')),
            'class_name'    => $request->filled('class_name') ? trim($request->input('class_name')) : null,
            'schedule_date' => $request->input('schedule_date'),
            'start_time'    => $request->input('start_time'),
            'end_time'      => $request->input('end_time'),
        ]);

        $label = $schedule->class_name ? "{$schedule->course_name} ({$schedule->class_name})" : $schedule->course_name;

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "CREATE_CLASS_SCHEDULE ({$label})",
            'entity'     => 'CLASS_SCHEDULES',
            'entity_id'  => $schedule->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'schedules'])
            ->with('success', "Jadwal kuliah \"{$label}\" berhasil ditambahkan.");
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $request->validate([
            'room_id'       => 'required|exists:rooms,id',
            'lecturer_id'   => 'required|exists:users,id',
            'course_name'   => 'required|string|max:150',
            'class_name'    => 'nullable|string|max:60',
            'schedule_date' => 'required|date',
            'start_time'    => 'required|string',
            'end_time'      => 'required|string',
        ]);

        $schedule->update([
            'room_id'       => $request->input('room_id'),
            'lecturer_id'   => $request->input('lecturer_id'),
            'course_name'   => trim($request->input('course_name')),
            'class_name'    => $request->filled('class_name') ? trim($request->input('class_name')) : null,
            'schedule_date' => $request->input('schedule_date'),
            'start_time'    => $request->input('start_time'),
            'end_time'      => $request->input('end_time'),
        ]);

        $label = $schedule->class_name ? "{$schedule->course_name} ({$schedule->class_name})" : $schedule->course_name;

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "UPDATE_CLASS_SCHEDULE ({$label})",
            'entity'     => 'CLASS_SCHEDULES',
            'entity_id'  => $schedule->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'schedules'])
            ->with('success', "Jadwal kuliah \"{$label}\" berhasil diperbarui.");
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
    // CRUD 4: BAAK — KELOLA AKUN MAHASISWA (MAHASISWA DIHANDLE OLEH BAAK)
    // =========================================================================
    public function storeMahasiswa(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:30',
            'password'  => 'required|string|min:4',
        ]);

        $student = User::create([
            'full_name'     => trim($request->input('full_name')),
            'email'         => trim($request->input('email')),
            'phone'         => $request->filled('phone') ? trim($request->input('phone')) : null,
            'password_hash' => Hash::make($request->input('password')),
            'role'          => 'mahasiswa',
            'status'        => 'active',
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "BAAK_CREATE_MAHASISWA ({$student->email})",
            'entity'     => 'USERS',
            'entity_id'  => $student->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'students'])
            ->with('success', "Akun mahasiswa {$student->full_name} berhasil ditambahkan oleh BAAK.");
    }

    public function updateMahasiswa(Request $request, $id)
    {
        $student = User::where('id', $id)->where('role', 'mahasiswa')->firstOrFail();

        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email,' . $student->id,
            'phone'     => 'nullable|string|max:30',
            'status'    => 'required|in:active,inactive',
            'password'  => 'nullable|string|min:4',
        ]);

        $data = [
            'full_name' => trim($request->input('full_name')),
            'email'     => trim($request->input('email')),
            'phone'     => $request->filled('phone') ? trim($request->input('phone')) : $student->phone,
            'status'    => $request->input('status'),
        ];

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->input('password'));
        }

        $student->update($data);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "BAAK_UPDATE_MAHASISWA ({$student->email})",
            'entity'     => 'USERS',
            'entity_id'  => $student->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'students'])
            ->with('success', "Data akun mahasiswa {$student->full_name} berhasil diperbarui.");
    }

    public function destroyMahasiswa($id)
    {
        $student = User::where('id', $id)->where('role', 'mahasiswa')->firstOrFail();
        $name = $student->full_name;
        $studentId = $student->id;

        Reservation::where('requester_id', $studentId)->each(function ($res) {
            $res->approval()?->delete();
            $res->delete();
        });
        $student->delete();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "BAAK_DELETE_MAHASISWA ({$name})",
            'entity'     => 'USERS',
            'entity_id'  => $studentId,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'students'])
            ->with('success', "Akun mahasiswa {$name} berhasil dihapus.");
    }

    public function resetMahasiswaPassword(Request $request, $id)
    {
        $student = User::where('id', $id)->where('role', 'mahasiswa')->firstOrFail();

        $request->validate([
            'password'           => 'required|string|min:4',
            'verification_notes' => 'nullable|string|max:250',
        ]);

        $newPassword = $request->input('password');
        $verif = trim((string) $request->input('verification_notes', 'Verifikasi langsung di BAAK (Komting Kelas / Organisasi)'));

        $student->update([
            'password_hash' => Hash::make($newPassword),
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "BAAK_RESET_PASSWORD_MAHASISWA ({$student->full_name} — {$verif})",
            'entity'     => 'USERS',
            'entity_id'  => $student->id,
            'created_at' => now(),
        ]);

        return redirect()->route('role.baak', ['tab' => 'students'])
            ->with('success', "Password untuk {$student->full_name} ({$student->email}) berhasil diperbarui oleh BAAK.");
    }

    // =========================================================================
    // CRUD 5: ADMIN — HANYA MEMBUAT AKUN DOSEN, KALAB, DAN BAAK
    // (Akun Admin dibuat di Database; Akun Mahasiswa dihandle oleh BAAK)
    // =========================================================================
    public function storeUser(Request $request)
    {
        if ($request->input('role') === 'admin') {
            return back()->with('error', 'Akun Administrator hanya dibuat langsung di database, tidak dapat dibuat melalui form.');
        }

        if ($request->input('role') === 'mahasiswa') {
            return back()->with('error', 'Akun Mahasiswa dikelola oleh BAAK, bukan melalui Administrator.');
        }

        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:30',
            'password'  => 'required|string|min:4',
            'role'      => 'required|in:dosen,kalab,baak',
        ]);

        $newUser = User::create([
            'full_name'     => $request->input('full_name'),
            'email'         => $request->input('email'),
            'phone'         => $request->filled('phone') ? trim($request->input('phone')) : null,
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

        if ($user->role === 'admin' || $request->input('role') === 'admin') {
            return back()->with('error', 'Akun Administrator dibuat langsung di database dan tidak dapat diubah.');
        }

        if ($user->role === 'mahasiswa' || $request->input('role') === 'mahasiswa') {
            return back()->with('error', 'Akun Mahasiswa dikelola oleh BAAK.');
        }

        $request->validate([
            'full_name' => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:30',
            'role'      => 'required|in:dosen,kalab,baak',
            'status'    => 'required|in:active,inactive',
            'password'  => 'nullable|string|min:4',
        ]);

        $data = [
            'full_name' => $request->input('full_name'),
            'email'     => $request->input('email'),
            'phone'     => $request->filled('phone') ? trim($request->input('phone')) : $user->phone,
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

    public function resetStaffPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!in_array($user->role, ['dosen', 'kalab', 'baak'], true)) {
            return back()->with('error', 'Administrator hanya dapat memperbarui kata sandi untuk akun Dosen, Kalab, atau BAAK.');
        }

        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $newPassword = $request->input('password');
        $user->update([
            'password_hash' => Hash::make($newPassword),
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => "ADMIN_RESET_PASSWORD_STAFF ({$user->full_name} [{$user->role}] via Konfirmasi WA)",
            'entity'     => 'USERS',
            'entity_id'  => $user->id,
            'created_at' => now(),
        ]);

        return back()->with(
            'success',
            "Password akun {$user->full_name} (Role: " . strtoupper($user->role) . ") berhasil diperbarui menjadi \"{$newPassword}\". Silakan informasikan kembali via WhatsApp."
        );
    }

    public function destroyUser($id)
    {
        if ((int) Auth::id() === (int) $id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return back()->with('error', 'Akun Administrator dibuat di database dan tidak dapat dihapus.');
        }

        if ($user->role === 'mahasiswa') {
            return back()->with('error', 'Akun Mahasiswa dikelola oleh BAAK.');
        }

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

    // =========================================================================
    // 6. PROFIL SAYA (DOSEN, KALAB, BAAK, MAHASISWA, & ADMIN)
    // =========================================================================
    public function showProfile(Request $request)
    {
        $data = $this->sharedViewData($request);
        $user = Auth::user();
        $data['tab'] = 'profile';
        $data['profileUser'] = $user;

        $data['myManagedLabs'] = $user->role === 'kalab'
            ? Room::with('building')->whereHas('labManager', fn($q) => $q->where('user_id', $user->id))->orderBy('room_code')->get()
            : collect();

        $data['myTeachingCount'] = $user->role === 'dosen'
            ? ClassSchedule::where('lecturer_id', $user->id)->count()
            : 0;

        $data['myReservationCount'] = Reservation::where('requester_id', $user->id)->count();
        $data['myApprovalCount'] = Approval::where('approver_id', $user->id)
            ->whereIn('decision', ['DISETUJUI', 'DITOLAK'])
            ->count();

        return view('profile', $data);
    }

    public function updateProfileAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Pilih file gambar terlebih dahulu.',
            'avatar.image'    => 'File harus berupa gambar (JPG, PNG, atau WebP).',
            'avatar.max'      => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        $user = Auth::user();
        $file = $request->file('avatar');
        $uploadDir = public_path('uploads/avatars');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($user->avatar) {
            $oldPath = public_path(ltrim($user->avatar, '/'));
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadDir, $filename);

        $user->update([
            'avatar' => '/uploads/avatars/' . $filename,
        ]);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => "UPDATE_PROFILE_AVATAR ({$user->email})",
            'entity'     => 'USERS',
            'entity_id'  => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Foto profil Anda berhasil diperbarui.');
    }

    public function updateProfilePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:4|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'new_password.required'     => 'Kata sandi baru wajib diisi.',
            'new_password.min'          => 'Kata sandi baru minimal 4 karakter.',
            'new_password.confirmed'    => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password_hash)) {
            return back()->with('error', 'Kata sandi saat ini tidak sesuai. Silakan periksa kembali.');
        }

        $user->update([
            'password_hash' => Hash::make($request->input('new_password')),
        ]);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => "UPDATE_OWN_PASSWORD ({$user->email})",
            'entity'     => 'USERS',
            'entity_id'  => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
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
