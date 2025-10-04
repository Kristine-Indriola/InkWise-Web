<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBulkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_qty',
        'max_qty',
        'price_per_unit',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
