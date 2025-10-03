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
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
