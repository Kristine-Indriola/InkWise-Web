<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'design_metadata' => 'array',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function order(): BelongsTo
	{
		return $this->belongsTo(Order::class);
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(Product::class);
	}

	public function bulkSelections(): HasMany
	{
		return $this->hasMany(OrderItemBulk::class);
	}

	public function paperStockSelection(): HasOne
	{
		return $this->hasOne(OrderItemPaperStock::class);
	}

	public function addons(): HasMany
	{
		return $this->hasMany(OrderItemAddon::class);
	}

	public function colors(): HasMany
	{
		return $this->hasMany(OrderItemColor::class);
	}
}
