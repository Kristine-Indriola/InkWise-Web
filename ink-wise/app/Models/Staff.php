<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false; // 🔑 not auto-increment
    protected $keyType = 'int';   // 🔑 integer type

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'role',
        'status',
    ];

    // 🔥 Auto-generate random 4-digit staff_id
    protected static function booted()
    {
        static::creating(function ($staff) {
            if (!$staff->staff_id) {
                do {
                    $randomId = rand(1000, 9999); // generate 4-digit random
                } while (self::where('staff_id', $randomId)->exists()); // ensure uniqueness

                $staff->staff_id = $randomId;
            }
        });
    }

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'user_id', 'user_id');
    }
}
