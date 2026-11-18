<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'approver_id',
        'approver_type',
        'decision',
        'notes',
        'decided_at',
    ];

    /**
     * Relasi ERD 4: One-to-One (1:1) — PERSETUJUAN (0..1) -> Milik -> (1) PENGAJUAN_PEMINJAMAN
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    /**
     * Relasi ERD 2: Many-to-One (N:1) — PERSETUJUAN (N) -> Dilakukan Oleh -> (1) PENYETUJU (Dosen/BAAK/Kalab)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
