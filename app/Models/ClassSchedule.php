<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'lecturer_id',
        'course_name',
        'schedule_date',
        'start_time',
        'end_time',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
