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
        'lead_time_days',
        'date_available',
        'published_at',
        'unpublished_reason',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('published_at')
                ->orWhereHas('uploads');
        });
    }


    /**
     * Relationship: A product can belong to a template.
     * Assumes you have a Template model and a foreign key 'template_id' in the products table.
     */
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
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
     * Relationship: sizes
     */
    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * Relationship: addons (legacy)
     */
    public function addons()
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * Relationship: ink usage
     */
    public function inkUsage()
    {
        return $this->hasMany(ProductColor::class);
    }

    /**
     * Relationship: colors (alias for inkUsage)
     */
    public function colors()
    {
        return $this->inkUsage();
    }

    /**
     * Relationship: envelope details
     */
    public function envelope()
    {
        return $this->hasOne(ProductEnvelope::class);
    }

    protected static function booted()
    {
        static::retrieved(function (Product $product) {
            // Bulk orders deprecated; keep relation empty to avoid queries
            $product->setRelation('bulkOrders', collect());
        });
    }

    /**
     * Relationship: bulk order tiers.
     */
    public function bulkOrders()
    {
        return $this->hasMany(ProductBulkOrder::class);
    }

    // Add any additional methods or relationships as needed
}
