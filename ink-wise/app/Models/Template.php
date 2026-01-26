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
        'fold_type',
        'sizes',
        'metadata',
        'svg_path',
        'back_svg_path',
        'preview',
        'preview_front',
        'preview_back',
        'has_back_design',
        'design',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sizes' => 'array',
        'has_back_design' => 'boolean',
        'figma_metadata' => 'array',
        'processed_at' => 'datetime',
        'figma_synced_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'template_id');
    }
}