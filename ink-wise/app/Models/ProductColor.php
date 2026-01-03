<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    use HasFactory;

    protected $table = 'product_ink_usage';

    protected $fillable = [
        'product_id',
        'average_usage_ml',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
