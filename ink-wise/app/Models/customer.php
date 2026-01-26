<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function orders()\r\n    {\r\n        return \->hasMany(CustomerOrder::class, 'customer_id', 'customer_id');\r\n    }\r\n}\r\n
