<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomReserveController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke halaman login atau ke dashboard role yang sedang aktif
Route::get('/', function () {
    AuthController::ensureSeeded();
    if (Auth::check()) {
        return redirect()->route('role.'.Auth::user()->role);
    }

    return redirect()->route('login');
});

// Route Autentikasi & Permintaan Reset Kata Sandi (Lupa Password Terverifikasi)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/forgot-password/request', [AuthController::class, 'requestPasswordReset'])->name('password.request.submit');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================================
// ROUTE RBAC TERPISAH UNTUK MASING-MASING ROLE (5 ROLE)
// =========================================================================

// 1. Role: Mahasiswa / Organisasi (CRUD Peminjaman)
Route::middleware(['auth', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {
    Route::get('/', [RoomReserveController::class, 'mahasiswaDashboard'])->name('role.mahasiswa');
    Route::post('/reservations', [RoomReserveController::class, 'storeReservation'])->name('mahasiswa.reservations.store');
    Route::put('/reservations/{id}', [RoomReserveController::class, 'updateReservation'])->name('mahasiswa.reservations.update');
    Route::delete('/reservations/{id}', [RoomReserveController::class, 'destroyReservation'])->name('mahasiswa.reservations.destroy');
});

// Aksi Pengajuan Peminjaman Ruangan (Mahasiswa & Dosen)
Route::middleware(['auth', 'role:mahasiswa,dosen'])->group(function () {
    Route::post('/reservations', [RoomReserveController::class, 'storeReservation'])->name('reservations.store');
    Route::put('/reservations/{id}', [RoomReserveController::class, 'updateReservation'])->name('reservations.update');
    Route::delete('/reservations/{id}', [RoomReserveController::class, 'destroyReservation'])->name('reservations.destroy');
});

// 2. Role: Dosen Pengampu (GKT)
Route::middleware(['auth', 'role:dosen'])->prefix('dosen')->group(function () {
    Route::get('/', [RoomReserveController::class, 'dosenDashboard'])->name('role.dosen');
});

// 3. Role: Kepala Laboratorium / Kalab (9 Lab Gedung TI)
Route::middleware(['auth', 'role:kalab'])->prefix('kalab')->group(function () {
    Route::get('/', [RoomReserveController::class, 'kalabDashboard'])->name('role.kalab');
});

// 4. Role: BAAK (CRUD Jadwal Kuliah, Master Ruangan, & Akun Mahasiswa)
Route::middleware(['auth', 'role:baak'])->prefix('baak')->group(function () {
    Route::get('/', [RoomReserveController::class, 'baakDashboard'])->name('role.baak');

    // CRUD Jadwal Kuliah GKT
    Route::post('/schedules', [RoomReserveController::class, 'storeSchedule'])->name('baak.schedules.store');
    Route::put('/schedules/{id}', [RoomReserveController::class, 'updateSchedule'])->name('baak.schedules.update');
    Route::delete('/schedules/series', [RoomReserveController::class, 'destroyScheduleSeries'])->name('baak.schedules.destroySeries');
    Route::delete('/schedules/{id}', [RoomReserveController::class, 'destroySchedule'])->name('baak.schedules.destroy');

    // CRUD Master Ruangan & Laboratorium
    Route::post('/rooms', [RoomReserveController::class, 'storeRoom'])->name('baak.rooms.store');
    Route::put('/rooms/{id}', [RoomReserveController::class, 'updateRoom'])->name('baak.rooms.update');
    Route::delete('/rooms/{id}', [RoomReserveController::class, 'destroyRoom'])->name('baak.rooms.destroy');
    Route::post('/rooms/toggle', [RoomReserveController::class, 'toggleRoomStatus'])->name('baak.rooms.toggle');
    Route::post('/kalab/assign', [RoomReserveController::class, 'assignKalab'])->name('baak.kalab.assign');

    // CRUD Akun Mahasiswa (Dihandle oleh BAAK)
    Route::post('/students', [RoomReserveController::class, 'storeMahasiswa'])->name('baak.students.store');
    Route::put('/students/{id}', [RoomReserveController::class, 'updateMahasiswa'])->name('baak.students.update');
    Route::put('/students/{id}/password', [RoomReserveController::class, 'resetMahasiswaPassword'])->name('baak.students.password');
    Route::delete('/students/{id}/password-request', [RoomReserveController::class, 'rejectMahasiswaPasswordReset'])->name('baak.students.password.reject');
    Route::delete('/students/{id}', [RoomReserveController::class, 'destroyMahasiswa'])->name('baak.students.destroy');
});

// 5. Role: Admin Sistem (Hanya Membuat Akun Dosen/Kalab/BAAK & View-Only Status Kelas/Lab)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [RoomReserveController::class, 'adminDashboard'])->name('role.admin');

    // CRUD Akun Khusus Dosen, Kalab, dan BAAK (Admin dibuat di DB, Mahasiswa dihandle BAAK)
    Route::post('/users', [RoomReserveController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{id}', [RoomReserveController::class, 'updateUser'])->name('admin.users.update');
    Route::put('/users/{id}/password', [RoomReserveController::class, 'resetStaffPassword'])->name('admin.users.password');
    Route::delete('/users/{id}/password-request', [RoomReserveController::class, 'rejectStaffPasswordReset'])->name('admin.users.password.reject');
    Route::delete('/users/{id}', [RoomReserveController::class, 'destroyUser'])->name('admin.users.destroy');

    Route::post('/seeder/reset', [RoomReserveController::class, 'resetSeeder'])->name('admin.seeder.reset');
});

// Keputusan Persetujuan / Penolakan (Dosen, Kalab, BAAK)
Route::middleware(['auth', 'role:dosen,kalab,baak'])
    ->post('/reservations/decide', [RoomReserveController::class, 'decideReservation'])
    ->name('reservations.decide');

// Profil Akun Saya (Tersedia untuk Dosen, Kalab, BAAK, Mahasiswa, & Admin)
Route::middleware(['auth'])->prefix('profile')->group(function () {
    Route::get('/', [RoomReserveController::class, 'showProfile'])->name('profile.show');
    Route::post('/avatar', [RoomReserveController::class, 'updateProfileAvatar'])->name('profile.avatar');
    Route::post('/password-request', [RoomReserveController::class, 'requestOwnPasswordReset'])->name('profile.password.request');
});

// Notifikasi Pengguna (Mahasiswa, Dosen, & Seluruh Role)
Route::middleware(['auth'])->prefix('notifications')->group(function () {
    Route::post('/read-all', [RoomReserveController::class, 'markNotificationsRead'])->name('notifications.readAll');
    Route::delete('/all', [RoomReserveController::class, 'destroyAllNotifications'])->name('notifications.destroyAll');
    Route::delete('/{id}', [RoomReserveController::class, 'destroyNotification'])->name('notifications.destroy');
});
