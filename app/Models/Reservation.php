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

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approval()
    {
        return $this->hasOne(Approval::class, 'reservation_id');
    }
}
