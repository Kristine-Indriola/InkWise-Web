<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'verified_at',
    ];

    // Relation to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
