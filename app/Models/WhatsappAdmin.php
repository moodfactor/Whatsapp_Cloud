<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class WhatsappAdmin extends Authenticatable
{
    use Notifiable;

    protected $table = 'whatsapp_admins';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'permissions', 'status', 'last_login', 'created_by',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
        'last_login' => 'datetime',
    ];
}
