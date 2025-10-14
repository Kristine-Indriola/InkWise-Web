<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductEnvelope extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'material_id',
        'envelope_material_name',
        'max_qty',
        'max_quantity',
        'price_per_unit',
        'envelope_image',
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