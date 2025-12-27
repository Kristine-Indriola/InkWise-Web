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

		.order-stage-chip--processing {
			background: #ede9fe;
			color: #7c3aed;
		}

		.order-stage-chip--in-production {
			background: #fef3c7;
			color: #b45309;
		}

		.order-stage-chip--confirmed {
			background: #dcfce7;
			color: #15803d;
		}

		.order-stage-chip--pending-awaiting-materials {
			background: #fef3c7;
			color: #92400e;
		}

		.order-stage-chip--to-receive {
			background: #f1f5f9;
			color: #0f172a;
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

		.status-alert {
			padding: 14px 18px;
			border-radius: 10px;
			font-size: 14px;
			border: 1px solid transparent;
		}

		.status-alert--success {
			background: #ecfdf5;
			border-color: #34d399;
			color: #047857;
		}

		.status-alert--danger {
			background: #fef2f2;
			border-color: #f87171;
			color: #b91c1c;
		}
	</style>
	<style>
		.rating-display {
			padding: 20px;
			background: #f9fafb;
			border-radius: 8px;
			margin-top: 20px;
		}

		.rating-display .stars {
			display: flex;
			gap: 4px;
			margin-bottom: 12px;
		}

		.rating-display .star {
			font-size: 24px;
			color: #ddd;
		}

		.rating-display .star.filled {
			color: #f59e0b;
		}

		.rating-display .review-text {
			margin: 12px 0;
			font-size: 14px;
			color: #374151;
			line-height: 1.5;
		}

		.rating-display .rating-photos-section {
			margin-top: 16px;
		}

		.rating-display .rating-photos {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}

		.rating-display .rating-photo {
			width: 80px;
			height: 80px;
			object-fit: cover;
			border-radius: 6px;
			border: 1px solid #e5e7eb;
			transition: transform 0.2s ease;
		}

		.rating-display .rating-photo:hover {
			transform: scale(1.05);
		}
	</style>
	<style>
		.ordersummary-card__header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 20px;
		}

		.ordersummary-card__header h2 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
			color: #111827;
		}

		.ordersummary-card__actions {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ordersummary-card__actions .btn {
			font-size: 14px;
			padding: 6px 12px;
			border-radius: 6px;
			border: 1px solid #d1d5db;
			background: #f9fafb;
			color: #374151;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ordersummary-card__actions .btn:hover {
			background: #f3f4f6;
			border-color: #9ca3af;
		}

		.ordersummary-card__actions .btn i {
			margin-right: 6px;
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
		?? ($orderId ? 'ORD-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) : 'New Order');
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
	$grandTotal = (float) data_get($order, 'grand_total', $subtotal);

	$customerName = trim((string) (data_get($customer, 'full_name')
		?? trim((data_get($customer, 'first_name') ?? '') . ' ' . (data_get($customer, 'last_name') ?? ''))))
		?: (data_get($customer, 'name') ?? 'Guest customer');
	$customerId = data_get($customer, 'id');
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
	$grandTotal = (float) data_get($order, 'grand_total', $subtotal);

	// initialize grouped sums (will be accumulated while rendering each item)
	$groupSums = [
		'invitations' => 0.0,
		'paper_stock' => 0.0,
		'addons' => 0.0,
		'envelopes' => 0.0,
		'giveaways' => 0.0,
		'others' => 0.0,
	];

	// Build timeline from order activities
	$timeline = collect();
	
	// Add order creation event
	$timeline->push([
		'label' => 'Order Created',
		'author' => $customerName,
		'state' => 'created',
		'note' => null,
		'timestamp' => data_get($order, 'created_at'),
	]);

	// Add activity events from database
	$activities = collect(data_get($order, 'activities', []));
	foreach ($activities as $activity) {
		$timeline->push([
			'label' => data_get($activity, 'description', 'Order Updated'),
			'author' => data_get($activity, 'user_name', 'System') . ' (' . data_get($activity, 'user_role', 'Unknown') . ')',
			'state' => data_get($activity, 'activity_type', 'updated'),
			'note' => null,
			'timestamp' => data_get($activity, 'created_at'),
		]);
	}

	$timeline = $timeline->sortBy(function ($event) {
		$timestamp = data_get($event, 'timestamp');
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
	try {
		$paymentManageUrl = $order ? route('admin.orders.payment.edit', ['order' => data_get($order, 'id')]) : null;
	} catch (\Throwable $e) {
		$paymentManageUrl = null;
	}
	$ordersBackUrl = $statusManageUrl ?? $ordersIndexUrl;

	$statusOptions = [
		'draft' => 'New Order',
		'pending' => 'Order Received',
		'processing' => 'Processing',
		'in_production' => 'In Progress',
		'confirmed' => 'Ready for Pickup',
		'completed' => 'Completed',
		'cancelled' => 'Cancelled',
	];
	$statusFlow = ['draft', 'pending', 'pending_awaiting_materials', 'processing', 'in_production', 'confirmed', 'completed'];
	$currentStatus = strtolower((string) data_get($order, 'status', 'draft'));
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
			<a href="{{ $ordersIndexUrl }}" class="pill-link" title="Return to order list">Back to order list</a>
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
			</div>
			<div class="status-progress-card__actions">
				<span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}" data-status-chip>
					{{ $currentStatusLabel }}
				</span>
				@if($statusManageUrl && $paymentStatus !== 'pending' && $currentStatus !== 'completed')
					<a href="{{ $statusManageUrl }}" class="status-progress-manage-link">Update status</a>
				@elseif($currentStatus === 'completed')
					<span class="status-progress-manage-link" style="color: #9ca3af; cursor: not-allowed;" title="Cannot update status for completed orders">Update status</span>
				@elseif($paymentStatus === 'pending')
					<span class="status-progress-manage-link" style="color: #9ca3af; cursor: not-allowed;" title="Cannot update status while payment is pending">Update status</span>
				@endif
				@if($paymentManageUrl)
					<a href="{{ $paymentManageUrl }}" class="status-progress-manage-link">Payment Details</a>
				@endif
			</div>
		</header>
		<ol class="status-tracker" aria-hidden="true">
			@foreach($statusFlow as $index => $statusKey)
				@php
					$stateClass = 'status-tracker__item--upcoming';
					if ($currentStatus === 'cancelled') {
						$stateClass = 'status-tracker__item--disabled';
					} elseif ($flowIndex !== false) {
						if ($index < $flowIndex) {
							$stateClass = 'status-tracker__item--done';
						} elseif ($index === $flowIndex) {
							$stateClass = $currentStatus === 'completed'
								? 'status-tracker__item--done'
								: 'status-tracker__item--current';
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
								@case('processing')
									Team is preparing assets before full production starts.
									@break
								@case('in_production')
									Production team is preparing the items.
									@break
								@case('confirmed')
									Packaged and ready for courier hand-off.
									@break
								@case('to_receive')
									Order is in transit to the customer.
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
	</section>

	@if($paymentStatus === 'pending')
		<div class="status-alert status-alert--danger" role="alert" style="margin-top: 16px;">
			<strong>Payment Required:</strong> Order status cannot be updated until payment is confirmed. Please mark the payment as paid first.
		</div>
	@endif

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
					<div class="ordersummary-card__actions">
						@if($customerEmail && $customerId)
							<a href="{{ route('admin.messages.index') }}?start_conversation={{ $customerId }}" class="btn btn-secondary btn-sm">
								<i class="fi fi-rr-envelope" aria-hidden="true"></i> Message Customer
							</a>
						@endif
					</div>
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
			</article> 

			<article class="ordersummary-card">
				<header class="ordersummary-card__header">
					<h2>Customer Rating</h2>
				</header>
				@php
					$rating = data_get($order, 'rating');
					$ratingValue = $rating ? (int) data_get($rating, 'rating', 0) : null;
					$ratingComment = $rating ? data_get($rating, 'comment') : null;
				@endphp
				@if($ratingValue)
					<div class="rating-display" style="padding: 20px;">
						<div class="rating-stars" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
							<div class="stars" style="display: flex; gap: 4px;">
								@for($i = 1; $i <= 5; $i++)
									@if($i <= $ratingValue)
										<span style="color: #f59e0b; font-size: 24px;">★</span>
									@else
										<span style="color: #d1d5db; font-size: 24px;">☆</span>
									@endif
								@endfor
							</div>
							<span style="font-size: 18px; font-weight: 600; color: #111827;">{{ $ratingValue }}/5</span>
						</div>
						@if($ratingComment)
							<div class="rating-comment" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
								<p style="margin: 0; font-style: italic; color: #374151; font-size: 16px; line-height: 1.5;">"{{ $ratingComment }}"</p>
							</div>
						@endif
						@php
							$ratingPhotos = collect(data_get($rating, 'photos', []))->filter();
						@endphp
						@if($ratingPhotos->isNotEmpty())
							<div class="rating-photos-section">
								<h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #374151;">Rating Photos</h4>
								<div class="rating-photos">
									@foreach($ratingPhotos as $photo)
										@php
											$photoUrl = \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
												? $photo
												: \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
										@endphp
										<a href="{{ $photoUrl }}" target="_blank" rel="noopener" style="display: block;">
											<img src="{{ $photoUrl }}" alt="Rating photo" class="rating-photo">
										</a>
									@endforeach
								</div>
							</div>
						@endif
					</div>
				@else
					<div style="padding: 20px; text-align: center; color: #6b7280;">
						<p style="margin: 0; font-size: 16px;">No customer rating has been submitted yet.</p>
					</div>
				@endif
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
				<div class="payment-status-highlight" style="background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; padding: 16px; margin-bottom: 16px; text-align: center;">
					<div style="font-size: 14px; font-weight: 600; color: #0c4a6e; margin-bottom: 8px;">Payment Status</div>
					<span class="status-chip {{ 'status-chip--' . $paymentStatus }}" style="font-size: 16px; padding: 8px 16px;">
						{{ ucfirst($paymentStatus ?: 'pending') }}
					</span>
				</div>
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
							$grandTotal = $subtotal;
						@endphp
						<dd data-money>{{ number_format($subtotal, 2) }}</dd>
					</div>
					<div class="sidebar-totals__total">
						<dt>Total due</dt>
						<dd data-grand-total data-money>{{ number_format($grandTotal, 2) }}</dd>
					</div>
				</dl>
			</section>

		</aside>
	</div>
</main>

<div class="ordersummary-toast" data-toast role="status" aria-live="polite" hidden></div>

<script src="{{ asset('js/admin/ordersummary.js') }}"></script>
@endsection
