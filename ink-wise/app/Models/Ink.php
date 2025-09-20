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
        'cost_per_ml',
        'avg_usage_per_invite_ml',
        'cost_per_invite',
        'description',
    ];
}
