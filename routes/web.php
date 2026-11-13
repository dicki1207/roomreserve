<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomReserveController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke halaman login atau ke dashboard role yang sedang aktif
Route::get('/', function () {
    AuthController::ensureSeeded();
    if (Auth::check()) {
        return redirect()->route('role.' . Auth::user()->role);
    }
    return redirect()->route('login');
});

// Route Autentikasi
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
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

// 4. Role: BAAK (CRUD Jadwal Kuliah & Ruangan)
Route::middleware(['auth', 'role:baak'])->prefix('baak')->group(function () {
    Route::get('/', [RoomReserveController::class, 'baakDashboard'])->name('role.baak');

    // CRUD Jadwal Kuliah GKT
    Route::post('/schedules', [RoomReserveController::class, 'storeSchedule'])->name('baak.schedules.store');
    Route::put('/schedules/{id}', [RoomReserveController::class, 'updateSchedule'])->name('baak.schedules.update');
    Route::delete('/schedules/{id}', [RoomReserveController::class, 'destroySchedule'])->name('baak.schedules.destroy');

    // CRUD Master Ruangan
    Route::post('/rooms', [RoomReserveController::class, 'storeRoom'])->name('baak.rooms.store');
    Route::put('/rooms/{id}', [RoomReserveController::class, 'updateRoom'])->name('baak.rooms.update');
    Route::delete('/rooms/{id}', [RoomReserveController::class, 'destroyRoom'])->name('baak.rooms.destroy');
    Route::post('/rooms/toggle', [RoomReserveController::class, 'toggleRoomStatus'])->name('baak.rooms.toggle');
});

// 5. Role: Admin Sistem (CRUD Pengguna & Lab Assignment)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [RoomReserveController::class, 'adminDashboard'])->name('role.admin');

    // CRUD Users
    Route::post('/users', [RoomReserveController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{id}', [RoomReserveController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [RoomReserveController::class, 'destroyUser'])->name('admin.users.destroy');

    Route::post('/kalab/assign', [RoomReserveController::class, 'assignKalab'])->name('admin.kalab.assign');
    Route::post('/seeder/reset', [RoomReserveController::class, 'resetSeeder'])->name('admin.seeder.reset');
});

// Keputusan Persetujuan / Penolakan (Dosen, Kalab, BAAK)
Route::middleware(['auth', 'role:dosen,kalab,baak'])
    ->post('/reservations/decide', [RoomReserveController::class, 'decideReservation'])
    ->name('reservations.decide');
