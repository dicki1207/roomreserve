@extends('layouts.app')

@section('title', 'Dashboard Administrator')

@section('content')
@if($tab === 'calendar')
    @include('partials.calendar')
@else
    <div class="grid-main-side">
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Akun Pengguna ({{ $allUsers->count() }})</div>
                    <form method="POST" action="{{ route('admin.seeder.reset') }}" onsubmit="return confirm('Reset database ke kondisi awal?')">
                        @csrf
                        <button type="submit" class="btn btn-slate" style="font-size:12px; padding:6px 12px;">Reset Data Awal</button>
                    </form>
                </div>
                <div class="table-wrap">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allUsers as $u)
                                @php
                                    $bMap = [
                                        'mahasiswa' => 'badge-royal',
                                        'dosen'     => 'badge-navy',
                                        'kalab'     => 'badge-cyan',
                                        'baak'      => 'badge-emerald',
                                        'admin'     => 'badge-amber',
                                    ];
                                @endphp
                                <tr>
                                    <td><strong>{{ $u->full_name }}</strong></td>
                                    <td>{{ $u->email }}</td>
                                    <td><span class="badge {{ $bMap[$u->role] ?? 'badge-royal' }}">{{ strtoupper($u->role) }}</span></td>
                                    <td>
                                        <span class="badge {{ $u->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                                            {{ $u->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <button type="button"
                                                    onclick="openEditUserModal({{ $u->id }}, '{{ addslashes($u->full_name) }}', '{{ $u->email }}', '{{ $u->role }}', '{{ $u->status }}')"
                                                    class="btn btn-slate" style="padding:3px 7px; font-size:11px;">
                                                Edit
                                            </button>
                                            @if((int)auth()->id() !== (int)$u->id)
                                                <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" onsubmit="return confirm('Hapus akun {{ $u->full_name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-rose" style="padding:3px 7px; font-size:11px;">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
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
                    <div class="card-title">Penugasan Kepala Laboratorium Gedung TI</div>
                </div>
                <div class="table-wrap">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Laboratorium</th>
                                <th>Lantai</th>
                                <th>Kepala Laboratorium</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tiLabs as $lab)
                                <tr>
                                    <td><span class="badge badge-navy">{{ $lab->room_code }}</span></td>
                                    <td><strong>{{ $lab->room_name }}</strong></td>
                                    <td>Lantai {{ $lab->floor }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.kalab.assign') }}" style="display:flex; gap:6px;">
                                            @csrf
                                            <input type="hidden" name="room_id" value="{{ $lab->id }}">
                                            <select name="kalab_id" class="form-control" style="padding:5px 8px; font-size:12.5px;">
                                                @foreach($kalabUsers as $ku)
                                                    <option value="{{ $ku->id }}" {{ (int)($lab->labManager?->user_id) === (int)$ku->id ? 'selected' : '' }}>
                                                        {{ $ku->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-navy" style="padding:5px 12px; font-size:12px;">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Tambah Akun Pengguna</div>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="card-body">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" required placeholder="Nama lengkap pengguna" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" required placeholder="nama@roomreserve.ac.id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kata Sandi</label>
                        <input type="text" name="password" value="password123" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="mahasiswa">Mahasiswa / Organisasi</option>
                            <option value="dosen">Dosen Pengampu</option>
                            <option value="kalab">Kepala Laboratorium</option>
                            <option value="baak">BAAK</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-navy" style="width:100%;">Simpan Akun</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Riwayat Aktivitas Sistem</div>
                </div>
                <div class="card-body" style="max-height:380px; overflow-y:auto;">
                    @foreach($auditLogs as $lg)
                        <div style="padding:10px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px; font-size:12px; background:#f8fafc;">
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

    <!-- MODAL EDIT PENGGUNA -->
    <div id="edit-user-modal" class="modal-backdrop">
        <div class="modal-card">
            <div class="card-header">
                <div class="card-title">Edit Akun Pengguna</div>
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
                    <label class="form-label">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Minimal 4 karakter" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" id="edit-user-role" class="form-control" required>
                        <option value="mahasiswa">Mahasiswa / Organisasi</option>
                        <option value="dosen">Dosen Pengampu</option>
                        <option value="kalab">Kepala Laboratorium</option>
                        <option value="baak">BAAK</option>
                        <option value="admin">Administrator</option>
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

    <script>
    function openEditUserModal(id, name, email, role, status) {
        const modal = document.getElementById('edit-user-modal');
        document.getElementById('edit-user-form').action = '/admin/users/' + id;
        document.getElementById('edit-user-name').value = name;
        document.getElementById('edit-user-email').value = email;
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
