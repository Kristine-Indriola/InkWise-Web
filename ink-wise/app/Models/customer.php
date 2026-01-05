<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'customers'; // ✅ matches migration

    // Primary key
    protected $primaryKey = 'customer_id'; // ✅ matches migration
    public $incrementing = true;
    protected $keyType = 'int';

    // Fillable fields
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'address_id',
        'user_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id')
            ->orderByDesc('updated_at');
    }

    // Accessors
    public function getNameAttribute()
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->middle_name ?? '') . ' ' . ($this->last_name ?? ''));
        return $name ?: 'Guest';
    }
}
