<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'user_id',
        'photo',
        'phone',
        'birthdate',
        'gender',
    ];

    // 🔹 Customer belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // 🔹 Customer has one Address
    public function address()
    {
        return $this->hasOne(Address::class, 'user_id', 'user_id');
    }
}
