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

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'requester_id');
    }
}
