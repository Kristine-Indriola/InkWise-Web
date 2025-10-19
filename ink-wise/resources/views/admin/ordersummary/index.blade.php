@extends('layouts.admin')

@section('title', 'Order Summary')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/ordersummary.css') }}">
	<style>
		.status-progress-card {
			margin-top: 24px;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 8px 16px rgba(108, 127, 172, 0.04);
		}

		.status-progress-card__header {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 16px;
			margin-bottom: 24px;
		}

		.status-progress-card__header h2 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
			color: #111827;
		}

		.status-progress-card__header p {
			margin: 6px 0 0;
			color: #6b7280;
			font-size: 13px;
			line-height: 1.5;
		}

		.status-progress-card__actions {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.status-progress-manage-link {
			font-size: 14px;
			font-weight: 600;
			color: #4f46e5;
			text-decoration: none;
		}

		.status-progress-manage-link:hover {
			text-decoration: underline;
		}

		.order-stage-chip {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border-radius: 9999px;
			font-weight: 600;
			font-size: 13px;
			letter-spacing: 0.04em;
			text-transform: uppercase;
		}

		.order-stage-chip::before {
			content: '';
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: currentColor;
		}

		.order-stage-chip--pending {
			background: #eef2ff;
			color: #3730a3;
		}

		.order-stage-chip--in-production {
			background: #fef3c7;
			color: #b45309;
		}

		.order-stage-chip--confirmed {
			background: #dcfce7;
			color: #15803d;
		}

		.order-stage-chip--completed {
			background: #e0f2fe;
			color: #0369a1;
		}

		.order-stage-chip--cancelled {
			background: #fee2e2;
			color: #b91c1c;
		}

		.status-tracker {
			list-style: none;
			display: flex;
			justify-content: space-between;
			gap: 0;
			padding: 0 4px;
			margin: 0;
		}

		.status-tracker__item {
			position: relative;
			flex: 1;
			text-align: center;
			padding: 0 12px;
		}

		.status-tracker__marker {
			width: 56px;
			height: 56px;
			border-radius: 50%;
			border: 3px solid #d1d5db;
			background: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 16px;
			margin: 0 auto 12px;
			color: #6b7280;
			position: relative;
			z-index: 2;
		}

		.status-tracker__number {
			font-size: 16px;
		}

		.status-tracker__icon {
			font-size: 20px;
			line-height: 1;
		}

		.status-tracker__line {
			position: absolute;
			top: 27px;
			left: calc(50% + 28px);
			width: calc(100% - 56px);
			height: 3px;
			background: #e5e7eb;
			z-index: 1;
			border-radius: 999px;
		}

		.status-tracker__item:last-child .status-tracker__line {
			display: none;
		}

		.status-tracker__title {
			font-size: 15px;
			font-weight: 600;
			margin: 0 0 6px;
			color: #111827;
		}

		.status-tracker__content {
			margin-top: 12px;
		}

		.status-tracker__subtitle {
			margin: 0;
			font-size: 13px;
			color: #6b7280;
			line-height: 1.6;
		}

		.status-tracker__item--done .status-tracker__marker {
			background: #22c55e;
			border-color: #22c55e;
			color: #ffffff;
		}

		.status-tracker__item--done .status-tracker__line {
			background: #22c55e;
		}

		.status-tracker__item--current .status-tracker__marker {
			border-color: #6366f1;
			background: #eef2ff;
			color: #4338ca;
		}

		.status-tracker__item--current .status-tracker__line {
			background: linear-gradient(90deg, #6366f1 0%, #e5e7eb 100%);
		}

		.status-tracker__item--disabled .status-tracker__marker,
		.status-tracker__item--upcoming .status-tracker__marker {
			background: #f3f4f6;
			border-color: #d1d5db;
			color: #9ca3af;
		}

		.status-info-grid {
			display: grid;
			gap: 16px;
			margin-top: 24px;
		}

		@media (min-width: 900px) {
			.status-info-grid {
				grid-template-columns: 2fr 1fr;
			}
		}

		.status-info-card {
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 10px;
			padding: 20px;
		}

		.status-info-card__title {
			margin: 0 0 12px;
			font-size: 16px;
			font-weight: 600;
			color: #111827;
		}

		.status-info-card dl {
			margin: 0;
			display: grid;
			gap: 12px;
		}

		.status-info-card dt {
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #6b7280;
			font-weight: 600;
			margin-bottom: 4px;
		}

		.status-info-card dd {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			color: #111827;
			word-break: break-word;
		}

		.status-info-card__text {
			margin: 0;
			color: #374151;
			font-size: 14px;
			line-height: 1.6;
		}

		.status-info-card__empty {
			color: #9ca3af;
			font-size: 14px;
			line-height: 1.6;
			margin: 0;
		}
	</style>
@endpush

@section('content')
@php
	$order = $order ?? null;
	$customer = data_get($order, 'customer');

	$orderId = data_get($order, 'id');
	$orderNumber = data_get($order, 'order_number')
		?? data_get($order, 'reference')
		?? ($orderId ? 'ORD-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) : 'Draft order');
	$orderTitle = $orderNumber ? 'Order ' . $orderNumber : 'Order Summary';

	$placedAtRaw = data_get($order, 'created_at');
	try {
		$placedAtCarbon = $placedAtRaw ? \Illuminate\Support\Carbon::parse($placedAtRaw) : null;
	} catch (\Throwable $e) {
		$placedAtCarbon = null;
	}
	$placedAt = $placedAtCarbon ? $placedAtCarbon->format('M j, Y g:i A') : '—';

	$paymentStatusRaw = data_get($order, 'payment_status', data_get($order, 'payment.status', 'pending'));
	$paymentStatus = strtolower($paymentStatusRaw ?: 'pending');
	$fulfillmentStatusRaw = data_get($order, 'fulfillment_status', data_get($order, 'status', 'processing'));
	$fulfillmentStatus = strtolower($fulfillmentStatusRaw ?: 'processing');

	$subtotal = (float) data_get($order, 'subtotal', data_get($order, 'total_amount', 0));
	$discount = (float) data_get($order, 'discount_total', 0);
	$shipping = (float) data_get($order, 'shipping_fee', 0);
	$tax = (float) data_get($order, 'tax_total', 0);
	$grandTotal = (float) data_get($order, 'grand_total', $subtotal - $discount + $shipping + $tax);

	$customerName = trim((string) (data_get($customer, 'full_name')
		?? trim((data_get($customer, 'first_name') ?? '') . ' ' . (data_get($customer, 'last_name') ?? ''))))
		?: (data_get($customer, 'name') ?? 'Guest customer');
	$customerEmail = data_get($customer, 'email');
	$customerPhone = data_get($customer, 'phone');
	$customerCompany = data_get($customer, 'company');
	$customerTags = collect(data_get($customer, 'tags', []))->filter()->values();

	$shippingAddress = data_get($order, 'shipping.formatted')
		?? data_get($order, 'shipping.address')
		?? data_get($order, 'shipping_address');
	$billingAddress = data_get($order, 'billing.formatted')
		?? data_get($order, 'billing.address')
		?? data_get($order, 'billing_address');

	$items = collect(data_get($order, 'items', []));

	// Compute subtotal from items and their breakdowns (prefer breakdown totals when present).
	$computedSubtotal = $items->reduce(function ($carry, $it) {
		$qty = (int) data_get($it, 'quantity', 1);
		$unit = (float) data_get($it, 'unit_price', data_get($it, 'price', 0));
		$break = collect(data_get($it, 'breakdown', []));
		// consider numeric breakdown totals (multiply by their quantity when present)
		$breakSum = $break->reduce(function ($bcarry, $row) {
			$rowQty = data_get($row, 'quantity');
			$rowTotal = data_get($row, 'total', data_get($row, 'unit_price'));
			if (is_numeric($rowTotal)) {
				$mult = ($rowQty !== null && is_numeric($rowQty)) ? (int) $rowQty : 1;
				return $bcarry + ((float) $rowTotal * $mult);
			}
			return $bcarry;
		}, 0);

		if ($breakSum > 0) {
			// If breakdown provides totals, use that as this item's subtotal
			return $carry + $breakSum;
		}

		// fallback to explicit item total or computed quantity * unit price
		$itemTotal = data_get($it, 'total');
		if (is_numeric($itemTotal)) {
			return $carry + (float) $itemTotal;
		}

		return $carry + ($qty * $unit);
	}, 0);

	// Use computed subtotal unless order explicitly provided one
	$subtotal = $computedSubtotal ?: (float) data_get($order, 'subtotal', data_get($order, 'total_amount', 0));
	$grandTotal = (float) data_get($order, 'grand_total', $subtotal - $discount + $shipping + $tax);

	// initialize grouped sums (will be accumulated while rendering each item)
	$groupSums = [
		'invitations' => 0.0,
		'paper_stock' => 0.0,
		'addons' => 0.0,
		'envelopes' => 0.0,
		'giveaways' => 0.0,
		'others' => 0.0,
	];
	$timeline = collect(data_get($order, 'timeline', data_get($order, 'events', [])))->sortByDesc(function ($event) {
		$timestamp = data_get($event, 'timestamp', data_get($event, 'created_at'));
		try {
			return $timestamp ? \Illuminate\Support\Carbon::parse($timestamp)->timestamp : 0;
		} catch (\Throwable $e) {
			return 0;
		}
	});

	$actionUrls = collect(data_get($order, 'admin_actions', []));
	$markPaidUrl = $actionUrls->get('mark_paid', data_get($order, 'admin_mark_paid_url'));
	$markFulfilledUrl = $actionUrls->get('mark_fulfilled', data_get($order, 'admin_mark_fulfilled_url'));
	$sendInvoiceUrl = $actionUrls->get('send_invoice', data_get($order, 'admin_send_invoice_url'));
	$schedulePickupUrl = $actionUrls->get('schedule_pickup', data_get($order, 'admin_schedule_pickup_url'));
	$exportUrl = $actionUrls->get('export_pdf', data_get($order, 'admin_export_url'));
	$printUrl = $actionUrls->get('print', data_get($order, 'admin_print_url'));

	try {
		$ordersIndexUrl = route('admin.orders.index');
	} catch (\Throwable $e) {
		$ordersIndexUrl = url('/admin/orders');
	}

	try {
		$statusManageUrl = $order ? route('admin.orders.status.edit', ['order' => data_get($order, 'id')]) : null;
	} catch (\Throwable $e) {
		$statusManageUrl = null;
	}

	$statusOptions = [
		'pending' => 'Order Received',
		'in_production' => 'In Progress',
		'confirmed' => 'To Ship',
		'completed' => 'Completed',
		'cancelled' => 'Cancelled',
	];
	$statusFlow = ['pending', 'in_production', 'confirmed', 'completed'];
	$currentStatus = strtolower((string) data_get($order, 'status', 'pending'));
	$flowIndex = array_search($currentStatus, $statusFlow, true);
	$currentChipModifier = str_replace('_', '-', $currentStatus);
	$currentStatusLabel = $statusOptions[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));
	$formatDateTime = function ($value) {
		try {
			if ($value instanceof \Illuminate\Support\Carbon) {
				return $value->format('M j, Y g:i A');
			}
			if ($value) {
				return \Illuminate\Support\Carbon::parse($value)->format('M j, Y g:i A');
			}
		} catch (\Throwable $e) {
			return null;
		}
		return null;
	};

	$metadataRaw = data_get($order, 'metadata');
	if (is_string($metadataRaw) && $metadataRaw !== '') {
		$decodedMetadata = json_decode($metadataRaw, true);
		$metadata = json_last_error() === JSON_ERROR_NONE && is_array($decodedMetadata) ? $decodedMetadata : [];
	} elseif (is_array($metadataRaw)) {
		$metadata = $metadataRaw;
	} else {
		$metadata = [];
	}

	$trackingNumber = $metadata['tracking_number'] ?? null;
	$statusNote = $metadata['status_note'] ?? null;
	$nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
	$nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;
	$lastUpdatedDisplay = $formatDateTime(data_get($order, 'updated_at'));
@endphp

<main
	class="ordersummary-admin-page admin-page-shell"
	data-order-id="{{ $orderId ?? '' }}"
	data-order-number="{{ $orderNumber }}"
	data-export-url="{{ $exportUrl }}"
	data-print-url="{{ $printUrl }}"
	data-current-status="{{ $currentStatus }}"
	data-status-labels='@json($statusOptions)'
	data-status-flow='@json($statusFlow)'
>
	<header class="page-header ordersummary-page-header">
		<div>
			<h1 class="page-title">{{ $orderTitle }}</h1>
			<p class="page-subtitle">
				Placed {{ $placedAt }} · {{ ucfirst($paymentStatus ?: 'pending') }} payment · {{ ucfirst($fulfillmentStatus ?: 'processing') }} fulfillment
			</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ $ordersIndexUrl }}" class="pill-link" title="Return to order list">Back to orders</a>
			@if($statusManageUrl)
				<a href="{{ $statusManageUrl }}" class="btn btn-outline" title="Update order status">Manage status</a>
			@endif
			<button type="button" class="btn btn-secondary" data-order-action="export">
				<i class="fi fi-rr-download" aria-hidden="true"></i> Export PDF
			</button>
			<button type="button" class="btn btn-primary" data-order-action="print">
				<i class="fi fi-rr-print" aria-hidden="true"></i> Print
			</button>
		</div>
	</header>

	<section class="ordersummary-banner" aria-live="polite">
		<div class="ordersummary-banner__status">
			<span class="status-chip {{ 'status-chip--' . $paymentStatus }}" data-payment-indicator>
				{{ ucfirst($paymentStatus ?: 'pending') }} payment
			</span>
			<span class="status-chip status-chip--outline {{ 'status-chip--' . $fulfillmentStatus }}" data-fulfillment-indicator>
				{{ ucfirst($fulfillmentStatus ?: 'processing') }} fulfillment
			</span>
		</div>
		<div class="ordersummary-banner__meta">
			<span class="ordersummary-banner__id">
				<strong>Order ID:</strong> {{ $orderNumber }}
			</span>
			<span class="ordersummary-banner__date">
				<strong>Placed:</strong> {{ $placedAt }}
			</span>
		</div>
	</section>

	<section class="status-progress-card" data-status-card>
		<header class="status-progress-card__header">
			<div>
				<h2>Order progress</h2>
				<p>Follow the Shopee-style milestones your customer sees from checkout to completion.</p>
			</div>
			<div class="status-progress-card__actions">
				<span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}" data-status-chip>
					{{ $currentStatusLabel }}
				</span>
				@if($statusManageUrl)
					<a href="{{ $statusManageUrl }}" class="status-progress-manage-link">Update status</a>
				@endif
			</div>
		</header>
		<ol class="status-tracker" aria-label="Order progress">
			@foreach($statusFlow as $index => $statusKey)
				@php
					$stateClass = 'status-tracker__item--upcoming';
					if ($currentStatus === 'cancelled') {
						$stateClass = 'status-tracker__item--disabled';
					} elseif ($flowIndex !== false) {
						if ($index < $flowIndex) {
							$stateClass = 'status-tracker__item--done';
						} elseif ($index === $flowIndex) {
							$stateClass = 'status-tracker__item--current';
						}
					}
				@endphp
				<li class="status-tracker__item {{ $stateClass }}" data-status-step="{{ $statusKey }}">
					<div class="status-tracker__marker">
						@if($stateClass === 'status-tracker__item--done')
							<span class="status-tracker__icon">✓</span>
						@else
							<span class="status-tracker__number">{{ $index + 1 }}</span>
						@endif
					</div>
					@if(!$loop->last)
						<span class="status-tracker__line" aria-hidden="true"></span>
					@endif
					<div class="status-tracker__content">
						<p class="status-tracker__title">
							{{ $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey)) }}
						</p>
						<p class="status-tracker__subtitle">
							@switch($statusKey)
								@case('pending')
									Order received and awaiting confirmation.
									@break
								@case('in_production')
									Production team is preparing the items.
									@break
								@case('confirmed')
									Packaged and ready for courier hand-off.
									@break
								@case('completed')
									Delivered and closed out.
									@break
								@default
									Status update in progress.
									@break
							@endswitch
						</p>
					</div>
				</li>
			@endforeach
		</ol>

		<div class="status-info-grid">
			<article class="status-info-card">
				<h2 class="status-info-card__title">Customer-facing update</h2>
				<dl>
					<div>
						<dt>Tracking number</dt>
						<dd>{{ $trackingNumber ?: '— Not provided yet' }}</dd>
					</div>
					<div>
						<dt>Next milestone</dt>
						<dd data-next-status>{{ $nextStatusLabel ?? 'All steps complete' }}</dd>
					</div>
					<div>
						<dt>Last updated</dt>
						<dd>{{ $lastUpdatedDisplay ?? 'Not available' }}</dd>
					</div>
				</dl>
			</article>
			<article class="status-info-card">
				<h2 class="status-info-card__title">Internal note</h2>
				@if(filled($statusNote))
					<p class="status-info-card__text">{{ $statusNote }}</p>
				@else
					<p class="status-info-card__empty">
						No notes yet. Hit “Update status” to leave instructions for the team.
					</p>
				@endif
			</article>
		</div>
	</section>

	<div class="ordersummary-layout">
		<section class="ordersummary-main" aria-label="Order details">
			<article class="ordersummary-card">
				<header class="ordersummary-card__header">
					<h2>Line items</h2>
					<span class="ordersummary-card__meta">
						{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}
					</span>
				</header>

				@if($items->isEmpty())
					<p class="ordersummary-empty-state">
						No items were attached to this order yet. Add an invitation or giveaway to begin fulfillment.
					</p>
				@else
					<div class="ordersummary-table-wrapper">
						<table class="ordersummary-table" role="grid">
							<thead>
								<tr>
									<th scope="col">Item</th>
									<th scope="col">Options</th>
									<th scope="col" class="text-center">Qty</th>
									<th scope="col" class="text-end">Unit price</th>
									<th scope="col" class="text-end">Line total</th>
								</tr>
							</thead>
							<tbody>
								@foreach($items as $item)
									@php
										$quantity = (int) data_get($item, 'quantity', 1);
										$unitPrice = (float) data_get($item, 'unit_price', data_get($item, 'price', 0));
										$lineTotal = (float) data_get($item, 'total', data_get($item, 'subtotal', $quantity * $unitPrice));
										// fallback: some giveaway items store their computed total in design_metadata or item metadata
										if (empty($lineTotal) || $lineTotal === 0.0) {
											$lineTotal = (float) data_get($item, 'design_metadata.total', data_get($item, 'metadata.giveaway.total', data_get($item, 'metadata.giveaway.price', data_get($item, 'metadata.total', 0))));
										}

										$normalizeToArray = function ($value) {
											if ($value instanceof \Illuminate\Support\Collection) {
												$value = $value->all();
											}

											if (is_string($value)) {
												$decoded = json_decode($value, true);
												if (json_last_error() === JSON_ERROR_NONE) {
													return $decoded;
												}
											}

											return is_array($value) ? $value : null;
										};

										$extractMoney = function ($value) {
											if ($value === null || $value === '') {
												return null;
											}

											if (is_numeric($value)) {
												return (float) $value;
											}

											if (is_string($value)) {
												$numeric = preg_replace('/[^0-9.\\-]/', '', $value);
												return $numeric === '' ? null : (float) $numeric;
											}

											return null;
										};


										// normalize breakdown and sync quantities for paper stock and addons
										$rawOptions = data_get($item, 'options', []);
										$paperStockValue = data_get($rawOptions, 'paper_stock') ?? data_get($rawOptions, 'paper stock') ?? null;
										$addonValues = [];
										foreach ($rawOptions as $optKey => $optVal) {
											if (str_contains(strtolower((string) $optKey), 'addon')) {
												$vals = $normalizeToArray($optVal) ?: (is_string($optVal) && trim($optVal) !== '' ? [$optVal] : []);
												foreach ($vals as $av) {
													if (is_array($av)) {
														$addonValues[] = strtolower(trim((string) ($av['name'] ?? $av['label'] ?? $av['title'] ?? $av['value'] ?? '')));
													} elseif (is_object($av)) {
														$tmp = json_decode(json_encode($av), true);
														$addonValues[] = strtolower(trim((string) ($tmp['name'] ?? $tmp['label'] ?? $tmp['title'] ?? $tmp['value'] ?? '')));
													} else {
														$addonValues[] = strtolower(trim((string) $av));
													}
												}
											}
										}
										$addonValues = array_filter($addonValues);

										$breakdown = collect(data_get($item, 'breakdown', []))
											->filter(function ($row) {
												return !empty($row['label']);
											})
											->map(function ($row) use ($quantity, $paperStockValue, $addonValues) {
												$label = strtolower((string) ($row['label'] ?? ''));
												$row = is_array($row) ? $row : (array) $row;

												// if this row matches the paper stock option, multiply quantity/total
												if ($paperStockValue && $paperStockValue !== '' && str_contains($label, strtolower((string) $paperStockValue))) {
													$row['quantity'] = $quantity;
													if (isset($row['unit_price']) && is_numeric($row['unit_price'])) {
														$row['total'] = (float) $row['unit_price'] * $quantity;
													} elseif (isset($row['total']) && is_numeric($row['total'])) {
														$row['total'] = (float) $row['total'] * $quantity;
													}
													return $row;
												}

												// if this row matches any addon name, multiply quantity/total
												foreach ($addonValues as $av) {
													if ($av !== '' && str_contains($label, $av)) {
														$row['quantity'] = $quantity;
														if (isset($row['unit_price']) && is_numeric($row['unit_price'])) {
															$row['total'] = (float) $row['unit_price'] * $quantity;
														} elseif (isset($row['total']) && is_numeric($row['total'])) {
															$row['total'] = (float) $row['total'] * $quantity;
														}
														break;
													}
												}

												return $row;
											})
											->values();

										$formatAddon = function ($addon) use ($extractMoney, $quantity) {
											if ($addon instanceof \Illuminate\Contracts\Support\Arrayable) {
												$addon = $addon->toArray();
											}

											if (is_object($addon)) {
												$addon = (array) $addon;
											}

											if (!is_array($addon)) {
												return trim((string) $addon);
											}

											$name = $addon['name'] ?? $addon['label'] ?? $addon['title'] ?? $addon['value'] ?? null;
											$type = $addon['type'] ?? $addon['category'] ?? null;
											$price = $extractMoney($addon['price'] ?? $addon['amount'] ?? $addon['total'] ?? null);

											if (!$name) {
												try {
													return json_encode($addon, JSON_UNESCAPED_UNICODE);
												} catch (\Throwable $e) {
													return 'Add-on';
												}
											}

											$parts = [$name];
											if ($type) {
												$parts[] = '(' . ucfirst(str_replace('_', ' ', (string) $type)) . ')';
											}

											if ($price !== null) {
												$totalPrice = $price * $quantity;
												$priceLabel = $totalPrice > 0.009
													? 'x' . $quantity . ' — ₱' . number_format($totalPrice, 2)
													: 'Included';
												$parts[] = $priceLabel;
											}

											return trim(implode(' ', array_filter($parts)));
										};

										$formatOptionValue = function ($key, $value) use ($normalizeToArray, $formatAddon, $quantity) {
											$keyLower = strtolower((string) $key);

											if (str_contains($keyLower, 'addon')) {
												$addons = $normalizeToArray($value);
												if (!$addons) {
													if (is_string($value) && trim($value) !== '') {
														return trim($value);
													}

													if (is_object($value)) {
														$value = json_decode(json_encode($value), true);
														$addons = is_array($value) ? $value : null;
													}

													if (!$addons) {
														return 'None';
													}
												}
												$labels = array_filter(array_map($formatAddon, $addons));
												return $labels ? implode('; ', $labels) : 'None';
											}

											if ($keyLower == 'paper_stock') {
												return (string) $value . ' x' . $quantity;
											}

											if ($keyLower == 'paper_stock_price' && is_numeric($value)) {
												$total = (float) $value * $quantity;
												return '₱' . number_format($total, 2);
											}

											if (is_array($value) || $value instanceof \Illuminate\Support\Collection) {
												$asArray = $normalizeToArray($value);
												if ($asArray) {
													return implode(', ', array_map(fn ($entry) => is_scalar($entry) ? (string) $entry : json_encode($entry), $asArray));
												}
											}

											if (is_object($value)) {
												$value = json_decode(json_encode($value), true);
												return $value ? implode(', ', array_map(fn ($entry) => is_scalar($entry) ? (string) $entry : json_encode($entry), $value)) : '';
											}

											return (string) $value;
										};

										$options = collect(data_get($item, 'options', []))
											->filter(function ($value, $key) {
												$k = strtolower($key);
												// exclude duplicated summary keys (quantity/price) and paper stock/addon summaries
												if (in_array($k, ['quantity_set', 'price_per_unit', 'paper_stock', 'paper_stock_price'])) {
													return false;
												}
												if (str_contains($k, 'addon')) {
													return false;
												}
												return true;
											})
											->filter(fn ($value) => filled($value))
											->map(function ($value, $key) use ($formatOptionValue) {
												$label = ucfirst(str_replace('_', ' ', $key));
												$valueLabel = trim($formatOptionValue($key, $value));
												return $label . ': ' . ($valueLabel !== '' ? $valueLabel : '—');
											})
											->unique(fn ($option) => \Illuminate\Support\Str::lower($option))
											->values();
										$images = collect(data_get($item, 'preview_images', data_get($item, 'images', [])))->filter();

										// detect if item is envelope or giveaway by product_type or name
										$ptype = strtolower((string) data_get($item, 'product_type', ''));
										$iname = strtolower((string) data_get($item, 'name', ''));
										$ltype = strtolower((string) data_get($item, 'line_type', ''));
										$isEnvelope = str_contains($ptype, 'envelope') || str_contains($iname, 'envelope');
										$isGiveaway = $ltype === 'giveaway' || str_contains($ptype, 'giveaway') || str_contains($iname, 'giveaway') || str_contains($iname, 'freebie');

										// calculate breakdown sum for this item
										$breakdownSum = 0;
										foreach ($breakdown as $brow) {
											$btotal = data_get($brow, 'total');
											if (!is_numeric($btotal)) {
												$bunit = data_get($brow, 'unit_price');
												$bqty = data_get($brow, 'quantity') ?: 1;
												$btotal = is_numeric($bunit) ? ((float) $bunit * (int) $bqty) : 0;
											}
											$breakdownSum += (float) $btotal;
										}

										// accumulate grouping sums: invitations (main line only, breakdowns are separate)
										if (!$isEnvelope && !$isGiveaway) {
											$groupSums['invitations'] += $lineTotal;
										}

										if ($isEnvelope) {
											$groupSums['envelopes'] += $lineTotal;
										}

										if ($isGiveaway) {
											$groupSums['giveaways'] += $lineTotal;
										}

										// breakdown rows (paper stock and addons) add to group sums only for invitations
										if (!$isEnvelope && !$isGiveaway) {
											foreach ($breakdown as $brow) {
												$lbl = strtolower((string) data_get($brow, 'label', ''));
												$btotal = data_get($brow, 'total');
												if (!is_numeric($btotal)) {
													$bunit = data_get($brow, 'unit_price');
													$bqty = data_get($brow, 'quantity') ?: 1;
													$btotal = is_numeric($bunit) ? ((float) $bunit * (int) $bqty) : 0;
												}
												$btotal = (float) $btotal;

												if ($lbl !== '' && str_contains($lbl, strtolower((string) $paperStockValue))) {
													$groupSums['paper_stock'] += $btotal;
												} elseif (collect($addonValues)->contains(function ($v) use ($lbl) { return $v !== '' && str_contains($lbl, $v); })) {
													$groupSums['addons'] += $btotal;
												} else {
													$groupSums['others'] += $btotal;
												}
											}
										}
									@endphp
									<tr>
										<td>
											<div class="item-cell">
												@if($images->isNotEmpty())
													<img src="{{ $images->first() }}" alt="" class="item-cell__thumb">
												@endif
												<div>
													<strong>{{ data_get($item, 'name', 'Custom product') }}</strong>
													@if(filled(data_get($item, 'sku')))
														<span class="item-cell__sku">SKU · {{ data_get($item, 'sku') }}</span>
													@endif
												</div>
											</div>
										</td>
										<td>
											@if($options->isEmpty())
												<span class="item-option">—</span>
											@else
												<ul class="item-options">
													@foreach($options as $option)
														<li>{{ $option }}</li>
													@endforeach
												</ul>
											@endif
										</td>
										<td class="text-center">{{ $quantity }}</td>
										<td class="text-end" data-money>{{ number_format($unitPrice, 2) }}</td>
										<td class="text-end" data-money>{{ number_format($lineTotal, 2) }}</td>
									</tr>
									@if($breakdown->isNotEmpty())
										@foreach($breakdown as $row)
											@php
												$rowQuantity = data_get($row, 'quantity');
												$rowUnit = data_get($row, 'unit_price');
												$rowTotal = data_get($row, 'total', $rowUnit);
											@endphp
											<tr class="ordersummary-row--breakdown">
												<td></td>
												<td colspan="1">
													<span class="item-breakdown-label">{{ $row['label'] }}</span>
												</td>
												<td class="text-center">{{ $rowQuantity !== null ? $rowQuantity : '—' }}</td>
												<td class="text-end" @if($rowUnit !== null) data-money @endif>
													{{ $rowUnit !== null ? number_format((float) $rowUnit, 2) : '—' }}
												</td>
												<td class="text-end" @if($rowTotal !== null) data-money @endif>
													{{ $rowTotal !== null ? number_format((float) $rowTotal, 2) : '—' }}
												</td>
											</tr>
										@endforeach
									@endif
								@endforeach
							</tbody>
						</table>
					</div>
				@endif
			</article>

			<article class="ordersummary-card">
				<header class="ordersummary-card__header">
					<h2>Customer</h2>
					@if($customerTags->isNotEmpty())
						<ul class="ordersummary-tags">
							@foreach($customerTags as $tag)
								<li>{{ $tag }}</li>
							@endforeach
						</ul>
					@endif
				</header>
				<div class="ordersummary-customer">
					<div class="ordersummary-customer__profile">
						<div class="avatar">
							<span aria-hidden="true">{{ mb_strtoupper(mb_substr($customerName, 0, 1)) }}</span>
						</div>
						<div>
							<h3>{{ $customerName }}</h3>
							@if($customerCompany)
								<p class="ordersummary-customer__company">{{ $customerCompany }}</p>
							@endif
						</div>
					</div>

					<dl class="ordersummary-contact">
						<div>
							<dt>Email</dt>
							<dd>
								@if($customerEmail)
									<a href="mailto:{{ $customerEmail }}">{{ $customerEmail }}</a>
									<button type="button" class="chip-action" data-copy="{{ $customerEmail }}">
										Copy
									</button>
								@else
									<span>—</span>
								@endif
							</dd>
						</div>
						<div>
							<dt>Phone</dt>
							<dd>
								@if($customerPhone)
									<a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
									<button type="button" class="chip-action" data-copy="{{ $customerPhone }}">
										Copy
									</button>
								@else
									<span>—</span>
								@endif
							</dd>
						</div>
					</dl>

					<div class="ordersummary-address-grid">
						<div>
							<span class="ordersummary-address-label">Shipping address</span>
							<p>{{ $shippingAddress ?? '—' }}</p>
							@if($shippingAddress)
								<button type="button" class="chip-action" data-copy="{{ $shippingAddress }}">Copy</button>
							@endif
						</div>
						<div>
							<span class="ordersummary-address-label">Billing address</span>
							<p>{{ $billingAddress ?? '—' }}</p>
							@if($billingAddress)
								<button type="button" class="chip-action" data-copy="{{ $billingAddress }}">Copy</button>
							@endif
						</div>
					</div>
				</div>
			</article>

			<article class="ordersummary-card">
				<header class="ordersummary-card__header">
					<h2>Timeline</h2>
					<div class="ordersummary-card__actions">
						<button type="button" class="btn btn-secondary btn-sm" data-timeline-toggle>Collapse</button>
					</div>
				</header>
				<ul class="ordersummary-timeline" data-timeline-list>
					@forelse($timeline as $event)
						@php
							$label = data_get($event, 'label', data_get($event, 'title', 'Activity'));
							$author = data_get($event, 'author', data_get($event, 'performed_by'));
							$state = strtolower((string) data_get($event, 'state', data_get($event, 'status', 'default')));
							$note = data_get($event, 'note', data_get($event, 'description'));
							$timestampRaw = data_get($event, 'timestamp', data_get($event, 'created_at'));
							try {
								$timestampCarbon = $timestampRaw ? \Illuminate\Support\Carbon::parse($timestampRaw) : null;
							} catch (\Throwable $e) {
								$timestampCarbon = null;
							}
							$timestampDisplay = $timestampCarbon ? $timestampCarbon->format('M j, Y g:i A') : ($timestampRaw ?? '—');
							$timestampIso = $timestampCarbon ? $timestampCarbon->toIso8601String() : null;
						@endphp
						<li class="timeline-entry timeline-entry--{{ $state }}" data-timeline-entry>
							<div class="timeline-entry__bullet" aria-hidden="true"></div>
							<div class="timeline-entry__body">
								<header>
									<h3>{{ $label }}</h3>
									<time datetime="{{ $timestampIso }}">{{ $timestampDisplay }}</time>
								</header>
								@if($author || $note)
									<p class="timeline-entry__meta">
										@if($author)
											<span>By {{ $author }}</span>
										@endif
										@if($author && $note)
											<span aria-hidden="true">·</span>
										@endif
										@if($note)
											<span>{{ $note }}</span>
										@endif
									</p>
								@endif
							</div>
						</li>
					@empty
						<li class="timeline-empty" data-timeline-empty>
							<div>
								<strong>No timeline events recorded yet.</strong>
								<p>Add a note below to document your next action.</p>
							</div>
						</li>
					@endforelse
				</ul>
				<form class="ordersummary-note" data-note-form>
					@csrf
					<label for="orderNote">Add internal note</label>
					<textarea id="orderNote" name="note" rows="3" placeholder="Record a call, update, or next step..."></textarea>
					<div class="ordersummary-note__actions">
						<button type="submit" class="btn btn-primary btn-sm">Save note</button>
						<span class="hint">Notes save locally until backend endpoint is connected.</span>
					</div>
				</form>
			</article>
		</section>

		<aside
			class="ordersummary-sidebar"
			aria-label="Order sidebar controls"
			data-order-sidebar
			data-payment-status="{{ $paymentStatus }}"
			data-fulfillment-status="{{ $fulfillmentStatus }}"
			data-update-payment-url="{{ $markPaidUrl }}"
			data-update-fulfillment-url="{{ $markFulfilledUrl }}"
			data-send-invoice-url="{{ $sendInvoiceUrl }}"
			data-schedule-pickup-url="{{ $schedulePickupUrl }}"
			data-customer-email="{{ $customerEmail }}"
		>
			<section class="sidebar-card sidebar-card--totals">
				<header>
					<h2>Payment summary</h2>
					<button type="button" class="chip-action" data-sidebar-toggle aria-expanded="true">
						Collapse
					</button>
				</header>
				<dl class="sidebar-totals" data-sidebar-section>
					<div>
						<dt>Invitations</dt>
						<dd data-money>{{ number_format($groupSums['invitations'] ?? 0, 2) }}</dd>
					</div>
					<div>
						<dt>Paper stock</dt>
						<dd data-money>{{ number_format($groupSums['paper_stock'] ?? 0, 2) }}</dd>
					</div>
					<div>
						<dt>Add-ons</dt>
						<dd data-money>{{ number_format($groupSums['addons'] ?? 0, 2) }}</dd>
					</div>
					<div>
						<dt>Envelopes</dt>
						<dd data-money>{{ number_format($groupSums['envelopes'] ?? 0, 2) }}</dd>
					</div>
					<div>
						<dt>Giveaways</dt>
						<dd data-money>{{ number_format($groupSums['giveaways'] ?? 0, 2) }}</dd>
					</div>
					@if(!empty($groupSums['others']) && (float) $groupSums['others'] !== 0.0)
						<div>
							<dt>Other line items</dt>
							<dd data-money>{{ number_format($groupSums['others'] ?? 0, 2) }}</dd>
						</div>
					@endif
					<div>
						<dt>Subtotal</dt>
						@php
							$computedSidebarSubtotal = array_sum($groupSums);
							$subtotal = $computedSidebarSubtotal ?: $subtotal;
							$grandTotal = (float) data_get($order, 'grand_total', $subtotal - $discount + $shipping + $tax);
						@endphp
						<dd data-money>{{ number_format($subtotal, 2) }}</dd>
					</div>
					<div>
						<dt>Discounts</dt>
						<dd class="text-negative" data-money>-{{ number_format($discount, 2) }}</dd>
					</div>
					<div>
						<dt>Shipping</dt>
						<dd data-money>{{ number_format($shipping, 2) }}</dd>
					</div>
					@if(!empty($tax) && (float) $tax !== 0.0)
						<div>
							<dt>Tax</dt>
							<dd data-money>{{ number_format($tax, 2) }}</dd>
						</div>
					@endif
					<div class="sidebar-totals__total">
						<dt>Total due</dt>
						<dd data-grand-total data-money>{{ number_format($grandTotal, 2) }}</dd>
					</div>
				</dl>
				<div class="sidebar-actions">
					<button type="button" class="btn btn-primary btn-sm" data-sidebar-action="mark-paid">
						Mark as paid
					</button>
					<button type="button" class="btn btn-secondary btn-sm" data-sidebar-action="send-invoice">
						Send invoice
					</button>
				</div>
			</section>

			<section class="sidebar-card">
				<header>
					<h2>Fulfillment</h2>
					<span class="status-chip status-chip--outline {{ 'status-chip--' . $fulfillmentStatus }}" data-fulfillment-pill>
						{{ ucfirst($fulfillmentStatus ?: 'processing') }}
					</span>
				</header>
				<div class="sidebar-list">
					<div class="sidebar-list__row">
						<span>Production</span>
						<span data-sidebar-production>Status: {{ ucfirst(data_get($order, 'production_status', 'queue')) }}</span>
					</div>
					<div class="sidebar-list__row">
						<span>Estimated ship date</span>
						<span>
							@php
								$shipDateRaw = data_get($order, 'estimated_ship_date');
								try {
									$shipDateCarbon = $shipDateRaw ? \Illuminate\Support\Carbon::parse($shipDateRaw) : null;
								} catch (\Throwable $e) {
									$shipDateCarbon = null;
								}
							@endphp
							{{ $shipDateCarbon ? $shipDateCarbon->format('M j, Y') : 'TBD' }}
						</span>
					</div>
				</div>
				<div class="sidebar-actions">
					<button type="button" class="btn btn-primary btn-sm" data-sidebar-action="mark-fulfilled">
						Mark as fulfilled
					</button>
					<button type="button" class="btn btn-secondary btn-sm" data-sidebar-action="schedule-pickup">
						Schedule pickup
					</button>
				</div>
			</section>

			<section class="sidebar-card">
				<header>
					<h2>Quick contact</h2>
				</header>
				<div class="sidebar-quick-actions">
					@if($customerEmail)
						<a class="pill-link" href="mailto:{{ $customerEmail }}">
							<i class="fi fi-rr-envelope" aria-hidden="true"></i> Email customer
						</a>
					@endif
					@if($customerPhone)
						<a class="pill-link" href="tel:{{ $customerPhone }}">
							<i class="fi fi-rr-phone-call" aria-hidden="true"></i> Call customer
						</a>
					@endif
					<button type="button" class="pill-link" data-sidebar-action="copy-summary">
						<i class="fi fi-rr-copy" aria-hidden="true"></i> Copy summary
					</button>
				</div>
			</section>
		</aside>
	</div>
</main>

<div class="ordersummary-toast" data-toast role="status" aria-live="polite" hidden></div>

<script src="{{ asset('js/admin/ordersummary.js') }}"></script>
@endsection
