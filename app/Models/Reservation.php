<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'requester_id',
        'purpose',
        'reservation_date',
        'start_time',
        'end_time',
        'status',
        'submitted_at',
    ];

    /**
     * Relasi ERD 2: Many-to-One (N:1) — PENGAJUAN_PEMINJAMAN (N) -> Untuk -> (1) RUANGAN
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Relasi ERD 2: Many-to-One (N:1) — PENGAJUAN_PEMINJAMAN (N) -> Diajukan Oleh -> (1) PENGGUNA
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Relasi ERD 4: One-to-One (1 : 0..1) — PENGAJUAN_PEMINJAMAN (1) -> Memiliki -> (0..1) PERSETUJUAN
     * Satu pengajuan memiliki maksimal satu persetujuan akhir.
     */
    public function approval()
    {
        return $this->hasOne(Approval::class, 'reservation_id');
    }
}
