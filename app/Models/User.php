<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'avatar',
        'password_hash',
        'role',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Gunakan kolom password_hash sesuai ERD untuk autentikasi Laravel
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->role, $roles, true);
    }

    public function managedLabs()
    {
        return $this->hasMany(LabManager::class, 'user_id');
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'lecturer_id');
    }

    /**
     * Relasi ERD 1: One-to-Many (1:N) — PENGGUNA (1) -> Mengajukan -> (N) PENGAJUAN_PEMINJAMAN
     * Satu pengguna dapat membuat banyak pengajuan peminjaman.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'requester_id');
    }

    /**
     * Relasi ERD 1: One-to-Many (1:N) — PENYETUJU (1) -> Menangani / Dilakukan Oleh -> (N) PERSETUJUAN
     * Satu penyetuju (Dosen, BAAK, atau Kalab) dapat menangani banyak persetujuan.
     */
    public function handledApprovals()
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    /**
     * Relasi ERD 3: Many-to-Many (M:N) — PENGGUNA (M) <-> (N) RUANGAN
     * Diselesaikan secara relasional melalui tabel transaksi PENGAJUAN_PEMINJAMAN (reservations).
     */
    public function borrowedRooms()
    {
        return $this->belongsToMany(Room::class, 'reservations', 'requester_id', 'room_id')
            ->withPivot(['id', 'purpose', 'reservation_date', 'start_time', 'end_time', 'status', 'submitted_at']);
    }

    public function passwordResetRequests()
    {
        return $this->hasMany(PasswordResetRequest::class, 'user_id');
    }

    public function pendingPasswordReset()
    {
        return $this->hasOne(PasswordResetRequest::class, 'user_id')
            ->where('status', 'MENUNGGU')
            ->latest('id');
    }

    /**
     * Sensor sebagian Nomor HP (mis. 0812****3005) agar tidak mudah disalin sembarangan
     */
    public function maskedPhone(): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->phone);
        if ($digits === '') {
            return 'Belum tercatat';
        }
        if (strlen($digits) <= 6) {
            return str_repeat('*', strlen($digits));
        }
        return substr($digits, 0, 4) . str_repeat('*', strlen($digits) - 8) . substr($digits, -4);
    }
}
