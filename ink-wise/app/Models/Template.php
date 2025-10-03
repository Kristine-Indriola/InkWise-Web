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
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'template_id');
    }
}

