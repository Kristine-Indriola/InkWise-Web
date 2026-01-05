<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
		return $this->paymentRecords()
			->filter(function ($payment) {
				return Str::lower((string) ($payment['status'] ?? 'pending')) === 'paid';
			})
			->sum(function ($payment) {
				return (float) ($payment['amount'] ?? 0);
			});
	}

	public function balanceDue(): float
	{
		return max($this->grandTotalAmount() - $this->totalPaid(), 0.0);
	}

	public function grandTotalAmount(): float
	{
		$summary = $this->summary_snapshot ?? [];
		$grandTotal = Arr::get($summary, 'totals.grand_total');

		if (!is_numeric($grandTotal) || (float) $grandTotal <= 0.0) {
			$grandTotal = Arr::get($summary, 'totals.total', Arr::get($summary, 'grand_total'));
		}

		if (!is_numeric($grandTotal) || (float) $grandTotal <= 0.0) {
			$metadata = $this->metadata ?? [];
			$grandTotal = Arr::get($metadata, 'financial.grand_total', Arr::get($metadata, 'grand_total'));
		}

		if (!is_numeric($grandTotal) || (float) $grandTotal <= 0.0) {
			$grandTotal = $this->total_amount ?? $this->grand_total ?? 0;
		}

		return (float) ($grandTotal ?? 0);
	}

	public function paymentRecords(): Collection
	{
		$records = $this->payments
			->map(function (Payment $payment) {
				$payload = $payment->raw_payload ?? [];
				$reference = $payment->provider_payment_id
					?: ($payment->intent_id ?: Arr::get($payload, 'reference'));

				return [
					'id' => $payment->id,
					'origin' => 'record',
					'amount' => (float) $payment->amount,
					'currency' => $payment->currency ?? 'PHP',
					'provider' => $payment->provider,
					'method' => $payment->method,
					'mode' => $payment->mode,
					'link' => $payment->receipt_url ?? null,
					'notes' => Arr::get($payload, 'note'),
					'created_at' => $this->normalizePaymentTimestamp($payment->created_at),
					'recorded_at' => $this->normalizePaymentTimestamp($payment->recorded_at),
					'status' => Str::lower((string) $payment->status ?: 'pending'),
					'reference' => $reference,
					'recorded_by' => $payment->recordedBy ? [
						'id' => $payment->recordedBy->user_id ?? $payment->recordedBy->id,
						'name' => $payment->recordedBy->name,
					] : null,
				];
			})
			->values();

		$metadataPayments = collect(Arr::get($this->metadata ?? [], 'payments', []))
			->map(function ($payment, $index) {
				if (!is_array($payment)) {
					$payment = is_object($payment) ? (array) $payment : [];
				}

				$recordedAt = Arr::get($payment, 'recorded_at')
					?? Arr::get($payment, 'paid_at')
					?? Arr::get($payment, 'created_at');

				return [
					'id' => Arr::get($payment, 'id', 'meta_' . $index),
					'origin' => 'metadata',
					'amount' => (float) Arr::get($payment, 'amount', 0),
					'currency' => Arr::get($payment, 'currency', 'PHP'),
					'provider' => Arr::get($payment, 'provider'),
					'method' => Arr::get($payment, 'method'),
					'mode' => Arr::get($payment, 'mode'),
					'link' => Arr::get($payment, 'receipt_url'),
					'notes' => Arr::get($payment, 'note'),
					'created_at' => $this->normalizePaymentTimestamp(Arr::get($payment, 'created_at')),
					'recorded_at' => $this->normalizePaymentTimestamp($recordedAt),
					'status' => Str::lower((string) Arr::get($payment, 'status', 'paid')),
					'reference' => Arr::get($payment, 'reference', Arr::get($payment, 'provider_payment_id')),
					'recorded_by' => null,
				];
			})
			->filter(function ($payment) use ($records) {
				if ($payment['amount'] === 0.0 && empty($payment['reference']) && empty($payment['recorded_at']) && empty($payment['notes'])) {
					return false;
				}

				return !$records->contains(function ($record) use ($payment) {
					$recordReference = $record['reference'] ?? null;
					$paymentReference = $payment['reference'] ?? null;
					if ($recordReference && $paymentReference && Str::lower($recordReference) === Str::lower($paymentReference)) {
						return true;
					}

					$recordTimestamp = $record['recorded_at'] ?? $record['created_at'];
					$paymentTimestamp = $payment['recorded_at'] ?? $payment['created_at'];
					if ($paymentTimestamp && $recordTimestamp) {
						$recordAmount = (float) ($record['amount'] ?? 0);
						return abs($recordAmount - (float) ($payment['amount'] ?? 0)) < 0.01
							&& $recordTimestamp === $paymentTimestamp;
					}

					return false;
				});
			});

		$merged = $records->concat($metadataPayments);
		$deduped = collect();
		$seen = [];

		foreach ($merged as $payment) {
			$referenceKey = $payment['reference'] ? 'ref:' . Str::lower((string) $payment['reference']) : null;
			$amountKey = number_format((float) ($payment['amount'] ?? 0), 2, '.', '');
			$timestampKey = $payment['recorded_at'] ?? $payment['created_at'] ?? 'none';
			$key = $referenceKey ?: ('amt:' . $amountKey . '|ts:' . $timestampKey);

			if (isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$deduped->push($payment);
		}

		return $deduped
			->sortByDesc(function ($payment) {
				return $payment['recorded_at'] ?? $payment['created_at'] ?? '';
			})
			->values();
	}

	protected function normalizePaymentTimestamp($value): ?string
	{
		if (!$value) {
			return null;
		}

		if ($value instanceof Carbon) {
			return $value->toIso8601String();
		}

		if ($value instanceof \DateTimeInterface) {
			return Carbon::instance($value)->toIso8601String();
		}

		if (is_numeric($value)) {
			try {
				return Carbon::createFromTimestamp((int) $value)->toIso8601String();
			} catch (\Throwable $e) {
				return null;
			}
		}

		if (is_string($value)) {
			try {
				return Carbon::parse($value)->toIso8601String();
			} catch (\Throwable $e) {
				return null;
			}
		}

		return null;
	}
}
