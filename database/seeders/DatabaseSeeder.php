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
     * - 7 Kepala Laboratorium (Kalab) sesuai daftar resmi Jurusan TI
     * - 26 Dosen Pengampu (termasuk Pimpinan, Kaprodi, dan seluruh Dosen Pengajar)
     * - Akun BAAK, Mahasiswa/Organisasi, dan Administrator Sistem (Database Seeded)
     * - Gedung GKT (4 Lantai, 8 Ruang Kelas) & Gedung TI (2 Lantai, 9 Laboratorium)
     */
    public function run(): void
    {
        $password = Hash::make('password123');
        $now = now();

        // 1. MASTER USERS — MAHASISWA (KOMTING KELAS) & ORGANISASI
        User::create([
            'full_name'     => 'Budi Santoso (Komting TI-5A)',
            'email'         => 'mahasiswa@roomreserve.ac.id',
            'phone'         => '081276001001',
            'password_hash' => $password,
            'role'          => 'mahasiswa',
            'status'        => 'active',
        ]);
        User::create([
            'full_name'     => 'Rizky Pratama (Ketua HMTI / BEM)',
            'email'         => 'bem@roomreserve.ac.id',
            'phone'         => '081276001002',
            'password_hash' => $password,
            'role'          => 'mahasiswa',
            'status'        => 'active',
        ]);

        // 2. MASTER USERS — 7 KEPALA LABORATORIUM (KALAB GEDUNG TI)
        $kalabList = [
            ['key' => 'desi',     'name' => 'Desi Amirullah, M.T.',           'email' => 'andes@roomreserve.ac.id'],
            ['key' => 'kasmawi',  'name' => 'Kasmawi, M.Kom. CISA',           'email' => 'kasmawi@roomreserve.ac.id'],
            ['key' => 'miftahul', 'name' => 'Miftahul Jannah, S.Pd., M.Kom.', 'email' => 'miftahuljannah@roomreserve.ac.id'],
            ['key' => 'fahmi',    'name' => 'Nurul Fahmi, S.ST., M.T.',       'email' => 'nurulfahmi@roomreserve.ac.id'],
            ['key' => 'rezki',    'name' => 'Rezki Kurniati, S.Kom., M.Kom.', 'email' => 'rezki@roomreserve.ac.id'],
            ['key' => 'musri',    'name' => 'Tengku Musri, S.ST., M.Kom.',    'email' => 'tengkumusri@roomreserve.ac.id'],
            ['key' => 'niky',     'name' => 'Niky Hardinata, M.Kom.',         'email' => 'nikyhardinata@roomreserve.ac.id'],
        ];

        $kalabs = [];
        foreach ($kalabList as $idx => $k) {
            $kalabs[$k['key']] = User::create([
                'full_name'     => $k['name'],
                'email'         => $k['email'],
                'phone'         => '0812760020' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                'password_hash' => $password,
                'role'          => 'kalab',
                'status'        => 'active',
            ]);
        }

        // 3. MASTER USERS — 26 DOSEN PENGAMPU (PIMPINAN, KAPRODI, & DOSEN PENGAJAR)
        $dosenList = [
            ['Agus Tedyyana, M.Kom., Ph.D.',             'agustedyyana@roomreserve.ac.id'],
            ['Fajar Ratnawati, S.Kom., M.Cs.',           'fajar@roomreserve.ac.id'],
            ['Depandi Enda, S.ST., M.Kom.',              'depandienda@roomreserve.ac.id'],
            ['Wahyat, S.Kom., M.Kom',                    'wahyat@roomreserve.ac.id'],
            ['Nurmi Hidayasari, S.T., M.Kom.',           'nurmihidayasari@roomreserve.ac.id'],
            ['Danuri, M.Cs',                             'danuri@roomreserve.ac.id'],
            ['Fajri Profesio Putra, S.Kom., M.Cs.',      'fajri@roomreserve.ac.id'],
            ['Jaroji',                                   'jaroji@roomreserve.ac.id'],
            ['Lipantri Mashur Gultom',                   'lipantri@roomreserve.ac.id'],
            ['Mansur, S.Kom., M.Kom.',                   'mansur@roomreserve.ac.id'],
            ['Muhamad Nasir',                            'muhamadnasir@roomreserve.ac.id'],
            ['Sri Mawarni',                              'srimawarni@roomreserve.ac.id'],
            ['Supria, S.ST., M.Kom.',                    'phiya@roomreserve.ac.id'],
            ['Afis Julianto, M.Kom',                     'afisjulianto@roomreserve.ac.id'],
            ['Desi Wahana, M. Li.',                      'desiwahana@roomreserve.ac.id'],
            ['Eko Prayitno, M.Kom.',                     'ekoprayitno@roomreserve.ac.id'],
            ['Elvi Rahmi, S.T., M.Kom.',                 'elvirahmi@roomreserve.ac.id'],
            ['Eva Kurniawaty',                           'evakurniawaty@roomreserve.ac.id'],
            ['Eva Yumami, S.Kom., M.T',                  'evayumami@roomreserve.ac.id'],
            ['I Gusti Agung Putu Mahendra, M.Kom.',      'mahendra@roomreserve.ac.id'],
            ['Muhammad Asep Subandri, M.Kom.',           'subandri@roomreserve.ac.id'],
            ['Muhammad Ikhsan Wibowo, S.Kom., M.T.',     'mikhsanw@roomreserve.ac.id'],
            ['Muhammad Ridho Nosa, S.T.,M.Kom',          'ridhonosa@roomreserve.ac.id'],
            ['PRETTI RISTRA, S.Pd., M.Ed.',              'prettirista@roomreserve.ac.id'],
            ['Zuliar Efendi, M.Kom.',                    'zuliarefendi@roomreserve.ac.id'],
            ['Ryci Rahmatil Fiska, S.Kom., M.Kom.',      'rycirf@roomreserve.ac.id'],
        ];

        foreach ($dosenList as $idx => $d) {
            User::create([
                'full_name'     => $d[0],
                'email'         => $d[1],
                'phone'         => '0812760030' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                'password_hash' => $password,
                'role'          => 'dosen',
                'status'        => 'active',
            ]);
        }

        // 4. MASTER USERS — BAAK & ADMINISTRATOR SISTEM (DIBUAT DI DATABASE)
        User::create([
            'full_name'     => 'Petugas Akademik (BAAK)',
            'email'         => 'baak@roomreserve.ac.id',
            'phone'         => '081276004001',
            'password_hash' => $password,
            'role'          => 'baak',
            'status'        => 'active',
        ]);

        $admin = User::create([
            'full_name'     => 'Administrator Sistem',
            'email'         => 'admin@roomreserve.ac.id',
            'phone'         => '081276005001',
            'password_hash' => $password,
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        // 5. MASTER BUILDINGS (Gedung GKT 4 Lantai & Gedung TI 2 Lantai)
        $gkt = Building::create(['name' => 'GKT', 'total_floors' => 4]);
        $ti  = Building::create(['name' => 'TI',  'total_floors' => 2]);

        // 6. MASTER ROOMS — GEDUNG GKT (Lantai 1 - 4)
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

        // 7. MASTER ROOMS — LABORATORIUM GEDUNG TI SESUAI DATA RESMI FASILITAS & PEMETAAN KALAB
        $tiLabsData = [
            [1, 'LAB-TI-01', 'Laboratorium Artificial Intelligence',          32, $kalabs['miftahul']->id],
            [1, 'LAB-TI-02', 'Laboratorium Pengembangan Perangkat Lunak',     30, $kalabs['desi']->id],
            [1, 'LAB-TI-03', 'Laboratorium Keamanan Informasi',               32, $kalabs['kasmawi']->id],
            [1, 'LAB-TI-04', 'Laboratorium High Performance Computing',       32, $kalabs['desi']->id],
            [1, 'LAB-TI-05', 'Lab Pemrograman',                               32, $kalabs['miftahul']->id],
            [1, 'LAB-TI-06', 'Laboratorium Software Testing',                 32, $kalabs['rezki']->id],
            [1, 'LAB-TI-07', 'Laboratorium Internet of Things',               32, $kalabs['fahmi']->id],
            [2, 'LAB-TI-08', 'Laboratorium Multimedia',                       32, $kalabs['niky']->id],
            [2, 'LAB-TI-09', 'Laboratorium Jaringan Komputer',                32, $kalabs['musri']->id],
            [2, 'LAB-TI-10', 'Laboratorium Sistem Informasi',                 30, $kalabs['rezki']->id],
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

            LabManager::create([
                'room_id'     => $room->id,
                'user_id'     => $lab[4],
                'assigned_at' => $now,
            ]);
        }

        // 8. LOG INISIALISASI SISTEM
        AuditLog::create([
            'user_id'    => $admin->id,
            'action'     => 'SYSTEM_INITIALIZED',
            'entity'     => 'SYSTEM',
            'entity_id'  => 1,
            'created_at' => $now,
        ]);
    }
}
