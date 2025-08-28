<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

     protected $table = 'inventory';  

    protected $primaryKey = 'inventory_id';

    protected $fillable = [
        'material_id',
        'stock_level',
        'reorder_level',
        'remarks',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
