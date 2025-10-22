<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUpload extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'design_data' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
