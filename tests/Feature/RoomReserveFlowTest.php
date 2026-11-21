<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReserveFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_renders_with_seeded_accounts(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('RoomReserve');
        $response->assertSee('mahasiswa@roomreserve.ac.id');
        $response->assertSee('zuliarefendi@roomreserve.ac.id');
        $response->assertSee('kasmawi@roomreserve.ac.id');
        $response->assertSee('baak@roomreserve.ac.id');
        $response->assertSee('admin@roomreserve.ac.id');
    }

    public function test_all_five_roles_can_login_and_view_their_isolated_dashboards(): void
    {
        $roles = [
            'mahasiswa' => ['email' => 'mahasiswa@roomreserve.ac.id', 'route' => '/mahasiswa'],
            'dosen'     => ['email' => 'zuliarefendi@roomreserve.ac.id', 'route' => '/dosen'],
            'kalab'     => ['email' => 'kasmawi@roomreserve.ac.id', 'route' => '/kalab'],
            'baak'      => ['email' => 'baak@roomreserve.ac.id', 'route' => '/baak'],
            'admin'     => ['email' => 'admin@roomreserve.ac.id', 'route' => '/admin'],
        ];

        foreach ($roles as $role => $info) {
            $user = User::where('email', $info['email'])->firstOrFail();

            $loginRes = $this->post('/login', [
                'email'    => $info['email'],
                'password' => 'password123',
            ]);
            $loginRes->assertRedirect($info['route']);

            $dashRes = $this->actingAs($user)->get($info['route']);
            $dashRes->assertStatus(200);

            $this->get('/logout');
        }
    }

    public function test_rbac_blocks_cross_role_access(): void
    {
        $mahasiswa = User::where('email', 'mahasiswa@roomreserve.ac.id')->firstOrFail();
        $baak      = User::where('email', 'baak@roomreserve.ac.id')->firstOrFail();

        $this->actingAs($mahasiswa)->get('/admin')->assertRedirect('/mahasiswa');
        $this->actingAs($mahasiswa)->get('/baak')->assertRedirect('/mahasiswa');
        $this->actingAs($mahasiswa)->get('/dosen')->assertRedirect('/mahasiswa');
        $this->actingAs($mahasiswa)->get('/kalab')->assertRedirect('/mahasiswa');

        // BAAK tidak boleh mengajukan peminjaman ruangan (hanya Mahasiswa)
        $this->actingAs($baak)->post('/reservations', [
            'room_id'          => 1,
            'reservation_date' => '2026-11-12',
            'start_time'       => '08:00',
            'end_time'         => '10:00',
            'purpose'          => 'Coba pinjam dari BAAK',
        ])->assertRedirect('/baak');
    }

    public function test_mahasiswa_crud_reservation_lifecycle(): void
    {
        $mahasiswa = User::where('email', 'mahasiswa@roomreserve.ac.id')->firstOrFail();
        $gkt301 = Room::where('room_code', 'GKT-301')->firstOrFail();
        $gkt302 = Room::where('room_code', 'GKT-302')->firstOrFail();

        // 1. CREATE reservation
        $this->actingAs($mahasiswa)->post('/mahasiswa/reservations', [
            'room_id'          => $gkt301->id,
            'reservation_date' => '2026-11-25',
            'start_time'       => '09:00',
            'end_time'         => '11:00',
            'purpose'          => 'Rapat Divisi HMTI',
        ])->assertSessionHas('success');

        $res = Reservation::where('purpose', 'Rapat Divisi HMTI')->firstOrFail();
        $this->assertEquals('MENUNGGU_BAAK', $res->status);

        // 2. UPDATE reservation (Edit saat masih MENUNGGU)
        $this->actingAs($mahasiswa)->put("/mahasiswa/reservations/{$res->id}", [
            'room_id'          => $gkt302->id,
            'reservation_date' => '2026-11-25',
            'start_time'       => '13:00',
            'end_time'         => '15:00',
            'purpose'          => 'Rapat Divisi HMTI (Pindah Ruangan)',
        ])->assertSessionHas('success');

        $res->refresh();
        $this->assertEquals($gkt302->id, $res->room_id);
        $this->assertEquals('Rapat Divisi HMTI (Pindah Ruangan)', $res->purpose);

        // 3. DELETE / CANCEL reservation
        $this->actingAs($mahasiswa)->delete("/mahasiswa/reservations/{$res->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reservations', ['id' => $res->id]);
    }

    public function test_baak_crud_schedules_and_rooms(): void
    {
        $baak  = User::where('email', 'baak@roomreserve.ac.id')->firstOrFail();
        $dosen = User::where('email', 'zuliarefendi@roomreserve.ac.id')->firstOrFail();
        $gkt   = Room::where('room_code', 'GKT-101')->firstOrFail();

        // 1. CREATE schedule (Mata Kuliah & Kelas terpisah)
        $this->actingAs($baak)->post('/baak/schedules', [
            'room_id'       => $gkt->id,
            'lecturer_id'   => $dosen->id,
            'course_name'   => 'Kriptografi Terapan',
            'class_name'    => 'KSI-5A',
            'schedule_date' => '2026-11-21',
            'start_time'    => '08:00',
            'end_time'      => '10:30',
        ])->assertRedirect('/baak?tab=schedules');

        $sched = ClassSchedule::where('course_name', 'Kriptografi Terapan')->firstOrFail();
        $this->assertEquals('KSI-5A', $sched->class_name);

        // 2. UPDATE schedule
        $this->actingAs($baak)->put("/baak/schedules/{$sched->id}", [
            'room_id'       => $gkt->id,
            'lecturer_id'   => $dosen->id,
            'course_name'   => 'Keamanan Aplikasi Web',
            'class_name'    => 'KSI-5B',
            'schedule_date' => '2026-11-21',
            'start_time'    => '08:00',
            'end_time'      => '11:00',
        ])->assertRedirect('/baak?tab=schedules');

        $sched->refresh();
        $this->assertEquals('Keamanan Aplikasi Web', $sched->course_name);
        $this->assertEquals('KSI-5B', $sched->class_name);

        // 3. DELETE schedule
        $this->actingAs($baak)->delete("/baak/schedules/{$sched->id}")
            ->assertRedirect('/baak?tab=schedules');

        $this->assertDatabaseMissing('class_schedules', ['id' => $sched->id]);

        // 4. CREATE Room
        $this->actingAs($baak)->post('/baak/rooms', [
            'building_id' => 1,
            'floor'       => 2,
            'room_code'   => 'GKT-203',
            'room_name'   => 'Ruang Kelas GKT 203',
            'room_type'   => 'Ruang Kelas',
            'capacity'    => 40,
        ])->assertRedirect('/baak?tab=rooms');

        $room = Room::where('room_code', 'GKT-203')->firstOrFail();

        // 5. UPDATE Room
        $this->actingAs($baak)->put("/baak/rooms/{$room->id}", [
            'room_name' => 'Ruang Diskusi GKT 203',
            'room_type' => 'Ruang Diskusi',
            'capacity'  => 50,
            'status'    => 'available',
        ])->assertRedirect('/baak?tab=rooms');

        $room->refresh();
        $this->assertEquals('Ruang Diskusi GKT 203', $room->room_name);

        // 6. DELETE Room
        $this->actingAs($baak)->delete("/baak/rooms/{$room->id}")
            ->assertRedirect('/baak?tab=rooms');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);

        // 7. BAAK CRUD Akun Mahasiswa (Mahasiswa dihandle oleh BAAK)
        $this->actingAs($baak)->post('/baak/students', [
            'full_name' => 'Andi Pratama (Mahasiswa TI)',
            'email'     => 'andi.mhs@roomreserve.ac.id',
            'password'  => 'password123',
        ])->assertRedirect('/baak?tab=students');

        $student = User::where('email', 'andi.mhs@roomreserve.ac.id')->firstOrFail();
        $this->assertEquals('mahasiswa', $student->role);

        $this->actingAs($baak)->put("/baak/students/{$student->id}", [
            'full_name' => 'Andi Pratama (Ketua Kelas TI)',
            'email'     => 'andi.mhs@roomreserve.ac.id',
            'status'    => 'active',
        ])->assertRedirect('/baak?tab=students');

        $student->refresh();
        $this->assertEquals('Andi Pratama (Ketua Kelas TI)', $student->full_name);

        // 8. BAAK memperbarui password Mahasiswa/Organisasi yang lupa password (setelah verifikasi Komting/Organisasi)
        $this->actingAs($baak)->put("/baak/students/{$student->id}/password", [
            'password'           => 'sandibaru123',
            'verification_notes' => 'Hadir di BAAK - Komting Kelas TI-5A',
        ])->assertRedirect('/baak?tab=students');

        $student->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('sandibaru123', $student->password_hash));

        $this->actingAs($baak)->delete("/baak/students/{$student->id}")
            ->assertRedirect('/baak?tab=students');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_admin_crud_users(): void
    {
        $admin = User::where('email', 'admin@roomreserve.ac.id')->firstOrFail();

        // 1. Admin HANYA bisa membuat akun Dosen, Kalab, atau BAAK
        $this->actingAs($admin)->post('/admin/users', [
            'full_name' => 'Teknisi Lab TI',
            'email'     => 'teknisi@roomreserve.ac.id',
            'password'  => 'password123',
            'role'      => 'kalab',
        ])->assertSessionHas('success');

        $newUser = User::where('email', 'teknisi@roomreserve.ac.id')->firstOrFail();

        // 2. Admin TIDAK BISA membuat akun Admin baru (dibuat di database)
        $this->actingAs($admin)->post('/admin/users', [
            'full_name' => 'Admin Kedua',
            'email'     => 'admin2@roomreserve.ac.id',
            'password'  => 'password123',
            'role'      => 'admin',
        ])->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'admin2@roomreserve.ac.id']);

        // 3. Admin TIDAK BISA membuat akun Mahasiswa (dihandle oleh BAAK)
        $this->actingAs($admin)->post('/admin/users', [
            'full_name' => 'Mahasiswa Baru',
            'email'     => 'mhs.baru@roomreserve.ac.id',
            'password'  => 'password123',
            'role'      => 'mahasiswa',
        ])->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'mhs.baru@roomreserve.ac.id']);

        // 4. Admin hanya bisa VIEW status kelas & lab, TIDAK BISA mengubah status ruangan
        $this->actingAs($admin)->get('/admin?tab=rooms')->assertStatus(200);
        $this->actingAs($admin)->post('/baak/rooms/toggle', ['room_id' => 1])->assertRedirect('/admin');

        // 5. UPDATE user (Dosen/Kalab/BAAK)
        $this->actingAs($admin)->put("/admin/users/{$newUser->id}", [
            'full_name' => 'Teknisi Senior Lab TI',
            'email'     => 'teknisi@roomreserve.ac.id',
            'role'      => 'kalab',
            'status'    => 'active',
        ])->assertSessionHas('success');

        $newUser->refresh();
        $this->assertEquals('Teknisi Senior Lab TI', $newUser->full_name);

        // 6. Admin memperbarui password Dosen/Kalab/BAAK yang lupa password (konfirmasi via WA)
        $this->actingAs($admin)->put("/admin/users/{$newUser->id}/password", [
            'password' => 'kalabbaru123',
        ])->assertSessionHas('success');

        $newUser->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('kalabbaru123', $newUser->password_hash));

        // 7. DELETE user (Dosen/Kalab/BAAK)
        $this->actingAs($admin)->delete("/admin/users/{$newUser->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_dosen_can_also_borrow_lab_or_gkt_room(): void
    {
        $dosen = User::where('email', 'zuliarefendi@roomreserve.ac.id')->firstOrFail();
        $kalab = User::where('email', 'kasmawi@roomreserve.ac.id')->firstOrFail();
        $labJarkom = Room::where('room_code', 'LAB-TI-01')->firstOrFail();

        // 1. Dosen mengajukan peminjaman Lab TI -> MENUNGGU_KALAB
        $this->actingAs($dosen)->post('/reservations', [
            'room_id'          => $labJarkom->id,
            'reservation_date' => '2026-11-28',
            'start_time'       => '08:00',
            'end_time'         => '11:00',
            'purpose'          => 'Praktikum Tambahan Jaringan Komputer',
        ])->assertSessionHas('success');

        $res = Reservation::where('purpose', 'Praktikum Tambahan Jaringan Komputer')->firstOrFail();
        $this->assertEquals('MENUNGGU_KALAB', $res->status);
        $this->assertEquals($dosen->id, $res->requester_id);

        // 2. Dosen mengedit pengajuan peminjaman
        $this->actingAs($dosen)->put("/reservations/{$res->id}", [
            'room_id'          => $labJarkom->id,
            'reservation_date' => '2026-11-28',
            'start_time'       => '13:00',
            'end_time'         => '16:00',
            'purpose'          => 'Praktikum Tambahan Jaringan Komputer (Sesi Siang)',
        ])->assertSessionHas('success');

        $res->refresh();
        $this->assertEquals('13:00', $res->start_time);
        $this->assertEquals('Praktikum Tambahan Jaringan Komputer (Sesi Siang)', $res->purpose);

        // 3. Dosen membatalkan pengajuan
        $this->actingAs($dosen)->delete("/reservations/{$res->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reservations', ['id' => $res->id]);
    }

    public function test_six_step_reservation_and_approval_workflow(): void
    {
        $mahasiswa = User::where('email', 'mahasiswa@roomreserve.ac.id')->firstOrFail();
        $dosen     = User::where('email', 'zuliarefendi@roomreserve.ac.id')->firstOrFail();
        $kalab     = User::where('email', 'kasmawi@roomreserve.ac.id')->firstOrFail();

        $gkt201  = Room::where('room_code', 'GKT-201')->firstOrFail();
        $gkt302  = Room::where('room_code', 'GKT-302')->firstOrFail();
        $labTi03 = Room::where('room_code', 'LAB-TI-03')->firstOrFail();

        // Setup 1 class schedule on GKT-201 to test Dosen routing
        ClassSchedule::create([
            'room_id'       => $gkt201->id,
            'lecturer_id'   => $dosen->id,
            'course_name'   => 'Arsitektur Cloud & Docker (TI-5B)',
            'schedule_date' => '2026-11-11',
            'start_time'    => '13:00',
            'end_time'      => '15:30',
        ]);

        // 1. GKT with class schedule -> MENUNGGU_DOSEN
        $resDosen = $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id'          => $gkt201->id,
            'reservation_date' => '2026-11-11',
            'start_time'       => '13:30',
            'end_time'         => '15:00',
            'purpose'          => 'Diskusi Kelompok Arsitektur Cloud',
        ]);
        $resDosen->assertSessionHas('success');

        $r1 = Reservation::where('purpose', 'Diskusi Kelompok Arsitektur Cloud')->firstOrFail();
        $this->assertEquals('MENUNGGU_DOSEN', $r1->status);
        $this->assertDatabaseHas('approvals', [
            'reservation_id' => $r1->id,
            'approver_id'    => $dosen->id,
            'approver_type'  => 'DOSEN',
            'decision'       => 'MENUNGGU',
        ]);

        // 2. GKT without class schedule -> MENUNGGU_BAAK
        $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id'          => $gkt302->id,
            'reservation_date' => '2026-11-11',
            'start_time'       => '13:00',
            'end_time'         => '15:00',
            'purpose'          => 'Rapat Panitia Seminar GKT',
        ]);
        $r2 = Reservation::where('purpose', 'Rapat Panitia Seminar GKT')->firstOrFail();
        $this->assertEquals('MENUNGGU_BAAK', $r2->status);
        $this->assertDatabaseHas('approvals', [
            'reservation_id' => $r2->id,
            'approver_type'  => 'BAAK',
            'decision'       => 'MENUNGGU',
        ]);

        // 3. Gedung TI Lab -> MENUNGGU_KALAB
        $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id'          => $labTi03->id,
            'reservation_date' => '2026-11-12',
            'start_time'       => '09:00',
            'end_time'         => '11:00',
            'purpose'          => 'Praktikum Mandiri Docker & Kubernetes',
        ]);
        $r3 = Reservation::where('purpose', 'Praktikum Mandiri Docker & Kubernetes')->firstOrFail();
        $this->assertEquals('MENUNGGU_KALAB', $r3->status);
        $this->assertDatabaseHas('approvals', [
            'reservation_id' => $r3->id,
            'approver_id'    => $kalab->id,
            'approver_type'  => 'KALAB',
            'decision'       => 'MENUNGGU',
        ]);

        // 4. Kalab Approves Lab Reservation -> DISETUJUI & enters Room Calendar (blocks conflicts)
        $this->actingAs($kalab)->post('/reservations/decide', [
            'reservation_id' => $r3->id,
            'decision'       => 'DISETUJUI',
            'notes'          => 'Silakan gunakan lab dengan tertib.',
        ]);
        $r3->refresh();
        $this->assertEquals('DISETUJUI', $r3->status);

        // Verify conflict detection blocks overlapping booking on same room/date/time
        $conflictRes = $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id'          => $labTi03->id,
            'reservation_date' => '2026-11-12',
            'start_time'       => '10:00',
            'end_time'         => '12:00',
            'purpose'          => 'Booking Bentrok',
        ]);
        $conflictRes->assertSessionHas('error');
        $this->assertDatabaseMissing('reservations', ['purpose' => 'Booking Bentrok']);

        // 5. Dosen Rejects without reason -> Blocked; Rejects with reason -> DITOLAK
        $rejectNoReason = $this->actingAs($dosen)->post('/reservations/decide', [
            'reservation_id' => $r1->id,
            'decision'       => 'DITOLAK',
            'notes'          => '   ',
        ]);
        $rejectNoReason->assertSessionHas('error');
        $r1->refresh();
        $this->assertEquals('MENUNGGU_DOSEN', $r1->status);

        $this->actingAs($dosen)->post('/reservations/decide', [
            'reservation_id' => $r1->id,
            'decision'       => 'DITOLAK',
            'notes'          => 'Ruangan dipakai untuk ujian tengah semester susulan.',
        ]);
        $r1->refresh();
        $this->assertEquals('DITOLAK', $r1->status);
        $this->assertDatabaseHas('approvals', [
            'reservation_id' => $r1->id,
            'decision'       => 'DITOLAK',
            'notes'          => 'Ruangan dipakai untuk ujian tengah semester susulan.',
        ]);

        // 6. Verifikasi 4 Jenis Relasi ERD (1:N, N:1, M:N, 1:1)
        // One-to-Many (1:N): Pengguna -> Pengajuan, Ruangan -> Pengajuan, Penyetuju -> Persetujuan
        $this->assertGreaterThanOrEqual(3, $mahasiswa->reservations()->count());
        $this->assertGreaterThanOrEqual(1, $labTi03->reservations()->count());
        $this->assertGreaterThanOrEqual(1, $kalab->handledApprovals()->count());

        // Many-to-One (N:1): Pengajuan -> Pengguna, Pengajuan -> Ruangan
        $this->assertEquals($mahasiswa->id, $r3->requester->id);
        $this->assertEquals($labTi03->id, $r3->room->id);

        // Many-to-Many (M:N): Pengguna <-> Ruangan melalui tabel Pengajuan (reservations)
        $this->assertTrue($mahasiswa->borrowedRooms()->where('rooms.id', $labTi03->id)->exists());
        $this->assertTrue($labTi03->borrowers()->where('users.id', $mahasiswa->id)->exists());

        // One-to-One (1:1 / 0..1): Pengajuan -> Persetujuan
        $this->assertNotNull($r3->approval);
        $this->assertEquals('DISETUJUI', $r3->approval->decision);
    }

    public function test_profile_access_and_password_update_for_all_roles(): void
    {
        $emails = [
            'mahasiswa@roomreserve.ac.id',
            'zuliarefendi@roomreserve.ac.id',
            'kasmawi@roomreserve.ac.id',
            'baak@roomreserve.ac.id',
            'admin@roomreserve.ac.id',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->actingAs($user)->get('/profile')->assertStatus(200);
        }

        // Kalab dapat melihat Daftar Ruangan dengan status dinamis berbasis tanggal
        $kalab = User::where('email', 'kasmawi@roomreserve.ac.id')->firstOrFail();
        $this->actingAs($kalab)->get('/kalab?tab=rooms')->assertStatus(200);

        // Ubah password sendiri gagal jika kata sandi saat ini salah
        $this->actingAs($kalab)->put('/profile/password', [
            'current_password'          => 'salah123',
            'new_password'              => 'baru1234',
            'new_password_confirmation' => 'baru1234',
        ])->assertSessionHas('error');

        // Ubah password sendiri berhasil jika kata sandi saat ini cocok
        $this->actingAs($kalab)->put('/profile/password', [
            'current_password'          => 'password123',
            'new_password'              => 'baru1234',
            'new_password_confirmation' => 'baru1234',
        ])->assertSessionHas('success');

        $kalab->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('baru1234', $kalab->password_hash));
    }
}
