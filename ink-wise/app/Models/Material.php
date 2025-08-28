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
        'material_name',
        'material_type',
        'unit',
        'unit_cost',
        'date_added',
        'date_updated',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'material_id');
    }
}
