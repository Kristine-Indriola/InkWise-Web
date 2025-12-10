<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'order_date' => 'datetime',
		'date_needed' => 'date',
		'summary_snapshot' => 'array',
		'metadata' => 'array',
		'archived' => 'boolean',
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

	public function getEffectiveCustomerAttribute()
	{
		// First try direct customer relationship
		if ($this->customer) {
			return $this->customer;
		}

		// If no direct customer, try through customerOrder
		if ($this->customerOrder && $this->customerOrder->customer) {
			return $this->customerOrder->customer;
		}

		// If customerOrder exists but no customer, return customerOrder as pseudo-customer
		if ($this->customerOrder) {
			return $this->customerOrder;
		}

		return null;
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	public function items(): HasMany
	{
		return $this->hasMany(OrderItem::class);
	}

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class);
	}

	public function rating(): HasOne
	{
		return $this->hasOne(OrderRating::class);
	}

	public function activities(): HasMany
	{
		return $this->hasMany(OrderActivity::class)->orderBy('created_at', 'desc');
	}

	public function totalPaid(): float
	{
		return (float) $this->payments
			->where('status', 'paid')
			->sum(fn (Payment $payment) => (float) $payment->amount);
	}

	public function balanceDue(): float
	{
		return max((float) ($this->total_amount ?? 0) - $this->totalPaid(), 0.0);
	}
}
