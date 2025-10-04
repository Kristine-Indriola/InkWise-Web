<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'addon_type',
        'name',
        'price',
        'image_path',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
