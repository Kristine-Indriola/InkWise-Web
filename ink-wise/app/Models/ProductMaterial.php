<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'order_item_id',
        'product_id',
        'material_id',
        'source_type',
        'source_id',
        'item',
        'type',
        'color',
        'unit',
        'weight',
        'qty',
        'quantity_mode',
        'order_quantity',
        'quantity_required',
        'quantity_reserved',
        'quantity_used',
        'reserved_at',
        'deducted_at',
        'metadata',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'order_quantity' => 'integer',
        'quantity_required' => 'decimal:4',
        'quantity_reserved' => 'decimal:4',
        'quantity_used' => 'decimal:4',
        'reserved_at' => 'datetime',
        'deducted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }
}

