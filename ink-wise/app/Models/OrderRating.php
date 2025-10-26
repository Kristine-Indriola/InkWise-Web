<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'submitted_by',
        'rating',
        'review',
        'photos',
        'metadata',
        'submitted_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by', 'user_id');
    }
}
