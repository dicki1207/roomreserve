<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\ClassSchedule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomReserveFixesTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasicData(): array
    {
        $password = Hash::make('password123');

        $mahasiswa = User::create([
            'full_name' => 'Mahasiswa Uji',
            'email' => 'mhs@test.ac.id',
            'password_hash' => $password,
            'role' => 'mahasiswa',
            'status' => 'active',
        ]);

        $dosen = User::create([
            'full_name' => 'Dosen Uji',
            'email' => 'dosen@test.ac.id',
            'password_hash' => $password,
            'role' => 'dosen',
            'status' => 'active',
        ]);

        $baak = User::create([
            'full_name' => 'BAAK Uji',
            'email' => 'baak@test.ac.id',
            'password_hash' => $password,
            'role' => 'baak',
            'status' => 'active',
        ]);

        $gkt = Building::create(['name' => 'GKT', 'total_floors' => 3]);
        $room = Room::create([
            'building_id' => $gkt->id,
            'floor' => 1,
            'room_code' => 'GKT-101',
            'room_name' => 'Ruang Kelas GKT 101',
            'room_type' => 'Ruang Kelas',
            'capacity' => 40,
            'status' => 'available',
        ]);

        return compact('mahasiswa', 'dosen', 'baak', 'room');
    }

    public function test_operational_hours_limit_and_full_room_status(): void
    {
        ['mahasiswa' => $mahasiswa, 'room' => $room] = $this->seedBasicData();

        // Di luar jam operasional (sebelum 08:00 atau lewat 21:30) harus ditolak
        $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id' => $room->id,
            'reservation_date' => '2026-12-01',
            'start_time' => '07:00',
            'end_time' => '09:00',
            'purpose' => 'Rapat Pagi',
        ])->assertSessionHas('error');

        $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id' => $room->id,
            'reservation_date' => '2026-12-01',
            'start_time' => '20:00',
            'end_time' => '22:00',
            'purpose' => 'Rapat Malam',
        ])->assertSessionHas('error');

        // Isi penuh jam operasional 08:00 - 21:30 dengan peminjaman DISETUJUI
        Reservation::create([
            'room_id' => $room->id,
            'requester_id' => $mahasiswa->id,
            'purpose' => 'Seminar Seharian',
            'reservation_date' => '2026-12-02',
            'start_time' => '08:00',
            'end_time' => '15:00',
            'status' => 'DISETUJUI',
            'submitted_at' => now(),
        ]);
        Reservation::create([
            'room_id' => $room->id,
            'requester_id' => $mahasiswa->id,
            'purpose' => 'Workshop Sore-Malam',
            'reservation_date' => '2026-12-02',
            'start_time' => '15:00',
            'end_time' => '21:30',
            'status' => 'DISETUJUI',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($mahasiswa)->get('/mahasiswa?tab=rooms&date=2026-12-02');
        $response->assertStatus(200);
        $response->assertSee('Penuh');
    }

    public function test_notifications_sent_on_baak_decision_and_can_be_deleted(): void
    {
        ['mahasiswa' => $mahasiswa, 'dosen' => $dosen, 'baak' => $baak, 'room' => $room] = $this->seedBasicData();

        // Mahasiswa mengajukan peminjaman
        $this->actingAs($mahasiswa)->post('/reservations', [
            'room_id' => $room->id,
            'reservation_date' => '2026-12-05',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'purpose' => 'Kegiatan HMTI',
        ])->assertSessionHas('success');

        $resMhs = Reservation::where('requester_id', $mahasiswa->id)->firstOrFail();

        // BAAK menyetujui -> notifikasi masuk ke mahasiswa
        $this->actingAs($baak)->post('/reservations/decide', [
            'reservation_id' => $resMhs->id,
            'decision' => 'DISETUJUI',
            'notes' => 'Disetujui oleh BAAK.',
        ])->assertSessionHas('success');

        $notifMhs = UserNotification::where('user_id', $mahasiswa->id)->first();
        $this->assertNotNull($notifMhs);
        $this->assertSame('Peminjaman ruangan disetujui', $notifMhs->title);

        // Dosen mengajukan peminjaman lalu ditolak BAAK -> notifikasi masuk ke dosen
        $this->actingAs($dosen)->post('/reservations', [
            'room_id' => $room->id,
            'reservation_date' => '2026-12-06',
            'start_time' => '13:00',
            'end_time' => '15:00',
            'purpose' => 'Kelas Tambahan',
        ]);
        $resDosen = Reservation::where('requester_id', $dosen->id)->firstOrFail();

        $this->actingAs($baak)->post('/reservations/decide', [
            'reservation_id' => $resDosen->id,
            'decision' => 'DITOLAK',
            'notes' => 'Ruangan dipakai ujian akademik.',
        ])->assertSessionHas('success');

        $notifDosen = UserNotification::where('user_id', $dosen->id)->first();
        $this->assertNotNull($notifDosen);
        $this->assertSame('Peminjaman ruangan ditolak', $notifDosen->title);

        // Mahasiswa dapat menghapus notifikasinya
        $this->actingAs($mahasiswa)
            ->deleteJson("/notifications/{$notifMhs->id}")
            ->assertOk();
        $this->assertDatabaseMissing('user_notifications', ['id' => $notifMhs->id]);
    }

    public function test_baak_recurring_day_buttons_generate_weekly_schedules(): void
    {
        ['dosen' => $dosen, 'baak' => $baak, 'room' => $room] = $this->seedBasicData();

        // Pilih Senin (1) dan Rabu (3) selama 4 minggu mulai 2026-11-30 (Senin)
        $this->actingAs($baak)->post('/baak/schedules', [
            'room_id' => $room->id,
            'lecturer_id' => $dosen->id,
            'course_name' => 'Pemrograman Web',
            'class_name' => 'TI-3A',
            'schedule_date' => '2026-11-30',
            'start_time' => '08:00',
            'end_time' => '10:30',
            'repeat_days' => [1, 3],
            'repeat_weeks' => 4,
        ])->assertRedirect(route('role.baak', ['tab' => 'schedules', 'date' => '2026-11-30']));

        // 2 hari x 4 minggu = 8 jadwal kuliah otomatis terbuat pada hari Senin & Rabu
        $schedules = ClassSchedule::where('course_name', 'Pemrograman Web')->get();
        $this->assertCount(8, $schedules);

        // Dashboard BAAK & Dosen hanya menampilkan jadwal pada tanggal yang dipilih (tidak membanjiri list dengan semua minggu)
        $baakPage = $this->actingAs($baak)->get('/baak?tab=schedules&date=2026-11-30');
        $baakPage->assertOk();
        $baakPage->assertSeeText('2026-11-30');
        $baakPage->assertDontSeeText('2026-12-07');
        $baakPage->assertViewHas('dailySchedules', fn ($s) => $s->count() === 1);

        $dosenPage = $this->actingAs($dosen)->get('/dosen?date=2026-11-30');
        $dosenPage->assertOk();
        $dosenPage->assertSeeText('Pemrograman Web');
        $dosenPage->assertViewHas('myLectures', fn ($l) => $l->count() === 1 && $l->first()->schedule_date === '2026-11-30');
    }
}
