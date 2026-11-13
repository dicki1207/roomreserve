<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\LabManager;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Master Data RoomReserve:
     * - Akun 5 Role (Mahasiswa, Dosen, Kalab, BAAK, Admin) untuk pengujian login
     * - Gedung GKT (4 Lantai) & Gedung TI (2 Lantai)
     * - 17 Ruangan Master (8 Ruang GKT & 9 Laboratorium TI)
     * - Pemetaan Kepala Laboratorium untuk 9 Lab TI
     * (Semua data transaksi peminjaman & jadwal bersih tanpa data dummy)
     */
    public function run(): void
    {
        $password = Hash::make('password123');
        $now = now();

        // 1. MASTER USERS (5 Role untuk Pengujian Autentikasi & Hak Akses)
        User::create([
            'full_name'     => 'Budi Santoso (Ketua HMTI)',
            'email'         => 'mahasiswa@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'mahasiswa',
            'status'        => 'active',
        ]);
        User::create([
            'full_name'     => 'Rizky Pratama (BEM)',
            'email'         => 'bem@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'mahasiswa',
            'status'        => 'active',
        ]);

        $dosen1 = User::create([
            'full_name'     => 'Dr. Hendra Saputra, M.Kom.',
            'email'         => 'dosen.hendra@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'dosen',
            'status'        => 'active',
        ]);
        User::create([
            'full_name'     => 'Prof. Bambang Irawan, Ph.D.',
            'email'         => 'dosen.bambang@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'dosen',
            'status'        => 'active',
        ]);

        $kalab1 = User::create([
            'full_name'     => 'Ir. Rahmat Hidayat, S.ST., M.T.',
            'email'         => 'kalab.rahmat@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'kalab',
            'status'        => 'active',
        ]);
        $kalab2 = User::create([
            'full_name'     => 'Dewi Sartika, S.Kom., M.Kom.',
            'email'         => 'kalab.dewi@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'kalab',
            'status'        => 'active',
        ]);
        $kalab3 = User::create([
            'full_name'     => 'Fajar Pratama, S.ST., M.T.',
            'email'         => 'kalab.fajar@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'kalab',
            'status'        => 'active',
        ]);

        User::create([
            'full_name'     => 'Siti Aminah, S.E., M.Si. (BAAK)',
            'email'         => 'baak@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'baak',
            'status'        => 'active',
        ]);

        $admin = User::create([
            'full_name'     => 'Administrator Sistem',
            'email'         => 'admin@roomreserve.ac.id',
            'password_hash' => $password,
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        // 2. MASTER BUILDINGS (Gedung GKT 4 Lantai & Gedung TI 2 Lantai)
        $gkt = Building::create(['name' => 'GKT', 'total_floors' => 4]);
        $ti  = Building::create(['name' => 'TI',  'total_floors' => 2]);

        // 3. MASTER ROOMS (Ruang GKT Lantai 1-4 & 9 Laboratorium Gedung TI)
        $gktRoomsData = [
            [1, 'GKT-101', 'Ruang Kelas GKT 101', 'Ruang Kelas', 40],
            [1, 'GKT-102', 'Ruang Kelas GKT 102', 'Ruang Kelas', 40],
            [2, 'GKT-201', 'Ruang Kelas GKT 201', 'Ruang Kelas', 45],
            [2, 'GKT-202', 'Ruang Kelas GKT 202', 'Ruang Kelas', 45],
            [3, 'GKT-301', 'Ruang Kuliah Gabungan GKT 301', 'Kelas Besar', 80],
            [3, 'GKT-302', 'Ruang Kelas GKT 302', 'Ruang Kelas', 40],
            [4, 'GKT-401', 'Aula Seminar GKT Lantai 4', 'Aula Seminar', 150],
            [4, 'GKT-402', 'Ruang Kelas GKT 402', 'Ruang Kelas', 40],
        ];
        foreach ($gktRoomsData as $r) {
            Room::create([
                'building_id' => $gkt->id,
                'floor'       => $r[0],
                'room_code'   => $r[1],
                'room_name'   => $r[2],
                'room_type'   => $r[3],
                'capacity'    => $r[4],
                'status'      => 'available',
            ]);
        }

        $tiLabsData = [
            [1, 'LAB-TI-01', 'Lab. Jaringan Komputer (Jarkom)', 36, $kalab1->id],
            [1, 'LAB-TI-02', 'Lab. Keamanan Siber & Cloud (Docker)', 32, $kalab1->id],
            [1, 'LAB-TI-03', 'Lab. Administrasi Server & Kontainer', 32, $kalab1->id],
            [1, 'LAB-TI-04', 'Lab. Basis Data Terpadu', 36, $kalab2->id],
            [1, 'LAB-TI-05', 'Lab. Pemrograman Dasar & Algoritma', 40, $kalab2->id],
            [2, 'LAB-TI-06', 'Lab. Rekayasa Perangkat Lunak (RPL)', 36, $kalab2->id],
            [2, 'LAB-TI-07', 'Lab. Sistem Tertanam & IoT', 30, $kalab3->id],
            [2, 'LAB-TI-08', 'Lab. Multimedia & Animasi 3D', 32, $kalab3->id],
            [2, 'LAB-TI-09', 'Lab. Kecerdasan Buatan (AI) & Data Science', 30, $kalab3->id],
        ];
        foreach ($tiLabsData as $lab) {
            $room = Room::create([
                'building_id' => $ti->id,
                'floor'       => $lab[0],
                'room_code'   => $lab[1],
                'room_name'   => $lab[2],
                'room_type'   => 'Laboratorium TI',
                'capacity'    => $lab[3],
                'status'      => 'available',
            ]);

            // 4. PEMETAAN KALAB (LAB_MANAGERS)
            LabManager::create([
                'room_id'     => $room->id,
                'user_id'     => $lab[4],
                'assigned_at' => $now,
            ]);
        }

        // 5. LOG INISIALISASI SISTEM (BERSIH TANPA DATA DUMMY)
        AuditLog::create([
            'user_id'    => $admin->id,
            'action'     => 'SYSTEM_INITIALIZED',
            'entity'     => 'SYSTEM',
            'entity_id'  => 1,
            'created_at' => $now,
        ]);
    }
}
