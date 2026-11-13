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
        $response->assertSee('dosen.hendra@roomreserve.ac.id');
        $response->assertSee('kalab.rahmat@roomreserve.ac.id');
        $response->assertSee('baak@roomreserve.ac.id');
        $response->assertSee('admin@roomreserve.ac.id');
    }

    public function test_all_five_roles_can_login_and_view_their_isolated_dashboards(): void
    {
        $roles = [
            'mahasiswa' => ['email' => 'mahasiswa@roomreserve.ac.id', 'route' => '/mahasiswa'],
            'dosen'     => ['email' => 'dosen.hendra@roomreserve.ac.id', 'route' => '/dosen'],
            'kalab'     => ['email' => 'kalab.rahmat@roomreserve.ac.id', 'route' => '/kalab'],
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
        $dosen = User::where('email', 'dosen.hendra@roomreserve.ac.id')->firstOrFail();
        $gkt   = Room::where('room_code', 'GKT-101')->firstOrFail();

        // 1. CREATE schedule
        $this->actingAs($baak)->post('/baak/schedules', [
            'room_id'       => $gkt->id,
            'lecturer_id'   => $dosen->id,
            'course_name'   => 'Kriptografi & Keamanan Web',
            'schedule_date' => '2026-11-21',
            'start_time'    => '08:00',
            'end_time'      => '10:30',
        ])->assertRedirect('/baak?tab=schedules');

        $sched = ClassSchedule::where('course_name', 'Kriptografi & Keamanan Web')->firstOrFail();

        // 2. UPDATE schedule
        $this->actingAs($baak)->put("/baak/schedules/{$sched->id}", [
            'room_id'       => $gkt->id,
            'lecturer_id'   => $dosen->id,
            'course_name'   => 'Kriptografi Lanjut',
            'schedule_date' => '2026-11-21',
            'start_time'    => '08:00',
            'end_time'      => '11:00',
        ])->assertRedirect('/baak?tab=schedules');

        $sched->refresh();
        $this->assertEquals('Kriptografi Lanjut', $sched->course_name);

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
    }

    public function test_admin_crud_users(): void
    {
        $admin = User::where('email', 'admin@roomreserve.ac.id')->firstOrFail();

        // 1. CREATE user
        $this->actingAs($admin)->post('/admin/users', [
            'full_name' => 'Teknisi Lab TI',
            'email'     => 'teknisi@roomreserve.ac.id',
            'password'  => 'password123',
            'role'      => 'kalab',
        ])->assertSessionHas('success');

        $newUser = User::where('email', 'teknisi@roomreserve.ac.id')->firstOrFail();

        // 2. UPDATE user
        $this->actingAs($admin)->put("/admin/users/{$newUser->id}", [
            'full_name' => 'Teknisi Senior Lab TI',
            'email'     => 'teknisi@roomreserve.ac.id',
            'role'      => 'kalab',
            'status'    => 'active',
        ])->assertSessionHas('success');

        $newUser->refresh();
        $this->assertEquals('Teknisi Senior Lab TI', $newUser->full_name);

        // 3. DELETE user
        $this->actingAs($admin)->delete("/admin/users/{$newUser->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_dosen_can_also_borrow_lab_or_gkt_room(): void
    {
        $dosen = User::where('email', 'dosen.hendra@roomreserve.ac.id')->firstOrFail();
        $kalab = User::where('email', 'kalab.rahmat@roomreserve.ac.id')->firstOrFail();
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
        $dosen     = User::where('email', 'dosen.hendra@roomreserve.ac.id')->firstOrFail();
        $kalab     = User::where('email', 'kalab.rahmat@roomreserve.ac.id')->firstOrFail();

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
    }
}
