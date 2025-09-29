<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'image',
        'name',           // From invitationName
        'event_type',     // From eventType
        'product_type',   // From productType
        'theme_style',    // From themeStyle
        'description',    // From description
        'min_order_qty',  // From minOrderQtyCustomization
        'lead_time',      // From leadTime
        'stock_availability', // From stockAvailability
        'type',           // Material type
        'item',           // Material item
        'color',          // Material color
        'size',           // Material size
        'weight',         // Material weight
        'unit_price',     // Material unit price
    ];


    /**
     * Relationship: A product can belong to a template.
     * Assumes you have a Template model and a foreign key 'template_id' in the products table.
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Relationship: A product can have many uploads.
     */
    public function uploads()
    {
        return $this->hasMany(ProductUpload::class);
    }

    /**
     * Relationship: A product can have many materials.
     */
    public function materials()
    {
        return $this->hasMany(ProductMaterial::class);
    }

    // Add any additional methods or relationships as needed
}
