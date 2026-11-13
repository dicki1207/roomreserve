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

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
