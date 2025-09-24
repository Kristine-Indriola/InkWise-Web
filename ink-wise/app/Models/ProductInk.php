<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInk extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ink_id',
        'item',
        'type',
        'usage',
        'cost_per_ml',
        'total_cost',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ink()
    {
        return $this->belongsTo(Ink::class);
    }
}
