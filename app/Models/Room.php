<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'building_id',
        'floor',
        'room_code',
        'room_name',
        'room_type',
        'capacity',
        'status',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function labManager()
    {
        return $this->hasOne(LabManager::class, 'room_id');
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'room_id');
    }

    /**
     * Relasi ERD 1: One-to-Many (1:N) — RUANGAN (1) -> Untuk -> (N) PENGAJUAN_PEMINJAMAN
     * Satu ruangan dapat digunakan dalam banyak pengajuan peminjaman.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'room_id');
    }

    /**
     * Relasi ERD 3: Many-to-Many (M:N) — RUANGAN (N) <-> (M) PENGGUNA
     * Diselesaikan secara relasional melalui tabel transaksi PENGAJUAN_PEMINJAMAN (reservations).
     */
    public function borrowers()
    {
        return $this->belongsToMany(User::class, 'reservations', 'room_id', 'requester_id')
            ->withPivot(['id', 'purpose', 'reservation_date', 'start_time', 'end_time', 'status', 'submitted_at']);
    }
}
