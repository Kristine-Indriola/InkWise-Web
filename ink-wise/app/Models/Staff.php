<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false; // ❌ stop auto-increment
    protected $keyType = 'int';   // staff_id is an integer

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'role',
        'status',
    ];

      // Scope to exclude archived staff
    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', 'archived');
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

    public function approval()
{
    return $this->hasOne(UserApproval::class);
}

    // 🔹 Auto-generate random staff_id when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($staff) {
            if (empty($staff->staff_id)) {
                do {
                    $randomId = random_int(1000, 9999); // 🎲 4-digit random ID
                } while (self::where('staff_id', $randomId)->exists());

                $staff->staff_id = $randomId;
            }
        });
    }
}
