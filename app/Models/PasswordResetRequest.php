<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'requested_email',
        'requested_phone',
        'requester_notes',
        'target_handler',
        'status',
        'handled_by',
        'handled_notes',
        'requested_at',
        'handled_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
