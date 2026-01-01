<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ink extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_name',
        'occasion',
        'product_type',
        'ink_color',
        'material_type',
        'stock_qty_ml',
        'stock_qty',
        'unit',
        'size',
        'cost_per_ml',
        'cost_per_invite',
        'description',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'ink_id');
    }
}
