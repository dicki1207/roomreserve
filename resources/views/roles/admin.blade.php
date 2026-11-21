@extends('layouts.app')

@section('title', 'Dashboard Administrator')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Akun Dosen, Kalab & BAAK</div>
            <div class="stat-value">{{ $staffUsers->count() }}</div>
        </div>
        <div class="stat-icon">👤</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Total Kelas GKT & Lab TI</div>
            <div class="stat-value">{{ $allRooms->count() }}</div>
        </div>
        <div class="stat-icon">🏢</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Ruangan Tersedia</div>
            <div class="stat-value">{{ $allRooms->where('status', 'available')->count() }}</div>
        </div>
        <div class="stat-icon">✓</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Ruangan Perbaikan</div>
            <div class="stat-value">{{ $allRooms->where('status', '!=', 'available')->count() }}</div>
        </div>
        <div class="stat-icon">!</div>
    </div>
</div>

<div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('role.admin', ['tab' => 'dashboard']) }}" class="btn {{ $tab === 'dashboard' ? 'btn-navy' : 'btn-slate' }}">
        Kelola Akun (Dosen, Kalab, BAAK)
    </a>
    <a href="{{ route('role.admin', ['tab' => 'rooms']) }}" class="btn {{ $tab === 'rooms' ? 'btn-navy' : 'btn-slate' }}">
        Cek Status Kelas GKT & Lab TI ({{ $allRooms->count() }})
    </a>
    <a href="{{ route('role.admin', ['tab' => 'calendar']) }}" class="btn {{ $tab === 'calendar' ? 'btn-navy' : 'btn-slate' }}">
        Kalender Ruangan
    </a>
</div>

@if($tab === 'calendar')
    @include('partials.calendar')

@elseif($tab === 'rooms')
    <!-- VIEW-ONLY: CEK SEMUA KELAS GKT & LABORATORIUM TI -->
    <div class="card">
        <div class="card-header" style="flex-wrap:wrap; gap:12px;">
            <div>
                <div class="card-title">Status Seluruh Ruang Kelas GKT &amp; Laboratorium Gedung TI (Hanya Lihat / View Only)</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Status khusus tanggal <strong>{{ $selectedDate }}</strong>
                    @if($isToday) (Hari Ini • {{ $nowTime }} WIB) @endif
                    — Perubahan status ruangan dikelola oleh BAAK.
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" action="{{ route('role.admin') }}" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="tab" value="rooms">
                    <label style="font-size:12px; color:#475569; font-weight:600;">Tanggal:</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:5px 9px; font-size:12px; width:auto;">
                    @if(!$isToday)
                        <a href="{{ route('role.admin', ['tab' => 'rooms']) }}" class="btn btn-slate" style="padding:5px 9px; font-size:11.5px;">Hari Ini</a>
                    @endif
                </form>
                <button type="button" onclick="filterRooms('ALL')" id="filter-ALL" class="btn btn-navy">Semua ({{ $allRooms->count() }})</button>
                <button type="button" onclick="filterRooms('GKT')" id="filter-GKT" class="btn btn-slate">Kelas GKT ({{ $allRooms->where('building.name', 'GKT')->count() }})</button>
                <button type="button" onclick="filterRooms('TI')" id="filter-TI" class="btn btn-slate">Lab Gedung TI ({{ $allRooms->where('building.name', 'TI')->count() }})</button>
            </div>
        </div>
        <div class="table-wrap">
            <table class="rr-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Kelas / Laboratorium</th>
                        <th>Gedung &amp; Lantai</th>
                        <th>Kapasitas</th>
                        <th>Penanggung Jawab</th>
                        <th>Jadwal pada {{ $selectedDate }}</th>
                        <th>Status Ruangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allRooms as $r)
                        @php
                            $st = $roomStatuses[$r->id];
                            $approvedSlots = $st['approved_slots'];
                            $classSlots = $st['class_slots'];
                        @endphp
                        <tr class="room-card" data-building="{{ $r->building->name }}">
                            <td><span class="badge badge-navy">{{ $r->room_code }}</span></td>
                            <td>
                                <strong>{{ $r->room_name }}</strong>
                                <div style="font-size:11.5px; color:#64748b;">{{ $r->room_type }}</div>
                            </td>
                            <td>Gedung {{ $r->building->name }} • Lantai {{ $r->floor }}</td>
                            <td>{{ $r->capacity }} Kursi/PC</td>
                            <td>
                                @if($r->building->name === 'TI')
                                    Kalab: <strong>{{ $r->labManager?->kalab?->full_name ?? '-' }}</strong>
                                @else
                                    Dosen Pengajar / BAAK
                                @endif
                            </td>
                            <td>
                                @if($approvedSlots->isNotEmpty() || $classSlots->isNotEmpty())
                                    <div style="display:flex; flex-direction:column; gap:3px; font-size:12px;">
                                        @foreach($approvedSlots as $slot)
                                            <span style="color:#b45309; font-weight:600;">• Dipinjam {{ $slot->start_time }}–{{ $slot->end_time }} WIB</span>
                                        @endforeach
                                        @foreach($classSlots as $cs)
                                            <span style="color:#1e40af; font-weight:600;">• Kuliah {{ $cs->start_time }}–{{ $cs->end_time }} WIB</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:#64748b; font-size:12px;">Kosong pada tanggal ini</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $st['badge_class'] }}">
                                    {{ $st['badge_label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@else
    <div class="grid-main-side">
        <div style="display:flex; flex-direction:column; gap:18px;">
            <!-- TABEL AKUN DOSEN, KALAB, DAN BAAK -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Daftar Akun Dosen, Kepala Laboratorium, & BAAK ({{ $staffUsers->count() }})</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Akun Mahasiswa dikelola oleh <strong>BAAK</strong>. Akun Administrator dibuat langsung di <strong>Database</strong>.
                        </div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Email &amp; Nomor HP Terdaftar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staffUsers as $u)
                                <tr>
                                    <td><strong>{{ $u->full_name }}</strong></td>
                                    <td>
                                        <div>{{ $u->email }}</div>
                                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">HP: <strong>{{ $u->phone ?: 'Belum diisi' }}</strong></div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $u->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                                            {{ $u->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <button type="button"
                                                    onclick="openResetStaffPasswordModal({{ $u->id }}, '{{ addslashes($u->full_name) }}', '{{ $u->email }}', '{{ addslashes($u->phone ?? '') }}')"
                                                    class="btn btn-navy" style="padding:3px 8px; font-size:11px;">
                                                Perbarui Password (WA)
                                            </button>
                                            <button type="button"
                                                    onclick="openEditUserModal({{ $u->id }}, '{{ addslashes($u->full_name) }}', '{{ $u->email }}', '{{ addslashes($u->phone ?? '') }}', '{{ $u->role }}', '{{ $u->status }}')"
                                                    class="btn btn-slate" style="padding:3px 8px; font-size:11px;">
                                                Edit Akun
                                            </button>
                                            <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" onsubmit="return confirm('Hapus akun {{ $u->full_name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-rose" style="padding:3px 8px; font-size:11px;">
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

            <!-- RINGKASAN CEK STATUS KELAS & LAB (VIEW ONLY) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Ringkasan Status Kelas GKT & Laboratorium TI (View Only)</div>
                    <a href="{{ route('role.admin', ['tab' => 'rooms']) }}" class="btn btn-slate" style="font-size:12px; padding:5px 10px;">
                        Lihat Detail 17 Ruangan →
                    </a>
                </div>
                <div class="table-wrap">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Kelas / Laboratorium</th>
                                <th>Lokasi</th>
                                <th>Status Ketersediaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allRooms as $r)
                                @php $st = $roomStatuses[$r->id]; @endphp
                                <tr>
                                    <td><span class="badge badge-navy">{{ $r->room_code }}</span></td>
                                    <td><strong>{{ $r->room_name }}</strong></td>
                                    <td>Gedung {{ $r->building->name }} • Lt. {{ $r->floor }}</td>
                                    <td>
                                        <span class="badge {{ $st['badge_class'] }}">
                                            {{ $st['badge_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:18px;">
            <!-- FORM TAMBAH AKUN (KHUSUS DOSEN, KALAB, BAAK) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Buat Akun Baru (Dosen / Kalab / BAAK)</div>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="card-body">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" required placeholder="Nama Dosen / Kalab / BAAK" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" required placeholder="nama@roomreserve.ac.id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor HP Terdaftar</label>
                        <input type="text" name="phone" required placeholder="Contoh: 081234567890" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kata Sandi</label>
                        <input type="text" name="password" value="password123" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="dosen">Dosen Pengampu</option>
                            <option value="kalab">Kepala Laboratorium (Kalab)</option>
                            <option value="baak">BAAK</option>
                        </select>
                    </div>
                    <div style="font-size:11.5px; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; border-radius:7px; padding:9px 11px; margin-bottom:12px;">
                        • Akun <strong>Administrator</strong> dibuat langsung di database.<br>
                        • Akun <strong>Mahasiswa</strong> dibuat &amp; dikelola oleh <strong>BAAK</strong>.
                    </div>
                    <button type="submit" class="btn btn-navy" style="width:100%;">Buat Akun</button>
                </form>
            </div>

            <!-- INFO AKUN ADMINISTRATOR DATABASE -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Akun Administrator (Database)</div>
                </div>
                <div class="card-body">
                    @foreach($adminUsers as $adm)
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                            <div>
                                <div style="font-weight:600; color:#0f172a;">{{ $adm->full_name }}</div>
                                <div style="font-size:12px; color:#64748b;">{{ $adm->email }}</div>
                            </div>
                            <span class="badge badge-amber">Database Seeded</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- RIWAYAT AKTIVITAS SISTEM -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Riwayat Aktivitas Sistem</div>
                </div>
                <div class="card-body" style="max-height:300px; overflow-y:auto;">
                    @foreach($auditLogs as $lg)
                        <div style="padding:9px 11px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px; font-size:12px; background:#f8fafc;">
                            <div style="display:flex; justify-content:space-between; font-weight:600; color:#0f172a;">
                                <span>{{ $lg->action }}</span>
                                <span style="font-size:11px; color:#64748b;">{{ $lg->created_at }}</span>
                            </div>
                            <div style="color:#64748b; margin-top:2px;">
                                {{ $lg->user?->full_name ?? 'Sistem' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT PENGGUNA (KHUSUS DOSEN, KALAB, BAAK) -->
    <div id="edit-user-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Edit Akun (Dosen / Kalab / BAAK)</div>
                <button type="button" onclick="closeEditUserModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="edit-user-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="full_name" id="edit-user-name" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit-user-email" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor HP Terdaftar</label>
                    <input type="text" name="phone" id="edit-user-phone" placeholder="0812xxxxxxxx" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Reset Kata Sandi (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Minimal 4 karakter" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" id="edit-user-role" class="form-control" required>
                        <option value="dosen">Dosen Pengampu</option>
                        <option value="kalab">Kepala Laboratorium</option>
                        <option value="baak">BAAK</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit-user-status" class="form-control" required>
                        <option value="active">Aktif (active)</option>
                        <option value="inactive">Nonaktif (inactive)</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="button" onclick="closeEditUserModal()" class="btn btn-slate">Batal</button>
                    <button type="submit" class="btn btn-navy">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PERBARUI PASSWORD DOSEN / KALAB / BAAK (KONFIRMASI VIA WHATSAPP) -->
    <div id="reset-staff-pw-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Perbarui Password Akun (Konfirmasi WhatsApp)</div>
                <button type="button" onclick="closeResetStaffPasswordModal()" style="color:#64748b; font-size:18px;">✕</button>
            </div>
            <form id="reset-staff-pw-form" method="POST" action="" class="card-body">
                @csrf
                @method('PUT')
                <div style="padding:11px 13px; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; font-size:12.5px; color:#1e293b; margin-bottom:14px;">
                    <div><strong>Nama:</strong> <span id="reset-staff-name"></span></div>
                    <div><strong>Email:</strong> <span id="reset-staff-email"></span></div>
                    <div><strong>Nomor HP Terdaftar:</strong> <span id="reset-staff-phone-display" style="color:#1d4ed8; font-weight:700;"></span></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="text" name="password" id="reset-staff-password" value="password123" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp Penerima (Terisi Otomatis dari Nomor HP Terdaftar)</label>
                    <input type="text" id="reset-staff-phone" placeholder="Contoh: 081234567890" class="form-control">
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0; flex-wrap:wrap;">
                    <button type="button" onclick="sendStaffResetWhatsApp()" class="btn btn-emerald">
                        Kirim Info Perbaikan via WA
                    </button>
                    <div style="display:flex; gap:8px;">
                        <button type="button" onclick="closeResetStaffPasswordModal()" class="btn btn-slate">Batal</button>
                        <button type="submit" class="btn btn-navy">Simpan Password Baru</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openResetStaffPasswordModal(id, name, email, phone) {
        const modal = document.getElementById('reset-staff-pw-modal');
        document.getElementById('reset-staff-pw-form').action = '/admin/users/' + id + '/password';
        document.getElementById('reset-staff-name').textContent = name;
        document.getElementById('reset-staff-email').textContent = email;
        document.getElementById('reset-staff-phone-display').textContent = phone || 'Belum tercatat';
        document.getElementById('reset-staff-phone').value = phone || '';
        document.getElementById('reset-staff-password').value = 'password123';
        modal.classList.add('show');
    }
    function closeResetStaffPasswordModal() {
        document.getElementById('reset-staff-pw-modal').classList.remove('show');
    }
    function sendStaffResetWhatsApp() {
        const name = document.getElementById('reset-staff-name').textContent;
        const email = document.getElementById('reset-staff-email').textContent;
        const pass = document.getElementById('reset-staff-password').value;
        let phone = document.getElementById('reset-staff-phone').value.replace(/[^0-9]/g, '');
        if (phone.startsWith('0')) phone = '62' + phone.substring(1);
        const msg = 'Halo ' + name + ', berikut informasi perbaikan kata sandi akun RoomReserve Anda:\n- Email: ' + email + '\n- Password Baru: *' + pass + '*\nSilakan masuk kembali ke sistem.';
        const url = (phone ? 'https://wa.me/' + phone : 'https://wa.me/') + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank');
    }

    function openEditUserModal(id, name, email, phone, role, status) {
        const modal = document.getElementById('edit-user-modal');
        document.getElementById('edit-user-form').action = '/admin/users/' + id;
        document.getElementById('edit-user-name').value = name;
        document.getElementById('edit-user-email').value = email;
        document.getElementById('edit-user-phone').value = phone || '';
        document.getElementById('edit-user-role').value = role;
        document.getElementById('edit-user-status').value = status;
        modal.classList.add('show');
    }
    function closeEditUserModal() {
        document.getElementById('edit-user-modal').classList.remove('show');
    }
    </script>
@endif
@endsection
