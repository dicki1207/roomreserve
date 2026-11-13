<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabManager extends Model
{
    public $timestamps = false;

    protected $fillable = ['room_id', 'user_id', 'assigned_at'];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function kalab()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
