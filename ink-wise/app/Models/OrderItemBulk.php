<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemBulk extends Model
{
    use HasFactory;

    protected $table = 'order_item_bulk';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function productBulkOrder(): BelongsTo
    {
        return $this->belongsTo(ProductBulkOrder::class, 'product_bulk_order_id');
    }
}
