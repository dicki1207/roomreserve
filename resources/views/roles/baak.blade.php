@extends('layouts.app')

@section('title', 'Dashboard BAAK')

@section('content')
<div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('role.baak', ['tab' => 'dashboard']) }}" class="btn {{ $tab === 'dashboard' ? 'btn-navy' : 'btn-slate' }}">Persetujuan Peminjaman ({{ $allReservations->count() }})</a>
    <a href="{{ route('role.baak', ['tab' => 'students']) }}" class="btn {{ $tab === 'students' ? 'btn-navy' : 'btn-slate' }}">Data Mahasiswa ({{ $students->count() }})</a>
    <a href="{{ route('role.baak', ['tab' => 'schedules', 'date' => $selectedDate]) }}" class="btn {{ $tab === 'schedules' ? 'btn-navy' : 'btn-slate' }}">Jadwal Kuliah &amp; Lab ({{ $dailySchedules->count() }})</a>
    <a href="{{ route('role.baak', ['tab' => 'rooms']) }}" class="btn {{ $tab === 'rooms' ? 'btn-navy' : 'btn-slate' }}">Data Ruangan & Lab ({{ $allRooms->count() }})</a>
    <a href="{{ route('role.baak', ['tab' => 'calendar']) }}" class="btn {{ $tab === 'calendar' ? 'btn-navy' : 'btn-slate' }}">Kalender Ruangan</a>
</div>

@if($tab === 'students')
    <div class="grid-main-side">
        <div style="display:flex; flex-direction:column; gap:18px;">
            <!-- PERMINTAAN RESET KATA SANDI MAHASISWA / ORGANISASI MASUK -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Permintaan Reset Kata Sandi Mahasiswa / Organisasi ({{ $pendingStudentResets->count() }})</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Keamanan: BAAK <strong>hanya dapat mengubah password</strong> akun Mahasiswa / Organisasi yang telah mengajukan permintaan resmi dari halaman <strong>Lupa Kata Sandi</strong>.
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($pendingStudentResets as $req)
                        <div class="item-box pending" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
                            <div style="flex:1; min-width:220px;">
                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-bottom:4px;">
                                    <span class="badge badge-amber">#PR-{{ $req->id }}</span>
                                    <strong>{{ $req->user->full_name }}</strong>
                                </div>
                                <div style="font-size:12px; color:#475569;">
                                    Email: <strong>{{ $req->requested_email }}</strong> • HP Terverifikasi: <strong>{{ $req->requested_phone }}</strong>
                                </div>
                                @if(!empty($req->requester_notes))
                                    <div style="font-size:12px; color:#1e40af; margin-top:2px;">
                                        Keterangan: <strong>{{ $req->requester_notes }}</strong>
                                    </div>
                                @endif
                                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                    Diajukan: {{ $req->requested_at }}
                                </div>
                            </div>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <button type="button"
                                        onclick="openResetStudentPasswordModal({{ $req->user->id }}, '{{ addslashes($req->user->full_name) }}', '{{ $req->user->email }}', '{{ addslashes($req->user->phone ?? '') }}')"
                                        class="btn btn-navy" style="padding:5px 10px; font-size:12px;">
                                    Proses &amp; Perbarui Password
                                </button>
                                <form method="POST" action="{{ route('baak.students.password.reject', $req->user->id) }}" onsubmit="return confirm('Tolak/batalkan permintaan reset #PR-{{ $req->id }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-rose" style="padding:5px 10px; font-size:12px;">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p style="color:#64748b; font-size:12.5px;">Belum ada permintaan reset kata sandi dari Mahasiswa atau Organisasi.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Daftar Akun Mahasiswa (Komting Kelas) &amp; Organisasi ({{ $students->count() }})</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Tombol reset kata sandi hanya muncul apabila Mahasiswa / Organisasi mengajukan permintaan dari halaman Lupa Kata Sandi.
                        </div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Nama &amp; Komting Kelas / Organisasi</th>
                                <th>Email &amp; Nomor HP Terdaftar</th>
                                <th>Status</th>
                                <th>Aksi BAAK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $mhs)
                                <tr>
                                    <td><strong>{{ $mhs->full_name }}</strong></td>
                                    <td>
                                        <div>{{ $mhs->email }}</div>
                                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">HP: <strong>{{ $mhs->maskedPhone() }}</strong></div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $mhs->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                                            {{ $mhs->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                            @if($mhs->pendingPasswordReset)
                                                <button type="button"
                                                        onclick="openResetStudentPasswordModal({{ $mhs->id }}, '{{ addslashes($mhs->full_name) }}', '{{ $mhs->email }}', '{{ addslashes($mhs->phone ?? '') }}')"
                                                        class="btn btn-navy" style="padding:4px 9px; font-size:11.5px;">
                                                    Proses Reset (#PR-{{ $mhs->pendingPasswordReset->id }})
                                                </button>
                                            @endif
                                            <button type="button"
                                                    onclick="openEditStudentModal({{ $mhs->id }}, '{{ addslashes($mhs->full_name) }}', '{{ $mhs->email }}', '{{ addslashes($mhs->maskedPhone()) }}', '{{ $mhs->status }}')"
                                                    class="btn btn-slate" style="padding:4px 8px; font-size:11.5px;">
                                                Edit Akun
                                            </button>
                                            <form method="POST" action="{{ route('baak.students.destroy', $mhs->id) }}" onsubmit="return confirm('Hapus akun mahasiswa {{ $mhs->full_name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-rose" style="padding:4px 8px; font-size:11.5px;">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center; color:#64748b; padding:24px;">Belum ada akun mahasiswa terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="height:fit-content;">
            <div class="card-header">
                <div class="card-title">Buat Akun Mahasiswa / Organisasi Baru</div>
            </div>
            <form method="POST" action="{{ route('baak.students.store') }}" class="card-body">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nama &amp; Komting Kelas / Organisasi</label>
                    <input type="text" name="full_name" required placeholder="Contoh: Andi Saputra (Komting TI-5A) / BEM" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Akun</label>
                    <input type="email" name="email" required placeholder="nim.nama@roomreserve.ac.id" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor HP Terdaftar (Verifikasi BAAK)</label>
                    <input type="text" name="phone" required placeholder="Contoh: 081234567890" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Kata Sandi Awal</label>
                    <input type="text" name="password" value="password123" required class="form-control">
                </div>
                <button type="submit" class="btn btn-navy" style="width:100%;">Simpan Akun Mahasiswa</button>
            </form>
        </div>
    </div>

    <!-- MODAL PERBARUI PASSWORD MAHASISWA / ORGANISASI (LUPA PASSWORD KE BAAK) -->
    <div id="reset-student-pw-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Perbarui Password Mahasiswa / Organisasi (BAAK)</div>
                <button type="button" onclick="closeResetStudentPasswordModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="reset-student-pw-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div style="padding:11px 13px; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; font-size:12.5px; color:#1e293b; margin-bottom:14px;">
                    <div><strong>Nama / Organisasi:</strong> <span id="reset-mhs-name"></span></div>
                    <div><strong>Email Akun:</strong> <span id="reset-mhs-email"></span></div>
                    <div><strong>Nomor HP Terdaftar di BAAK:</strong> <span id="reset-mhs-phone" style="color:#1d4ed8; font-weight:700;"></span></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan Verifikasi (Kesesuaian Email, Nomor HP, &amp; Komting/Organisasi)</label>
                    <input type="text" name="verification_notes" id="reset-mhs-verif" required placeholder="Contoh: Email &amp; Nomor HP sesuai data BAAK - Komting TI-5A" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="text" name="password" id="reset-mhs-password" value="password123" required class="form-control">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="button" onclick="closeResetStudentPasswordModal()" class="btn btn-slate">Batal</button>
                    <button type="submit" class="btn btn-navy">Perbarui Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT AKUN MAHASISWA -->
    <div id="edit-student-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Edit Data Akun Mahasiswa</div>
                <button type="button" onclick="closeEditStudentModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="edit-student-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nama Lengkap / Organisasi</label>
                    <input type="text" name="full_name" id="edit-student-name" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Akun <span style="font-size:11px; color:#64748b;">(Terkunci)</span></label>
                    <input type="email" id="edit-student-email" readonly class="form-control" style="background:#f1f5f9; color:#475569; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor HP Terdaftar <span style="font-size:11px; color:#64748b;">(Terkunci)</span></label>
                    <input type="text" id="edit-student-phone" readonly class="form-control" style="background:#f1f5f9; color:#475569; cursor:not-allowed;">
                </div>
                <div style="font-size:11.5px; color:#1e3a8a; background:#eff6ff; border:1px solid #bfdbfe; border-radius:7px; padding:9px 11px; margin-bottom:12px; line-height:1.5;">
                    <strong>Keamanan Permanen:</strong> <strong>Email</strong> dan <strong>Nomor HP Terdaftar</strong> dikunci setelah pembuatan akun agar tidak dapat dimanipulasi untuk membajak verifikasi Lupa Kata Sandi. Perubahan kata sandi hanya aktif bila Mahasiswa/Organisasi mengajukan permintaan resmi.
                </div>
                <div class="form-group">
                    <label class="form-label">Status Akun</label>
                    <select name="status" id="edit-student-status" class="form-control" required>
                        <option value="active">Aktif (active)</option>
                        <option value="inactive">Nonaktif (inactive)</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="button" onclick="closeEditStudentModal()" class="btn btn-slate">Batal</button>
                    <button type="submit" class="btn btn-navy">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

@elseif($tab === 'schedules')
    <div class="grid-main-side">
        <div class="card">
            <div class="card-header" style="flex-wrap:wrap; gap:10px;">
                <div>
                    <div class="card-title">Daftar Jadwal Kuliah Ruang GKT &amp; Laboratorium TI</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        Menampilkan jadwal khusus tanggal <strong>{{ $selectedDate }}</strong>
                        @if($isToday) (Hari Ini) @endif
                    </div>
                </div>
                <form method="GET" action="{{ route('role.baak') }}" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="tab" value="schedules">
                    <label style="font-size:12px; color:#475569; font-weight:600;">Tanggal:</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:5px 9px; font-size:12px; width:auto;">
                    @if(!$isToday)
                        <a href="{{ route('role.baak', ['tab' => 'schedules']) }}" class="btn btn-slate" style="padding:5px 9px; font-size:11.5px;">Hari Ini</a>
                    @endif
                </form>
            </div>
            <div class="table-wrap">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Ruangan</th>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Dosen Pengajar</th>
                            <th>Tanggal & Jam</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                        @endphp
                        @forelse($dailySchedules as $cs)
                            @php
                                $dayLabel = $indoDays[\Carbon\Carbon::parse($cs->schedule_date)->dayOfWeekIso] ?? '';
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge {{ $cs->room->building->name === 'TI' ? 'badge-royal' : 'badge-navy' }}">{{ $cs->room->room_code }}</span>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">{{ $cs->room->room_name }}</div>
                                </td>
                                <td><strong>{{ $cs->course_name }}</strong></td>
                                <td><span class="badge badge-royal">{{ $cs->class_name ?: '-' }}</span></td>
                                <td>{{ $cs->lecturer->full_name }}</td>
                                <td>
                                    <strong>{{ $dayLabel }}, {{ $cs->schedule_date }}</strong><br>
                                    <span style="color:#64748b; font-size:12px;">{{ $cs->start_time }}–{{ $cs->end_time }} WIB</span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <button type="button"
                                                onclick="openEditScheduleModal({{ $cs->id }}, {{ $cs->room_id }}, {{ $cs->lecturer_id }}, '{{ addslashes($cs->course_name) }}', '{{ addslashes($cs->class_name ?? '') }}', '{{ $cs->schedule_date }}', '{{ $cs->start_time }}', '{{ $cs->end_time }}')"
                                                class="btn btn-slate" style="padding:4px 8px; font-size:11.5px;">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('baak.schedules.destroy', $cs->id) }}" onsubmit="return confirm('Hapus jadwal kuliah tanggal {{ $cs->schedule_date }} ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-rose" style="padding:4px 8px; font-size:11.5px;">
                                                Hapus
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('baak.schedules.destroySeries') }}" onsubmit="return confirm('Hapus seluruh seri jadwal mingguan {{ addslashes($cs->course_name) }} ({{ $cs->class_name ?: '-' }}) di ruang {{ $cs->room->room_code }} pada jam {{ $cs->start_time }}–{{ $cs->end_time }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="room_id" value="{{ $cs->room_id }}">
                                            <input type="hidden" name="course_name" value="{{ $cs->course_name }}">
                                            <input type="hidden" name="class_name" value="{{ $cs->class_name }}">
                                            <input type="hidden" name="start_time" value="{{ $cs->start_time }}">
                                            <input type="hidden" name="end_time" value="{{ $cs->end_time }}">
                                            <button type="submit" class="btn btn-slate" style="padding:4px 8px; font-size:11px; color:#be123c; border-color:#fecdd3;" title="Hapus semua pertemuan berulang untuk kelas & jam ini">
                                                Hapus Seri
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:#64748b; padding:24px;">Belum ada jadwal kuliah pada tanggal <strong>{{ $selectedDate }}</strong>. Pilih tanggal lain di atas atau tambahkan jadwal di sebelah kanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Tambah Jadwal Kuliah</div>
            </div>
            <form method="POST" action="{{ route('baak.schedules.store') }}" class="card-body">
                @csrf
                <div class="form-group">
                    <label class="form-label">Ruangan (Ruang GKT / Laboratorium TI)</label>
                    <select name="room_id" class="form-control" required>
                        <optgroup label="Gedung GKT (Ruang Kelas Lantai 1–4)">
                            @foreach($allRooms->where('building.name', 'GKT') as $gr)
                                <option value="{{ $gr->id }}">{{ $gr->room_code }} — {{ $gr->room_name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Gedung TI (Laboratorium Lantai 1–2)">
                            @foreach($allRooms->where('building.name', 'TI') as $lr)
                                <option value="{{ $lr->id }}">{{ $lr->room_code }} — {{ $lr->room_name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dosen Pengajar</label>
                    <select name="lecturer_id" class="form-control" required>
                        @foreach($lecturers as $lec)
                            <option value="{{ $lec->id }}">{{ $lec->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mata Kuliah</label>
                    <select name="course_name" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Mata Kuliah --</option>
                        @foreach($courseOptions as $groupLabel => $courses)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($courses as $courseItem)
                                    <option value="{{ $courseItem }}">{{ $courseItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kelas</label>
                    <select name="class_name" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Kelas --</option>
                        @foreach($classOptions as $groupLabel => $classes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($classes as $classItem)
                                    <option value="{{ $classItem }}">{{ $classItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- TOMBOL HARI SENIN - MINGGU (TEKAN NYALA -> OTOMATIS SETIAP HARI YANG DIPILIH) -->
                <div class="form-group">
                    <label class="form-label">Pilih Hari Otomatis (Senin – Minggu)</label>
                    <div style="font-size:11.5px; color:#64748b; margin-bottom:6px;">
                        Tekan tombol hari hingga menyala agar jadwal otomatis dibuat rutin setiap hari yang dipilih:
                    </div>
                    @php
                        $dayButtons = [
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu',
                            7 => 'Minggu',
                        ];
                    @endphp
                    <div class="day-selector-grid">
                        @foreach($dayButtons as $dayNum => $dayName)
                            <button type="button"
                                    class="day-toggle-btn"
                                    data-day="{{ $dayNum }}"
                                    data-name="{{ $dayName }}"
                                    onclick="toggleScheduleDay(this)">
                                <span class="day-toggle-dot"></span>
                                <span>{{ $dayName }}</span>
                            </button>
                            <input type="checkbox" name="repeat_days[]" value="{{ $dayNum }}" id="repeat-day-{{ $dayNum }}" class="hidden">
                        @endforeach
                    </div>

                    <div id="repeat-days-panel" class="repeat-days-panel hidden">
                        <div style="font-weight:700; margin-bottom:6px;">
                            Jadwal Otomatis Aktif: <span id="repeat-days-summary">-</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <label for="repeat-weeks-select" style="font-size:11.5px; color:#1e40af; font-weight:600;">Durasi Perkuliahan:</label>
                            <select name="repeat_weeks" id="repeat-weeks-select" class="form-control" style="width:auto; padding:4px 10px; font-size:12px; background:#ffffff;">
                                <option value="4">4 Minggu (1 Bulan)</option>
                                <option value="8">8 Minggu (Setengah Semester)</option>
                                <option value="14">14 Minggu</option>
                                <option value="16" selected>16 Minggu (1 Semester)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" id="schedule-date-label">Tanggal</label>
                    <input type="date" name="schedule_date" id="baak-schedule-date" value="{{ date('Y-m-d') }}" required class="form-control">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;" class="form-group">
                    <div>
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="start_time" value="08:00" min="08:00" max="21:30" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="end_time" value="10:30" min="08:00" max="21:30" required class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-navy" style="width:100%;">Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT JADWAL KULIAH -->
    <div id="edit-schedule-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Edit Jadwal Kuliah</div>
                <button type="button" onclick="closeEditScheduleModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="edit-schedule-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Ruangan (Ruang GKT / Laboratorium TI)</label>
                    <select name="room_id" id="edit-sched-room" class="form-control" required>
                        <optgroup label="Gedung GKT (Ruang Kelas Lantai 1–4)">
                            @foreach($allRooms->where('building.name', 'GKT') as $gr)
                                <option value="{{ $gr->id }}">{{ $gr->room_code }} — {{ $gr->room_name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Gedung TI (Laboratorium Lantai 1–2)">
                            @foreach($allRooms->where('building.name', 'TI') as $lr)
                                <option value="{{ $lr->id }}">{{ $lr->room_code }} — {{ $lr->room_name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dosen Pengajar</label>
                    <select name="lecturer_id" id="edit-sched-lecturer" class="form-control" required>
                        @foreach($lecturers as $lec)
                            <option value="{{ $lec->id }}">{{ $lec->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mata Kuliah</label>
                    <select name="course_name" id="edit-sched-course" class="form-control" required>
                        @foreach($courseOptions as $groupLabel => $courses)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($courses as $courseItem)
                                    <option value="{{ $courseItem }}">{{ $courseItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kelas</label>
                    <select name="class_name" id="edit-sched-class" class="form-control" required>
                        @foreach($classOptions as $groupLabel => $classes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($classes as $classItem)
                                    <option value="{{ $classItem }}">{{ $classItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="schedule_date" id="edit-sched-date" required class="form-control">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;" class="form-group">
                    <div>
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="start_time" id="edit-sched-start" min="08:00" max="21:30" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="end_time" id="edit-sched-end" min="08:00" max="21:30" required class="form-control">
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="button" onclick="closeEditScheduleModal()" class="btn btn-slate">Batal</button>
                    <button type="submit" class="btn btn-navy">Perbarui Jadwal</button>
                </div>
            </form>
        </div>
    </div>

@elseif($tab === 'rooms')
    <div class="grid-main-side">
        <div class="card">
            <div class="card-header" style="flex-wrap:wrap; gap:10px;">
                <div>
                    <div class="card-title">Kelola Data &amp; Status Ruangan GKT &amp; Laboratorium TI</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        Status penggunaan ditampilkan khusus tanggal <strong>{{ $selectedDate }}</strong>
                        @if($isToday) (Hari Ini • {{ $nowTime }} WIB) @endif
                    </div>
                </div>
                <form method="GET" action="{{ route('role.baak') }}" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="tab" value="rooms">
                    <label style="font-size:12px; color:#475569; font-weight:600;">Tanggal:</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:5px 9px; font-size:12px; width:auto;">
                    @if(!$isToday)
                        <a href="{{ route('role.baak', ['tab' => 'rooms']) }}" class="btn btn-slate" style="padding:5px 9px; font-size:11.5px;">Hari Ini</a>
                    @endif
                </form>
            </div>
            <div class="table-wrap">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Ruangan</th>
                            <th>Lokasi</th>
                            <th>Kapasitas</th>
                            <th>Status ({{ $selectedDate }})</th>
                            <th>Aksi BAAK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allRooms as $r)
                            @php
                                $st = $roomStatuses[$r->id];
                                $approvedSlots = $st['approved_slots'];
                            @endphp
                            <tr>
                                <td><span class="badge badge-navy">{{ $r->room_code }}</span></td>
                                <td><strong>{{ $r->room_name }}</strong><br><span style="font-size:11.5px; color:#64748b;">{{ $r->room_type }}</span></td>
                                <td>Gedung {{ $r->building->name }} • Lt. {{ $r->floor }}</td>
                                <td>{{ $r->capacity }} Kursi</td>
                                <td>
                                    <span class="badge {{ $st['badge_class'] }}">
                                        {{ $st['badge_label'] }}
                                    </span>
                                    @if($approvedSlots->isNotEmpty() && $r->status === 'available')
                                        @foreach($approvedSlots as $slot)
                                            <div style="font-size:11px; color:#b45309; margin-top:3px;">
                                                {{ $slot->start_time }}–{{ $slot->end_time }} WIB ({{ $slot->requester->full_name }})
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <button type="button"
                                                onclick="openEditRoomModal({{ $r->id }}, '{{ $r->room_code }}', '{{ addslashes($r->room_name) }}', '{{ addslashes($r->room_type) }}', {{ $r->capacity }}, '{{ $r->status }}')"
                                                class="btn btn-slate" style="padding:3px 7px; font-size:11px;">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('baak.rooms.toggle') }}">
                                            @csrf
                                            <input type="hidden" name="room_id" value="{{ $r->id }}">
                                            <button type="submit" class="btn btn-slate" style="padding:3px 7px; font-size:11px;">
                                                {{ $r->status === 'available' ? 'Set Perbaikan' : 'Set Tersedia' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('baak.rooms.destroy', $r->id) }}" onsubmit="return confirm('Hapus ruangan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-rose" style="padding:3px 7px; font-size:11px;">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Tambah Ruangan Baru</div>
            </div>
            <form method="POST" action="{{ route('baak.rooms.store') }}" class="card-body">
                @csrf
                <div class="form-group">
                    <label class="form-label">Gedung</label>
                    <select name="building_id" class="form-control" required>
                        <option value="1">Gedung GKT (Lantai 1–4)</option>
                        <option value="2">Gedung TI (2 Lantai)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Lantai</label>
                    <select name="floor" class="form-control" required>
                        <option value="1">Lantai 1</option>
                        <option value="2">Lantai 2</option>
                        <option value="3">Lantai 3</option>
                        <option value="4">Lantai 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kode Ruangan</label>
                    <select name="room_code" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Kode Ruangan --</option>
                        @foreach($roomCodeOptions as $groupLabel => $codes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($codes as $codeItem)
                                    <option value="{{ $codeItem }}">{{ $codeItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Ruangan</label>
                    <select name="room_name" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Nama Ruangan --</option>
                        @foreach($roomNameOptions as $groupLabel => $names)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($names as $nameItem)
                                    <option value="{{ $nameItem }}">{{ $nameItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Ruangan</label>
                    <select name="room_type" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Tipe Ruangan --</option>
                        @foreach($roomTypeOptions as $typeItem)
                            <option value="{{ $typeItem }}">{{ $typeItem }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kapasitas (Kursi/PC)</label>
                    <input type="number" name="capacity" min="1" value="40" required class="form-control">
                </div>
                <button type="submit" class="btn btn-navy" style="width:100%;">Tambah Ruangan</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT RUANGAN -->
    <div id="edit-room-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Edit Data Ruangan <span id="edit-room-code-badge" class="badge badge-navy"></span></div>
                <button type="button" onclick="closeEditRoomModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="edit-room-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nama Ruangan</label>
                    <select name="room_name" id="edit-room-name" class="form-control" required>
                        @foreach($roomNameOptions as $groupLabel => $names)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($names as $nameItem)
                                    <option value="{{ $nameItem }}">{{ $nameItem }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Ruangan</label>
                    <select name="room_type" id="edit-room-type" class="form-control" required>
                        @foreach($roomTypeOptions as $typeItem)
                            <option value="{{ $typeItem }}">{{ $typeItem }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kapasitas</label>
                    <input type="number" name="capacity" id="edit-room-capacity" min="1" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status Ruangan</label>
                    <select name="status" id="edit-room-status" class="form-control" required>
                        <option value="available">Tersedia (available)</option>
                        <option value="maintenance">Perbaikan (maintenance)</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="button" onclick="closeEditRoomModal()" class="btn btn-slate">Batal</button>
                    <button type="submit" class="btn btn-navy">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

@elseif($tab === 'calendar')
    @include('partials.calendar')

@else
    <div class="card">
        <div class="card-header">
            <div class="card-title">Daftar Permintaan Peminjaman Ruangan</div>
        </div>
        <div class="card-body">
            @forelse($allReservations as $item)
                <div class="item-box {{ str_starts_with($item->status, 'MENUNGGU') ? 'pending' : '' }}">
                    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:12px;">
                        <div style="flex:1; min-width:260px;">
                            <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px;">
                                <span class="badge badge-navy">{{ $item->room->room_code }}</span>
                                <strong>{{ $item->room->room_name }}</strong>
                                <span class="badge {{ $item->status === 'DISETUJUI' ? 'badge-emerald' : ($item->status === 'DITOLAK' ? 'badge-rose' : 'badge-amber') }}">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                            </div>
                            <div style="font-weight:500; margin-bottom:4px;">{{ $item->purpose }}</div>
                            <div style="font-size:12px; color:#64748b;">
                                Peminjam: {{ $item->requester->full_name }} •
                                {{ $item->reservation_date }} ({{ $item->start_time }}–{{ $item->end_time }}) •
                                Persetujuan: {{ $item->approval?->approver?->full_name ?? $item->approval?->approver_type ?? '-' }}
                            </div>
                            @if(!empty($item->approval?->notes))
                                <div class="reason-box {{ $item->status === 'DITOLAK' ? 'rejected' : 'approved' }}">
                                    <strong>Catatan:</strong> {{ $item->approval->notes }}
                                </div>
                            @endif
                        </div>

                        <div style="display:flex; gap:8px;">
                            @if(str_starts_with($item->status, 'MENUNGGU'))
                                <form method="POST" action="{{ route('reservations.decide') }}">
                                    @csrf
                                    <input type="hidden" name="reservation_id" value="{{ $item->id }}">
                                    <input type="hidden" name="decision" value="DISETUJUI">
                                    <input type="hidden" name="notes" value="Disetujui oleh BAAK.">
                                    <button type="submit" class="btn btn-emerald">Setujui</button>
                                </form>
                                <button type="button"
                                        onclick="openRejectModal({{ $item->id }}, '#RR-{{ $item->id }}', '{{ $item->room->room_name }}')"
                                        class="btn btn-rose">
                                    Tolak
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p style="color:#64748b;">Belum ada permintaan peminjaman ruangan saat ini.</p>
            @endforelse
        </div>
    </div>
@endif

<script>
function openResetStudentPasswordModal(id, name, email, phone) {
    const modal = document.getElementById('reset-student-pw-modal');
    document.getElementById('reset-student-pw-form').action = '/baak/students/' + id + '/password';
    document.getElementById('reset-mhs-name').textContent = name;
    document.getElementById('reset-mhs-email').textContent = email;
    document.getElementById('reset-mhs-phone').textContent = phone || 'Belum tercatat';
    document.getElementById('reset-mhs-verif').value = 'Verifikasi Email & Nomor HP cocok di BAAK: ' + name;
    document.getElementById('reset-mhs-password').value = 'password123';
    modal.classList.add('show');
}
function closeResetStudentPasswordModal() {
    document.getElementById('reset-student-pw-modal').classList.remove('show');
}

function openEditStudentModal(id, name, email, phone, status) {
    const modal = document.getElementById('edit-student-modal');
    document.getElementById('edit-student-form').action = '/baak/students/' + id;
    document.getElementById('edit-student-name').value = name;
    document.getElementById('edit-student-email').value = email;
    document.getElementById('edit-student-phone').value = phone || '';
    document.getElementById('edit-student-status').value = status;
    modal.classList.add('show');
}
function closeEditStudentModal() {
    document.getElementById('edit-student-modal').classList.remove('show');
}

function toggleScheduleDay(btn) {
    const dayNum = btn.getAttribute('data-day');
    const cb = document.getElementById('repeat-day-' + dayNum);
    if (!cb) return;

    cb.checked = !cb.checked;
    btn.classList.toggle('active', cb.checked);

    const activeBtns = Array.from(document.querySelectorAll('.day-toggle-btn.active'));
    const panel = document.getElementById('repeat-days-panel');
    const summary = document.getElementById('repeat-days-summary');
    const dateLabel = document.getElementById('schedule-date-label');

    if (activeBtns.length > 0) {
        const names = activeBtns.map(b => b.getAttribute('data-name')).join(', ');
        if (summary) summary.textContent = 'Setiap ' + names;
        if (panel) panel.classList.remove('hidden');
        if (dateLabel) dateLabel.textContent = 'Mulai Dari Tanggal';
    } else {
        if (panel) panel.classList.add('hidden');
        if (dateLabel) dateLabel.textContent = 'Tanggal';
    }
}

function syncBaakEndTime(startId, endId) {
    const startSel = document.getElementById(startId);
    const endSel = document.getElementById(endId);
    if (!startSel || !endSel) return;
    const startVal = startSel.value;
    let firstValid = null;
    Array.from(endSel.options).forEach(opt => {
        const disabled = opt.value <= startVal;
        opt.disabled = disabled;
        if (!disabled && !firstValid) firstValid = opt.value;
    });
    if (endSel.value <= startVal && firstValid) {
        endSel.value = firstValid;
    }
}

function openEditScheduleModal(id, roomId, lecturerId, course, className, date, start, end) {
    const modal = document.getElementById('edit-schedule-modal');
    document.getElementById('edit-schedule-form').action = '/baak/schedules/' + id;
    document.getElementById('edit-sched-room').value = String(roomId);
    document.getElementById('edit-sched-lecturer').value = String(lecturerId);

    const courseSelect = document.getElementById('edit-sched-course');
    if (course && !Array.from(courseSelect.options).some(opt => opt.value === course)) {
        const customOpt = new Option(course, course, true, true);
        courseSelect.add(customOpt);
    } else {
        courseSelect.value = course;
    }

    const classSelect = document.getElementById('edit-sched-class');
    if (className && !Array.from(classSelect.options).some(opt => opt.value === className)) {
        const customClassOpt = new Option(className, className, true, true);
        classSelect.add(customClassOpt);
    } else if (className) {
        classSelect.value = className;
    }

    document.getElementById('edit-sched-date').value = date;
    document.getElementById('edit-sched-start').value = start;
    document.getElementById('edit-sched-end').value = end;

    modal.classList.add('show');
}
function closeEditScheduleModal() {
    document.getElementById('edit-schedule-modal').classList.remove('show');
}

function openEditRoomModal(id, code, name, type, capacity, status) {
    const modal = document.getElementById('edit-room-modal');
    document.getElementById('edit-room-form').action = '/baak/rooms/' + id;
    document.getElementById('edit-room-code-badge').textContent = code;

    const nameSelect = document.getElementById('edit-room-name');
    if (name && !Array.from(nameSelect.options).some(opt => opt.value === name)) {
        nameSelect.add(new Option(name, name, true, true));
    } else {
        nameSelect.value = name;
    }

    const typeSelect = document.getElementById('edit-room-type');
    if (type && !Array.from(typeSelect.options).some(opt => opt.value === type)) {
        typeSelect.add(new Option(type, type, true, true));
    } else {
        typeSelect.value = type;
    }

    document.getElementById('edit-room-capacity').value = capacity;
    document.getElementById('edit-room-status').value = status;
    modal.classList.add('show');
}
function closeEditRoomModal() {
    document.getElementById('edit-room-modal').classList.remove('show');
}
</script>
@endsection
