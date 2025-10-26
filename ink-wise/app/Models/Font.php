<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Font extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'source',
        'file_path',
        'google_family',
        'variants',
        'subsets',
        'category',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'variants' => 'array',
        'subsets' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the font URL for web fonts
     */
    public function getUrlAttribute()
    {
        if ($this->source === 'uploaded' && $this->file_path) {
            return Storage::url($this->file_path);
        }

        if ($this->source === 'google' && $this->google_family) {
            $variants = $this->variants ? implode(',', $this->variants) : '400';
            return "https://fonts.googleapis.com/css2?family={$this->google_family}:wght@{$variants}&display=swap";
        }

        return null;
    }

    /**
     * Get CSS font-family declaration
     */
    public function getCssFamilyAttribute()
    {
        return $this->source === 'google' ? $this->google_family : $this->name;
    }

    /**
     * Record font usage
     */
    public function recordUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope for active fonts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for fonts by source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for fonts by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get popular fonts (by usage count)
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Search fonts by name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%")
              ->orWhere('google_family', 'like', "%{$search}%");
        });
    }
}
