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

    public static function boot()
    {
        parent::boot();

        static::saving(function ($inventory) {
            if ($inventory->stock_level <= 0) {
                $inventory->remarks = 'Out of Stock';
            } elseif ($inventory->stock_level <= $inventory->reorder_level) {
                $inventory->remarks = 'Low Stock';
            } else {
                $inventory->remarks = 'In Stock';
            }
        });
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
