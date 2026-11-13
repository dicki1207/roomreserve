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
                    <label class="form-label">Kata Sandi</label>
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

<script>
function quickFillAndLogin(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-password').value = pass;
    document.getElementById('login-form').submit();
}
</script>
@endsection
