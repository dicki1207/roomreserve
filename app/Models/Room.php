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

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'room_id');
    }
}
