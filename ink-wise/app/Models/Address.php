<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $primaryKey = 'address_id';

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'street',
        'barangay',
        'city',
        'province',
        'postal_code',
        'country',
    ];

    // Relationship to User
    public function user()
    {
        
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
