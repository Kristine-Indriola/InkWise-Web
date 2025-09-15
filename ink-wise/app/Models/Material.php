<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $primaryKey = 'material_id';
    public $timestamps = false;

    protected $fillable = [
        'sku',
        'material_name',
        'occasion',
        'product_type',
        'material_type',
        'size',
        'color',
        'weight_gsm',
        'volume_ml',
        'unit',
        'unit_cost',
        'stock_qty',
        'reorder_point',
        'description',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'material_id');
    }
}
