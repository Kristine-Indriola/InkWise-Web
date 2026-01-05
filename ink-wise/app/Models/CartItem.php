<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    // Use production table name
    protected $table = 'customer_cart_items';

    protected $fillable = [
        'cart_id',
        'session_id',
        'customer_id',
        'product_type',
        'product_id',
        'quantity',
        'paper_type_id',
        'paper_price',
        'unit_price',
        'total_price',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paper_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
