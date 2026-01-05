<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerFinalized extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_order_items';

    protected $guarded = ['id'];

    protected $casts = [
        'design' => 'array',
        'preview_images' => 'array',
        'paper_stock' => 'array',
        'estimated_date' => 'date',
        'total_price' => 'decimal:2',
        'pre_order_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}