<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = ['name', 'total_floors'];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'building_id');
    }
}
