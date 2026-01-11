<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUpload extends Model
{
    protected $fillable = [
        'product_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'template_id',
        'template_name',
        'description',
        'product_type',
        'event_type',
        'theme_style',
        'front_image',
        'back_image',
        'preview_image',
        'design_data',
        'sizes',
    ];

    protected $casts = [
        'design_data' => 'array',
        'sizes' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
