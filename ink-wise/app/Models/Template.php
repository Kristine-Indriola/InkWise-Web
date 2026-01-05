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
        'status_note',
        'status_updated_at',
        'metadata',
        'design',
        'svg_path',
        'back_svg_path',
        'preview',
        'preview_front',
        'preview_back',
        'has_back_design',
        'processed_at',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'design' => 'array',
        'processed_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'has_back_design' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'template_id');
    }
}