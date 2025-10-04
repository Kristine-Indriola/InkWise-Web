<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemPaperStock extends Model
{
    use HasFactory;

    protected $table = 'order_item_paper_stock';

    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function paperStock(): BelongsTo
    {
        return $this->belongsTo(ProductPaperStock::class, 'paper_stock_id');
    }
}
