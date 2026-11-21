@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
@php
    $unitLabels = [
        'mahasiswa' => 'Mahasiswa (Komting Kelas / Organisasi)',
        'dosen'     => 'Dosen Pengampu — Jurusan Teknik Informatika',
        'kalab'     => 'Kepala Laboratorium — Jurusan Teknik Informatika',
        'baak'      => 'Bagian Administrasi Akademik & Kemahasiswaan (BAAK)',
        'admin'     => 'Administrator Sistem (Database Seeded)',
    ];
    $unitLabel = $unitLabels[$profileUser->role] ?? ucfirst($profileUser->role);
@endphp

<div class="grid-main-side">
    <!-- KIRI: IDENTITAS RESMI & FOTO PROFIL -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Profil Pengguna</div>
                <span class="badge {{ $profileUser->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                    {{ $profileUser->status === 'active' ? 'Akun Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="card-body">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px; padding-bottom:20px; border-bottom:1px solid #e2e8f0; margin-bottom:20px;">
                    @if(!empty($profileUser->avatar))
                        <img src="{{ $profileUser->avatar }}" alt="{{ $profileUser->full_name }}"
                             style="width:82px; height:82px; border-radius:16px; object-fit:cover; border:2px solid #cbd5e1;">
                    @else
                        <div style="width:82px; height:82px; border-radius:16px; background:linear-gradient(135deg, #1e293b 0%, #1d4ed8 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:700; flex-shrink:0;">
                            {{ strtoupper(substr($profileUser->full_name, 0, 2)) }}
                        </div>
                    @endif

                    <div style="flex:1; min-width:220px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:2px;">{{ $profileUser->full_name }}</div>
                        <div style="font-size:13px; color:#475569; margin-bottom:10px;">{{ $unitLabel }}</div>

                        <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            @csrf
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required class="form-control" style="max-width:250px; padding:5px 8px; font-size:12px;">
                            <button type="submit" class="btn btn-navy" style="padding:6px 12px; font-size:12px;">Simpan Foto Profil</button>
                        </form>
                        <div style="font-size:11px; color:#64748b; margin-top:4px;">Format JPG, PNG, atau WebP • Maksimal 2 MB.</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" value="{{ $profileUser->full_name }}" readonly class="form-control" style="background:#f1f5f9; color:#334155;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Email Akun</label>
                        <input type="text" value="{{ $profileUser->email }}" readonly class="form-control" style="background:#f1f5f9; color:#334155;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nomor HP Terdaftar</label>
                        <input type="text" value="{{ $profileUser->phone ?: 'Belum tercatat' }}" readonly class="form-control" style="background:#f1f5f9; color:#1e40af; font-weight:600;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Jabatan / Status Unit</label>
                        <input type="text" value="{{ $unitLabel }}" readonly class="form-control" style="background:#f1f5f9; color:#334155;">
                    </div>
                </div>

                <div style="margin-top:16px; padding:11px 13px; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; font-size:12px; color:#1e3a8a; line-height:1.55;">
                    @if($profileUser->role === 'mahasiswa')
                        <strong>Keamanan Data Akun:</strong> Nama, Email, dan Nomor HP Terdaftar dikunci dan hanya dapat diubah oleh petugas <strong>BAAK</strong> sebagai kunci verifikasi akun &amp; layanan lupa kata sandi.
                    @elseif($profileUser->role === 'admin')
                        <strong>Akun Administrator Database:</strong> Identitas utama Administrator dikelola langsung di database sistem.
                    @else
                        <strong>Keamanan Data Akun:</strong> Nama, Email, dan Nomor HP Terdaftar dikelola oleh <strong>Administrator Sistem</strong> sebagai dasar verifikasi akun &amp; layanan lupa kata sandi.
                    @endif
                </div>
            </div>
        </div>

        @if($profileUser->role === 'kalab')
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Laboratorium yang Dikelola ({{ $myManagedLabs->count() }})</div>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                        @forelse($myManagedLabs as $lab)
                            <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span class="badge badge-navy">{{ $lab->room_code }}</span>
                                    <span style="font-size:11.5px; color:#64748b;">Lt. {{ $lab->floor }} • {{ $lab->capacity }} PC</span>
                                </div>
                                <div style="font-weight:600; color:#0f172a;">{{ $lab->room_name }}</div>
                            </div>
                        @empty
                            <p style="color:#64748b; font-size:12.5px;">Belum ada penugasan laboratorium.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="card-title">Ringkasan Aktivitas Akun</div>
            </div>
            <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
                @if(in_array($profileUser->role, ['mahasiswa', 'dosen']))
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Total Pengajuan Peminjaman</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myReservationCount }}</div>
                    </div>
                @endif
                @if($profileUser->role === 'dosen')
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Jadwal Kuliah Terdaftar</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myTeachingCount }}</div>
                    </div>
                @endif
                @if(in_array($profileUser->role, ['dosen', 'kalab', 'baak']))
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Persetujuan Diproses</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myApprovalCount }}</div>
                    </div>
                @endif
                <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                    <div style="font-size:11.5px; color:#64748b;">Tanggal Terdaftar</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:4px;">{{ $profileUser->created_at?->format('d M Y') ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN: UBAH KATA SANDI SENDIRI -->
    <div class="card" style="height:fit-content;">
        <div class="card-header">
            <div class="card-title">Ubah Kata Sandi</div>
        </div>
        <form method="POST" action="{{ route('profile.password') }}" class="card-body">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required placeholder="Masukkan kata sandi lama" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Kata Sandi Baru</label>
                <input type="password" name="new_password" required minlength="4" placeholder="Minimal 4 karakter" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="new_password_confirmation" required minlength="4" placeholder="Ulangi kata sandi baru" class="form-control">
            </div>
            <button type="submit" class="btn btn-navy" style="width:100%;">Simpan Kata Sandi Baru</button>
        </form>
    </div>
</div>
@endsection
