<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialArchive extends Model
{
    use HasFactory;

    protected $table = 'archived_materials';

    protected $fillable = [
        'original_material_id',
        'material_name',
        'material_type',
        'unit',
        'unit_cost',
        'stock_level',
        'reorder_level',
        'remarks',
        'archived_by',
        'metadata',
        'archived_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'archived_at' => 'datetime',
    ];
}
