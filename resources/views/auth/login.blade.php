@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="login-page">
    <div class="login-box">
        <!-- Header Navy Lembut -->
        <div class="login-box-header">
            <div class="brand-icon" style="width:40px; height:40px; font-size:15px;">RR</div>
            <div>
                <div style="font-size:17px; font-weight:700; color:#ffffff; letter-spacing:-0.01em;">RoomReserve</div>
                <div style="font-size:12px; color:#cbd5e1;">Sistem Peminjaman Ruang GKT & Laboratorium TI</div>
            </div>
        </div>

        <!-- Form Login -->
        <div class="login-box-body">
            @if(session('error'))
                <div class="reason-box rejected" style="margin-top:0; margin-bottom:16px; font-weight:500;">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="reason-box approved" style="margin-top:0; margin-bottom:16px; font-weight:500;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="login-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="login-email" required value="{{ old('email', 'mahasiswa@roomreserve.ac.id') }}" placeholder="nama@roomreserve.ac.id" class="form-control">
                </div>
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label class="form-label" style="margin-bottom:0;">Kata Sandi</label>
                        <button type="button" onclick="openForgotModal()" style="font-size:12px; font-weight:600; color:#1d4ed8;">
                            Lupa Kata Sandi?
                        </button>
                    </div>
                    <input type="password" name="password" id="login-password" required value="password123" class="form-control">
                </div>
                <button type="submit" class="btn btn-navy" style="width:100%; padding:10px; margin-top:4px;">
                    Masuk
                </button>
            </form>

            <!-- Pilihan Akun Cepat yang Rapi & Tanpa Scrollbar -->
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <label class="form-label" style="color:#64748b; font-weight:500; margin-bottom:6px;">
                    Pilih Akun Demo (Otomatis Masuk):
                </label>
                <select onchange="if(this.value) quickFillAndLogin(this.value, 'password123')" class="form-control" style="font-size:12.5px;">
                    <option value="">— Pilih Akun Berdasarkan Role —</option>
                    @foreach($seededAccounts as $acc)
                        <option value="{{ $acc->email }}">
                            [{{ strtoupper($acc->role) }}] {{ $acc->full_name }} ({{ $acc->email }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ALUR LUPA KATA SANDI (BAAK UNTUK MAHASISWA/ORGANISASI & WA ADMIN UNTUK DOSEN/KALAB/BAAK) -->
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
                    Bagi <strong>Mahasiswa (Komting Kelas)</strong> atau pengurus <strong>Organisasi Mahasiswa</strong> yang lupa kata sandi, silakan datang langsung menemui petugas <strong>BAAK</strong> dengan menyampaikan:
                </p>
                <ul style="font-size:12px; color:#334155; padding-left:18px; line-height:1.6;">
                    <li><strong>Email / Username Akun</strong> yang ingin diperbarui</li>
                    <li><strong>Nama Lengkap</strong> &amp; <strong>Komting Kelas berapa</strong> (contoh: Komting TI-5A)</li>
                    <li>Atau <strong>Nama Organisasi &amp; Penanggung Jawab</strong> (contoh: Ketua HMTI / BEM)</li>
                </ul>
                <div style="margin-top:8px; font-size:11.5px; color:#1d4ed8; font-weight:600;">
                    → Petugas BAAK akan memverifikasi data dan langsung memperbarui password akun Anda di sistem.
                </div>
            </div>

            <!-- JALUR 2: DOSEN PENGAMPU, KALAB, & BAAK -> HUBUNGI ADMIN VIA WHATSAPP -->
            <div style="padding:14px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <strong style="color:#0f172a; font-size:13.5px;">2. Dosen Pengampu, Kalab, &amp; BAAK</strong>
                    <span class="badge badge-emerald">Via WhatsApp Admin</span>
                </div>
                <p style="font-size:12.5px; color:#334155; line-height:1.55; margin-bottom:10px;">
                    Untuk <strong>Dosen Pengampu</strong>, <strong>Kepala Laboratorium (Kalab)</strong>, atau <strong>BAAK</strong>, silakan hubungi <strong>Administrator Sistem via WhatsApp</strong>. Admin akan memperbarui akun dan menginformasikan password baru sesuai role Anda.
                </p>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <div>
                        <label class="form-label" style="font-size:11.5px;">Role Anda</label>
                        <select id="wa-role" class="form-control" style="padding:6px 10px; font-size:12px;">
                            <option value="Dosen Pengampu">Dosen Pengampu</option>
                            <option value="Kepala Laboratorium (Kalab)">Kepala Laboratorium (Kalab)</option>
                            <option value="BAAK">BAAK</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11.5px;">Nama &amp; Email Akun</label>
                        <input type="text" id="wa-identity" placeholder="Nama / email@roomreserve.ac.id" class="form-control" style="padding:6px 10px; font-size:12px;">
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
    const role = document.getElementById('wa-role').value;
    const identity = document.getElementById('wa-identity').value.trim() || '(Nama / Email Akun)';
    const message = 'Halo Administrator RoomReserve, saya dari role *' + role + '* (' + identity + ') mengalami lupa password dan mohon bantuan perbaikan kata sandi akun saya. Terima kasih.';
    const waUrl = 'https://wa.me/6281234567890?text=' + encodeURIComponent(message);
    window.open(waUrl, '_blank');
}
</script>
@endsection
