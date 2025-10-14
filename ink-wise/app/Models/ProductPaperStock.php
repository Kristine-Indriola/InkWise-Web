<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPaperStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'material_id',
        'name',
        'price',
        'image_path',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }
}
