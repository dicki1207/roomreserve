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

<!-- HEADER PROFIL RINGKAS & BEBAS OVERFLOW -->
<div class="card">
    <div class="card-body" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:18px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; min-width:0; flex:1;">
            @if(!empty($profileUser->avatar))
                <img src="{{ $profileUser->avatar }}" alt="{{ $profileUser->full_name }}"
                     style="width:76px; height:76px; border-radius:16px; object-fit:cover; border:2px solid #cbd5e1; flex-shrink:0;">
            @else
                <div style="width:76px; height:76px; border-radius:16px; background:linear-gradient(135deg, #0d47a1 0%, #1d4ed8 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; flex-shrink:0;">
                    {{ strtoupper(substr($profileUser->full_name, 0, 2)) }}
                </div>
            @endif

            <div style="min-width:0; flex:1;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                    <h2 style="font-size:19px; font-weight:700; color:#0f172a; margin:0;">{{ $profileUser->full_name }}</h2>
                    <span class="badge {{ $profileUser->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                        {{ $profileUser->status === 'active' ? 'Akun Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div style="font-size:13px; color:#475569;">{{ $unitLabel }}</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $profileUser->email }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatar-upload-form" style="display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0;">
            @csrf
            <input type="file" name="avatar" id="avatar-file-input" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="document.getElementById('avatar-upload-form').submit();">
            <button type="button" onclick="document.getElementById('avatar-file-input').click();" class="btn btn-slate" style="padding:7px 14px; font-size:12.5px;">
                Ganti Foto Profil
            </button>
            <span style="font-size:11px; color:#64748b;">JPG / PNG / WebP • Maks. 2 MB</span>
        </form>
    </div>
</div>

<!-- GRID 2 KOLOM SEIMBANG (MIN-WIDTH:0 MENCEGAH OVERFLOW KE KANAN) -->
<div class="grid-main-side" style="align-items:start;">
    <!-- KOLOM KIRI: IDENTITAS RESMI & RINGKASAN AKTIVITAS -->
    <div style="display:flex; flex-direction:column; gap:18px; min-width:0;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Identitas Terverifikasi</div>
                <span class="badge badge-navy">Data Permanen</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:12.5px; color:#64748b;">Nama Lengkap</span>
                    <strong style="font-size:13px; color:#0f172a; text-align:right;">{{ $profileUser->full_name }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:12.5px; color:#64748b;">Email Akun</span>
                    <strong style="font-size:13px; color:#0f172a; word-break:break-all; text-align:right;">{{ $profileUser->email }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:12.5px; color:#64748b;">Nomor HP Terdaftar</span>
                    <strong style="font-size:13px; color:#0d47a1; text-align:right;">{{ $profileUser->phone ?: 'Belum tercatat' }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:12.5px; color:#64748b;">Jabatan / Unit</span>
                    <strong style="font-size:13px; color:#0f172a; text-align:right;">{{ $unitLabel }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <span style="font-size:12.5px; color:#64748b;">Tanggal Terdaftar</span>
                    <strong style="font-size:13px; color:#0f172a;">{{ $profileUser->created_at?->format('d M Y') ?? '-' }}</strong>
                </div>

                <div style="margin-top:6px; padding:10px 12px; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; font-size:12px; color:#1e3a8a; line-height:1.5;">
                    <strong>Keamanan Identitas:</strong> Email dan Nomor HP Terdaftar dikunci permanen sebagai kunci verifikasi akun &amp; pengajuan perbaikan kata sandi.
                </div>
            </div>
        </div>

        @if($profileUser->role === 'kalab')
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Laboratorium yang Dikelola ({{ $myManagedLabs->count() }})</div>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:8px;">
                    @forelse($myManagedLabs as $lab)
                        <div style="padding:11px 12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                            <div>
                                <div style="font-weight:600; color:#0f172a;">{{ $lab->room_name }}</div>
                                <div style="font-size:11.5px; color:#64748b;">Lantai {{ $lab->floor }} • {{ $lab->capacity }} PC</div>
                            </div>
                            <span class="badge badge-navy">{{ $lab->room_code }}</span>
                        </div>
                    @empty
                        <p style="color:#64748b; font-size:12.5px;">Belum ada penugasan laboratorium.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="card-title">Ringkasan Aktivitas Akun</div>
            </div>
            <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px;">
                @if(in_array($profileUser->role, ['mahasiswa', 'dosen']))
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Pengajuan Pinjam</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myReservationCount }}</div>
                    </div>
                @endif
                @if($profileUser->role === 'dosen')
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Jadwal Kuliah</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myTeachingCount }}</div>
                    </div>
                @endif
                @if(in_array($profileUser->role, ['dosen', 'kalab', 'baak']))
                    <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11.5px; color:#64748b;">Persetujuan Diproses</div>
                        <div style="font-size:20px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $myApprovalCount }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: PERMOHONAN PERBAIKAN KATA SANDI (SESUAI ALUR RESMI ADMIN / BAAK) -->
    <div class="card" style="min-width:0;">
        <div class="card-header">
            <div class="card-title">Permohonan Perbaikan Kata Sandi</div>
            <span class="badge {{ $profileUser->role === 'mahasiswa' ? 'badge-navy' : 'badge-emerald' }}">
                {{ $profileUser->role === 'mahasiswa' ? 'Melalui BAAK' : ($profileUser->role === 'admin' ? 'Database' : 'Melalui Admin (WA)') }}
            </span>
        </div>
        <div class="card-body">
            @if(session('wa_reset_url'))
                <div class="reason-box approved" style="margin-top:0; margin-bottom:14px;">
                    <strong>Tiket {{ session('wa_ticket_code') }} Aktif!</strong> Lanjutkan konfirmasi ke Administrator melalui tombol berikut:
                    <div style="margin-top:8px;">
                        <a href="{{ session('wa_reset_url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-emerald" style="width:100%; padding:8px 12px; font-size:12.5px;">
                            Buka WhatsApp Administrator ({{ session('wa_ticket_code') }})
                        </a>
                    </div>
                </div>
            @endif

            @if($profileUser->role === 'admin')
                <div style="padding:12px 14px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px; color:#475569; line-height:1.55;">
                    Akun <strong>Administrator Sistem</strong> dibuat dan dikelola langsung di tingkat database demi menjaga integritas dan keamanan tertinggi sistem.
                </div>
            @elseif($activeResetTicket)
                <div style="padding:14px; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px;">
                        <strong style="color:#92400e; font-size:13.5px;">Permintaan Sedang Menunggu</strong>
                        <span class="badge badge-amber">#PR-{{ $activeResetTicket->id }}</span>
                    </div>
                    <div style="font-size:12.5px; color:#78350f; line-height:1.5;">
                        Tiket permohonan perubahan kata sandi Anda telah dikirim ke <strong>{{ $activeResetTicket->target_handler === 'BAAK' ? 'BAAK' : 'Administrator Sistem' }}</strong> pada <strong>{{ $activeResetTicket->requested_at }}</strong>.
                    </div>
                    @if($activeResetTicket->target_handler === 'ADMIN')
                        @php
                            $waMsg = "Halo Administrator RoomReserve, saya mengonfirmasi tiket perubahan kata sandi *#PR-{$activeResetTicket->id}*.\n- Email: {$profileUser->email}\n- Nomor HP Terdaftar: {$profileUser->phone}\nTerima kasih.";
                            $waDirectUrl = 'https://wa.me/6281234567890?text=' . rawurlencode($waMsg);
                        @endphp
                        <a href="{{ $waDirectUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-emerald" style="width:100%; margin-top:10px; padding:8px 12px; font-size:12.5px;">
                            Hubungi WhatsApp Administrator (#PR-{{ $activeResetTicket->id }})
                        </a>
                    @else
                        <div style="margin-top:8px; font-size:12px; font-weight:600; color:#92400e;">
                            → Silakan temui petugas BAAK untuk konfirmasi dan menerima kata sandi baru Anda.
                        </div>
                    @endif
                </div>
            @else
                <p style="font-size:12.5px; color:#475569; line-height:1.55; margin-bottom:14px;">
                    @if($profileUser->role === 'mahasiswa')
                        Demi keamanan akun, perubahan kata sandi <strong>Mahasiswa / Organisasi</strong> dilakukan melalui petugas <strong>BAAK</strong>. Konfirmasi Nomor HP terdaftar Anda di bawah ini untuk mengirim tiket permohonan ke BAAK.
                    @else
                        Demi keamanan akun, perubahan kata sandi <strong>Dosen, Kalab, &amp; BAAK</strong> dilakukan melalui <strong>Administrator Sistem</strong> (konfirmasi WhatsApp). Masukkan Nomor HP terdaftar Anda di bawah ini untuk mengajukan tiket resmi.
                    @endif
                </p>

                <form method="POST" action="{{ route('profile.password.request') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Nomor HP Terdaftar</label>
                        <input type="text" name="phone" required placeholder="Masukkan Nomor HP Anda (08xxxxxxxxxx)" class="form-control">
                    </div>

                    @if($profileUser->role === 'mahasiswa')
                        <div class="form-group">
                            <label class="form-label">Komting Kelas / Nama Organisasi</label>
                            <input type="text" name="requester_notes" required placeholder="Contoh: Komting TI-5A atau Ketua HMTI" class="form-control">
                        </div>
                    @endif

                    <button type="submit" class="btn {{ $profileUser->role === 'mahasiswa' ? 'btn-navy' : 'btn-emerald' }}" style="width:100%; padding:10px;">
                        {{ $profileUser->role === 'mahasiswa' ? 'Ajukan Permohonan Ubah Sandi ke BAAK' : 'Ajukan Permohonan & Hubungi Admin (WA)' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
