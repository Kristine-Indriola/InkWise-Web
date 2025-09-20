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
        'name',           // Example: Product name (e.g., "Wedding Invitation")
        'description',    // Example: Product description
        'price',          // Example: If you add pricing later
        'category',       // Example: e.g., 'invitation', 'giveaway'
        // Add more fields as needed based on your database schema
    ];

    /**
     * Relationship: A product can have many giveaways.
     * Assumes you have a Giveaway model and migration.
     */
    public function giveaways()
    {
        return $this->hasMany(Giveaway::class); // Adjust if the model name differs
    }

    /**
     * Relationship: A product can have many invitations (if applicable).
     * Assumes you have an Invitation model.
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class); // Adjust if the model name differs
    }

    // Add any additional methods or relationships as needed
}
