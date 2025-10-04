<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'order_date' => 'datetime',
		'date_needed' => 'date',
		'summary_snapshot' => 'array',
		'metadata' => 'array',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function customerOrder(): BelongsTo
	{
		return $this->belongsTo(CustomerOrder::class);
	}

	public function customer(): BelongsTo
	{
		return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	public function items(): HasMany
	{
		return $this->hasMany(OrderItem::class);
	}
}
