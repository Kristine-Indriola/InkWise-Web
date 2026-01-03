<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;


class OrderItem extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'design_metadata' => 'array',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public const LINE_TYPE_INVITATION = 'invitation';
	public const LINE_TYPE_ENVELOPE = 'envelope';
	public const LINE_TYPE_GIVEAWAY = 'giveaway';

	public function order(): BelongsTo
	{
		return $this->belongsTo(Order::class);
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(Product::class);
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

	public function scopeLineType($query, string $lineType)
	{
		return $query->where('line_type', $lineType);
	}
}
