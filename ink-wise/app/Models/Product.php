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
        'name',           // Example: Product name (e.g., "Wedding Invitation")
        'event_type',
        'product_type',
        'theme_style',
        'description',    // Example: Product description
        'color_options',
        'envelope_options',
        'min_order_qty',
        'bulk_pricing',
        'lead_time',
        'stock_availability',
        'total_raw_cost',
        'quantity_ordered',
        'cost_per_invite',
        'markup',
        'selling_price',
        'total_selling_price',
        'status',
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
     * Relationship: A product can have many materials.
     * Assumes you have a Material model and a pivot table 'product_materials'.
     */
    public function materials()
    {
        return $this->hasMany(ProductMaterial::class);
    }

    /**
     * Relationship: A product can have many inks.
     * Assumes you have an Ink model and a pivot table 'product_inks'.
     */
    public function inks()
    {
        return $this->hasMany(ProductInk::class);
    }

    // Add any additional methods or relationships as needed
}
