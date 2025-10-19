<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemAddon extends Model
{
    use HasFactory;

    protected $table = 'order_item_addons';

    protected $guarded = [];

    protected $casts = [
        'addon_price' => 'float',
        'quantity' => 'integer',
        'pricing_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function productAddon(): BelongsTo
    {
        return $this->belongsTo(ProductAddon::class, 'addon_id');
    }
}
