<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Costumer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'costumers'; // make sure your migration matches this

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Laravel 10/11+ will auto-hash if you include this
    protected $casts = [
        'password' => 'hashed',
    ];
}
