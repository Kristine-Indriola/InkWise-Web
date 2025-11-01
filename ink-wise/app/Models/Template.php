<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_type',
        'product_type',
        'theme_style',
        'description',
        'front_image',
        'back_image',
        'status',
        'metadata',
        'svg_path',
        'back_svg_path',
        'design',
        'preview',
        'processed_at',
        'figma_file_key',
        'figma_node_id',
        'figma_url',
        'figma_metadata',
        'figma_synced_at',
        'has_back_design',
    ];

    protected $casts = [
        'metadata' => 'array',
        'figma_metadata' => 'array',
        'figma_synced_at' => 'datetime',
        'processed_at' => 'datetime',
        'has_back_design' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'template_id');
    }
}