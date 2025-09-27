<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id'; // ✅ tell Laravel our PK
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships
    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id', 'user_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'user_id');
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'user_id', 'user_id');
    }

   public function verification()
{
    return $this->hasOne(UserVerification::class, 'user_id');
}

}
