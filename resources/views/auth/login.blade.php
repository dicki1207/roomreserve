@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="login-split-screen">
    <!-- SISI KIRI (55% LAYAR): FOTO KAMPUS FULL-BLEED + LOGO DI AREA LANGIT ATAS -->
    <div class="login-left-stage">
        <div class="login-brand-hero">
            <div class="login-brand-lockup">
                <img src="{{ asset('images/logo-icon.png') }}?v=2" alt="RoomReserve Logo" style="width:46px; height:46px; object-fit:contain; flex-shrink:0;">
                <img src="{{ asset('images/logo-wordmark.png') }}?v=2" alt="RoomReserve" style="height:27px; width:auto; display:block;">
            </div>
            <p class="login-brand-tagline">
                Sistem Peminjaman Ruang GKT &amp; Laboratorium TI
            </p>
        </div>
    </div>

    <!-- SISI KANAN (45% LAYAR): FORM LOGIN BERSIH & TERFOKUS -->
    <div class="login-right-panel">
        <div class="login-form-wrap">
            <!-- Logo khusus tampilan mobile -->
            <div class="login-mobile-brand">
                <img src="{{ asset('images/logo-icon.png') }}?v=2" alt="RoomReserve Logo" style="width:40px; height:40px; object-fit:contain;">
                <img src="{{ asset('images/logo-wordmark.png') }}?v=2" alt="RoomReserve" style="height:24px; width:auto;">
            </div>

            <h1 class="login-heading">Masuk</h1>
            <p class="login-subheading">Silakan masukkan email dan kata sandi akun Anda.</p>

            @if(session('error'))
                <div class="reason-box rejected" style="margin-top:0; margin-bottom:18px; font-weight:500;">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="reason-box approved" style="margin-top:0; margin-bottom:18px; font-weight:500;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="login-form">
                @csrf
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="margin-bottom:6px;">Email</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </span>
                        <input type="email" name="email" id="login-email" required value="{{ old('email', 'mahasiswa@roomreserve.ac.id') }}" placeholder="nama@roomreserve.ac.id" class="login-input">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="form-label" style="margin-bottom:0;">Kata Sandi</label>
                        <button type="button" onclick="openForgotModal()" style="font-size:12px; font-weight:600; color:#0d47a1;">
                            Lupa Kata Sandi?
                        </button>
                    </div>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" name="password" id="login-password" required value="password123" placeholder="Masukkan kata sandi" class="login-input">
                        <button type="button" onclick="toggleLoginPassword()" class="password-toggle-btn" aria-label="Tampilkan kata sandi" title="Tampilkan / Sembunyikan Kata Sandi">
                            <svg id="eye-icon-show" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eye-icon-hide" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" x2="22" y1="2" y2="22"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login-primary">
                    Masuk
                </button>
            </form>

            <!-- Akses Cepat Akun Demo (Ringkas & Dapat Dibuka-Tutup) -->
            <div class="demo-drawer-wrap">
                <button type="button" onclick="toggleDemoDrawer()" class="demo-toggle-link" id="demo-toggle-btn">
                    <span>Akses Cepat Akun Demo</span>
                    <span id="demo-arrow">▾</span>
                </button>
                <div class="demo-drawer-box" id="demo-drawer-box">
                    <select onchange="if(this.value) quickFillAndLogin(this.value, 'password123')" class="form-control" style="font-size:12.5px;">
                        <option value="">— Pilih Akun —</option>
                        @foreach($seededAccounts as $acc)
                            <option value="{{ $acc->email }}">
                                {{ $acc->full_name }} ({{ $acc->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="login-footer-note">
            &copy; 2026 RoomReserve • Jurusan Teknik Informatika
        </div>
    </div>
</div>

<!-- MODAL ALUR LUPA KATA SANDI (VERIFIKASI EMAIL & NOMOR HP TERDAFTAR) -->
<div id="forgot-modal" class="modal-backdrop">
    <div class="modal-card" style="max-width:560px;">
        <div class="card-header">
            <div class="card-title">Layanan Lupa Kata Sandi (Perbaikan Akun)</div>
            <button type="button" onclick="closeForgotModal()" style="color:#64748b; font-size:18px;">✕</button>
        </div>

        <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
            <!-- JALUR 1: MAHASISWA (KOMTING KELAS) & ORGANISASI -> MELALUI BAAK -->
            <div style="padding:14px 16px; border-radius:10px; border:1px solid #bfdbfe; background:#eff6ff;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <strong style="color:#1e40af; font-size:13.5px;">1. Mahasiswa (Komting Kelas) &amp; Organisasi</strong>
                    <span class="badge badge-navy">Melalui BAAK</span>
                </div>
                <p style="font-size:12.5px; color:#1e293b; line-height:1.55; margin-bottom:8px;">
                    Bagi <strong>Mahasiswa (Komting Kelas)</strong> atau pengurus <strong>Organisasi Mahasiswa</strong> yang lupa kata sandi, silakan datang menemui petugas <strong>BAAK</strong> dengan menyertakan:
                </p>
                <ul style="font-size:12px; color:#334155; padding-left:18px; line-height:1.6;">
                    <li><strong>Email Akun</strong> &amp; <strong>Nomor HP Terdaftar di BAAK</strong> (wajib sesuai dengan nomor HP yang tercatat di BAAK)</li>
                    <li><strong>Nama Lengkap</strong> &amp; <strong>Komting Kelas berapa</strong> (contoh: Komting TI-5A) atau <strong>Nama Organisasi</strong></li>
                </ul>
                <div style="margin-top:8px; font-size:11.5px; color:#1d4ed8; font-weight:600;">
                    → Petugas BAAK akan mencocokkan Email serta Nomor HP terdaftar sebelum memperbarui kata sandi akun Anda.
                </div>
            </div>

            <!-- JALUR 2: DOSEN PENGAMPU, KALAB, & BAAK -> HUBUNGI ADMIN VIA WHATSAPP -->
            <div style="padding:14px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <strong style="color:#0f172a; font-size:13.5px;">2. Dosen Pengampu, Kalab, &amp; BAAK</strong>
                    <span class="badge badge-emerald">Via WhatsApp Admin</span>
                </div>
                <p style="font-size:12.5px; color:#334155; line-height:1.55; margin-bottom:10px;">
                    Masukkan <strong>Email Akun</strong> dan <strong>Nomor HP Terdaftar</strong> untuk menghubungi <strong>Administrator Sistem via WhatsApp</strong>. Admin akan memverifikasi kecocokan email dan nomor HP Anda sebelum memperbarui kata sandi.
                </p>

                <div class="form-row-2" style="margin-bottom:8px;">
                    <div>
                        <label class="form-label" style="font-size:11.5px;">Email Akun</label>
                        <input type="email" id="wa-email" placeholder="nama@roomreserve.ac.id" class="form-control" style="padding:8px 10px; font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11.5px;">Nomor HP Terdaftar</label>
                        <input type="text" id="wa-phone" placeholder="0812xxxxxxxx" class="form-control" style="padding:8px 10px; font-size:13px;">
                    </div>
                </div>

                <button type="button" onclick="openAdminWhatsApp()" class="btn btn-emerald" style="width:100%; padding:9px 12px;">
                    Buka WhatsApp Administrator
                </button>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="button" onclick="closeForgotModal()" class="btn btn-slate">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLoginPassword() {
    const input = document.getElementById('login-password');
    const iconShow = document.getElementById('eye-icon-show');
    const iconHide = document.getElementById('eye-icon-hide');
    if (input.type === 'password') {
        input.type = 'text';
        iconShow.style.display = 'none';
        iconHide.style.display = 'block';
    } else {
        input.type = 'password';
        iconShow.style.display = 'block';
        iconHide.style.display = 'none';
    }
}

function toggleDemoDrawer() {
    const box = document.getElementById('demo-drawer-box');
    const arrow = document.getElementById('demo-arrow');
    const isOpen = box.classList.toggle('open');
    arrow.textContent = isOpen ? '▴' : '▾';
}

function quickFillAndLogin(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-password').value = pass;
    document.getElementById('login-form').submit();
}

function openForgotModal() {
    document.getElementById('forgot-modal').classList.add('show');
}

function closeForgotModal() {
    document.getElementById('forgot-modal').classList.remove('show');
}

function openAdminWhatsApp() {
    const email = document.getElementById('wa-email').value.trim() || '(Email Akun)';
    const phone = document.getElementById('wa-phone').value.trim() || '(Nomor HP Terdaftar)';
    const message = 'Halo Administrator RoomReserve, saya mengajukan permohonan reset kata sandi untuk akun dengan Email: *' + email + '* dan Nomor HP Terdaftar: *' + phone + '*. Mohon bantuan verifikasi dan perbaikan kata sandi akun saya. Terima kasih.';
    const waUrl = 'https://wa.me/6281234567890?text=' + encodeURIComponent(message);
    window.open(waUrl, '_blank');
}
</script>
@endsection
