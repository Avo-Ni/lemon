<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password',
        'subscribed'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'subscribed' => 'boolean'
    ];
}