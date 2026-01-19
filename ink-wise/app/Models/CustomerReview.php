<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_reviews';

    protected $guarded = ['id'];

    protected $casts = [
        'rating' => 'integer',
        'design_json' => 'array',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
