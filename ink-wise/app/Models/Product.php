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
        'name',
        'event_type',
        'product_type',
        'theme_style',
        'description',
        'base_price',
        'lead_time',
        'date_available',
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
        return $this->hasMany(ProductUpload::class)->latest();
    }

    /**
     * Relationship: A product can have many materials.
     */
    public function materials()
    {
        return $this->hasMany(ProductMaterial::class);
    }

    /**
     * Relationship: product images (front/back/preview)
     */
    public function images()
    {
        return $this->hasOne(ProductImage::class);
    }

    /**
     * Relationship: paper stocks
     */
    public function paperStocks()
    {
        return $this->hasMany(ProductPaperStock::class);
    }

    /**
     * Relationship: addons
     */
    public function addons()
    {
        return $this->hasMany(ProductAddon::class);
    }

    /**
     * Relationship: colors
     */
    public function colors()
    {
        return $this->hasMany(ProductColor::class);
    }

    /**
     * Relationship: bulk order tiers
     */
    public function bulkOrders()
    {
        return $this->hasMany(ProductBulkOrder::class);
    }

    /**
     * Relationship: envelope details
     */
    public function envelope()
    {
        return $this->hasOne(ProductEnvelope::class);
    }

    // Add any additional methods or relationships as needed
}
