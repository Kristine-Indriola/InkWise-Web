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
	<style>
		.payment-summary-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
			gap: 12px;
			margin-bottom: 16px;
		}

		.payment-summary-grid__item {
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 12px;
		}

		.payment-summary-grid__label {
			display: block;
			font-size: 12px;
			font-weight: 600;
			color: #6b7280;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			margin-bottom: 4px;
		}

		.payment-summary-grid__value {
			font-size: 18px;
			font-weight: 600;
			color: #0f172a;
		}

		.payment-alert {
			border-radius: 8px;
			padding: 12px;
			margin-bottom: 16px;
			font-size: 13px;
			font-weight: 500;
		}

		.payment-alert--balance {
			background: #fef3c7;
			border: 1px solid #fbbf24;
			color: #92400e;
		}

		.payment-alert--clear {
			background: #dcfce7;
			border: 1px solid #34d399;
			color: #166534;
		}

		.payment-alert--pending {
			background: #e0f2fe;
			border: 1px solid #38bdf8;
			color: #0c4a6e;
		}

		.payment-history {
			border-top: 1px solid #e5e7eb;
			padding-top: 16px;
			margin-top: 12px;
		}

		.payment-history h3 {
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
			margin: 0 0 12px;
		}

		.payment-history__row {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 12px;
			padding: 12px 0;
			border-top: 1px solid #f1f5f9;
		}

		.payment-history__row:first-of-type {
			border-top: none;
			padding-top: 0;
		}

		.payment-history__amount {
			font-size: 16px;
			font-weight: 600;
			color: #0f172a;
		}

		.payment-history__meta {
			font-size: 12px;
			color: #6b7280;
			margin-top: 4px;
		}

		.payment-history__status {
			display: inline-flex;
			align-items: center;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			white-space: nowrap;
		}

		.payment-history__status--paid {
			background: #dcfce7;
			color: #166534;
		}

		.payment-history__status--pending {
			background: #fef3c7;
			color: #92400e;
		}

		.payment-history__status--partial {
			background: #ede9fe;
			color: #5b21b6;
		}

		.payment-history__status--failed,
		.payment-history__status--refunded {
			background: #fee2e2;
			color: #991b1b;
		}
	</style>
	<style>
		.materials-grid {
			display: grid;
			gap: 16px;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		}

		.material-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 18px;
			box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			position: relative;
		}

		.material-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
		}

		.material-card__type {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			padding: 6px 10px;
			border-radius: 999px;
			background: #eef2ff;
			color: #4338ca;
			margin-bottom: 12px;
		}

		.material-card--addon .material-card__type {
			background: #fef3c7;
			color: #b45309;
		}

		.material-card--paper .material-card__type {
			background: #dcfce7;
			color: #166534;
		}

		.material-card--other .material-card__type {
			background: #f1f5f9;
			color: #1f2937;
		}

		.material-card--deducted .material-card__type {
			background: #fee2e2;
			color: #dc2626;
		}

		.material-card__title {
			margin: 0 0 8px;
			font-size: 18px;
			font-weight: 600;
			color: #111827;
		}

		.material-card__quantity,
		.material-card__cost {
			margin: 0;
			font-size: 14px;
			color: #475569;
		}

		.material-card__quantity strong {
			font-size: 20px;
			color: #1f2937;
		}

		.item-cell__thumb-button {
			appearance: none;
			border: none;
			background: transparent;
			padding: 0;
			cursor: pointer;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
			overflow: hidden;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		.item-cell__thumb-button:focus-visible {
			outline: 3px solid rgba(99, 102, 241, 0.5);
			outline-offset: 2px;
		}

		.item-cell__thumb-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 18px rgba(15, 23, 42, 0.18);
		}

		.item-cell__thumb-button img {
			display: block;
		}

		.ordersummary-preview-modal {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 105;
		}

		.ordersummary-preview-modal[hidden] {
			display: none !important;
		}

		.ordersummary-preview-backdrop {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(15, 23, 42, 0.6);
		}

		.ordersummary-preview-dialog {
			position: relative;
			background: #ffffff;
			border-radius: 16px;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
			max-width: min(900px, 95vw);
			width: 90vw;
			max-height: 90vh;
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.ordersummary-preview-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 16px 20px;
			border-bottom: 1px solid #e2e8f0;
		}

		.ordersummary-preview-header h2 {
			margin: 0;
			font-size: 18px;
			font-weight: 700;
			color: #111827;
		}

		.ordersummary-preview-meta {
			font-size: 14px;
			color: #475569;
			margin-top: 4px;
		}

		.ordersummary-preview-meta span[data-preview-label] {
			color: #0f172a;
			font-weight: 500;
		}

		.ordersummary-preview-meta span[data-preview-label]:empty {
			display: none;
		}

		.ordersummary-preview-meta span[data-preview-label]::before {
			content: '·';
			display: inline-block;
			margin: 0 6px 0 8px;
			color: #94a3b8;
		}

		.ordersummary-preview-close {
			appearance: none;
			border: none;
			background: transparent;
			cursor: pointer;
			color: #475569;
			font-size: 18px;
			padding: 6px;
			border-radius: 6px;
		}

		.ordersummary-preview-close:hover,
		.ordersummary-preview-close:focus-visible {
			background: #f1f5f9;
			outline: none;
		}

		.ordersummary-preview-body {
			flex: 1;
			padding: 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #f8fafc;
		}

		.ordersummary-preview-body img {
			max-width: 100%;
			height: auto;
			max-height: 65vh;
			border-radius: 12px;
			box-shadow: 0 12px 24px rgba(15, 23, 42, 0.15);
		}

		.ordersummary-preview-details {
			padding: 4px 20px 18px;
			background: #ffffff;
			border-top: 1px solid #e2e8f0;
		}

		.ordersummary-preview-details[hidden] {
			display: none !important;
		}

		.ordersummary-preview-details h3 {
			margin: 0 0 8px;
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
		}

		.ordersummary-preview-materials {
			margin: 0;
			padding: 0;
			list-style: none;
		}

		.ordersummary-preview-materials li {
			font-size: 13px;
			color: #475569;
			padding: 8px 0;
			border-top: 1px solid #e2e8f0;
		}

		.ordersummary-preview-materials li:first-child {
			border-top: none;
		}

		.ordersummary-preview-actions {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 16px 20px;
			border-top: 1px solid #e2e8f0;
		}

		.ordersummary-preview-actions .btn {
			white-space: nowrap;
		}

		.ordersummary-preview-actions .btn[disabled],
		.ordersummary-preview-actions .btn:disabled {
			opacity: 0.4;
			cursor: not-allowed;
		}

		body.ordersummary-modal-open {
			overflow: hidden;
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

	// Collect production materials for quick-reference cards.
	$materialRequirements = [
		'paper' => [],
		'addon' => [],
		'other' => [],
	];

	$registerMaterial = function (string $type, string $label, $quantity, $unitPrice, $total) use (&$materialRequirements) {
		$typeKey = in_array($type, ['paper', 'addon'], true) ? $type : 'other';
		$normalizedLabel = trim(mb_strtolower($label));
		$key = $normalizedLabel !== '' ? $normalizedLabel : uniqid($typeKey . '_');

		if (!isset($materialRequirements[$typeKey][$key])) {
			$materialRequirements[$typeKey][$key] = [
				'label' => $label !== '' ? $label : 'Material',
				'quantity' => 0.0,
				'unit_price' => null,
				'total' => 0.0,
			];
		}

		if (is_numeric($quantity)) {
			$materialRequirements[$typeKey][$key]['quantity'] += (float) $quantity;
		}

		if (is_numeric($unitPrice) && $materialRequirements[$typeKey][$key]['unit_price'] === null) {
			$materialRequirements[$typeKey][$key]['unit_price'] = (float) $unitPrice;
		}

		if (is_numeric($total)) {
			$materialRequirements[$typeKey][$key]['total'] += (float) $total;
		}
	};

	$materialsList = collect();

	$normalizeTimelineState = static function ($value) {
		if (!is_string($value)) {
			return 'default';
		}
		$normalized = strtolower(trim($value));
		$normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized);
		$normalized = trim($normalized, '-');
		return $normalized !== '' ? $normalized : 'default';
	};

	$decodeJsonValue = static function ($value) {
		if (!is_string($value)) {
			return null;
		}
		$trimmed = trim($value);
		if ($trimmed === '') {
			return null;
		}
		$firstChar = $trimmed[0] ?? '';
		if (!in_array($firstChar, ['{', '[', '"'], true)) {
			return null;
		}
		$decoded = json_decode($trimmed, true);
		return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
	};

	$formatKeyForDisplay = static function ($key) {
		$key = is_string($key) ? $key : (string) $key;
		$key = strtr($key, ['_' => ' ', '-' => ' ', '.' => ' ']);
		return ucwords($key);
	};

	$formatTimelinePayload = null;
	$formatTimelinePayload = static function ($value) use (&$formatTimelinePayload, $decodeJsonValue, $formatKeyForDisplay) {
		$decoded = $decodeJsonValue($value);
		if ($decoded !== null) {
			$value = $decoded;
		}

		if ($value instanceof \DateTimeInterface) {
			return $value->format('M j, Y g:i A');
		}

		if (is_bool($value)) {
			return $value ? 'Yes' : 'No';
		}

		if (is_array($value)) {
			if ($value === []) {
				return null;
			}
			$isAssoc = array_keys($value) !== range(0, count($value) - 1);
			$segments = [];
			foreach ($value as $key => $item) {
				$text = $formatTimelinePayload($item);
				if ($text === null || $text === '') {
					continue;
				}
				if ($isAssoc && is_string($key)) {
					$segments[] = $formatKeyForDisplay($key) . ': ' . $text;
				} else {
					$segments[] = $text;
				}
			}
			if ($segments === []) {
				return null;
			}
			return implode($isAssoc ? ' · ' : ', ', $segments);
		}

		if (is_numeric($value)) {
			return (string) $value;
		}

		if (is_string($value)) {
			$trimmed = trim($value);
			return $trimmed === '' ? null : $trimmed;
		}

		return null;
	};

	$activityTypeLabels = [
		'order_created' => 'Order Created',
		'order_updated' => 'Order Updated',
		'order_number_updated' => 'Order Number Updated',
		'status_updated' => 'Status Updated',
		'status_update' => 'Status Updated',
		'status_changed' => 'Status Updated',
		'items_updated' => 'Items Updated',
		'metadata_updated' => 'Details Updated',
		'note_added' => 'Note Added',
		'customer_updated' => 'Customer Updated',
	];

	$formatActivityLabel = static function ($activity) use ($activityTypeLabels, $formatKeyForDisplay, $formatTimelinePayload, $decodeJsonValue) {
		$rawDescription = data_get($activity, 'description');
		$decodedDescription = $decodeJsonValue($rawDescription);
		if (!is_array($decodedDescription)) {
			$descriptionText = $formatTimelinePayload($rawDescription);
			if ($descriptionText) {
				return $descriptionText;
			}
		}

		$type = strtolower((string) data_get($activity, 'activity_type', ''));
		if ($type !== '' && isset($activityTypeLabels[$type])) {
			return $activityTypeLabels[$type];
		}

		return $type !== '' ? $formatKeyForDisplay($type) : 'Activity';
	};

	$formatActivityNote = static function ($activity) use ($formatTimelinePayload) {
		$newValue = $formatTimelinePayload(data_get($activity, 'new_value'));
		$oldValue = $formatTimelinePayload(data_get($activity, 'old_value'));
		if ($newValue && $oldValue && $newValue !== $oldValue) {
			return $oldValue . ' → ' . $newValue;
		}
		if ($newValue) {
			return $newValue;
		}
		if ($oldValue) {
			return 'Previous: ' . $oldValue;
		}

		$extraFields = ['details', 'note', 'changes', 'metadata', 'description'];
		foreach ($extraFields as $field) {
			$text = $formatTimelinePayload(data_get($activity, $field));
			if ($text) {
				return $text;
			}
		}

		return null;
	};

	$formatActivityAuthor = static function ($activity) {
		$name = trim((string) data_get($activity, 'user_name', ''));
		$role = trim((string) data_get($activity, 'user_role', ''));
		if ($name !== '' && $role !== '') {
			return $name . ' (' . $role . ')';
		}
		if ($name !== '') {
			return $name;
		}
		if ($role !== '') {
			return $role;
		}
		return 'System';
	};

	$prebuiltTimeline = collect(data_get($order, 'timeline', []));
	$activities = collect(data_get($order, 'activities', []));

	$activityTimeline = $activities->map(function ($activity) use ($formatActivityAuthor, $formatActivityLabel, $formatActivityNote, $normalizeTimelineState) {
		return [
			'label' => $formatActivityLabel($activity),
			'author' => $formatActivityAuthor($activity),
			'state' => $normalizeTimelineState(data_get($activity, 'activity_type')),
			'note' => $formatActivityNote($activity),
			'timestamp' => data_get($activity, 'created_at'),
		];
	});

	$timeline = $prebuiltTimeline
		->merge($activityTimeline)
		->map(function ($event) use ($normalizeTimelineState, $formatTimelinePayload) {
			$event['state'] = $normalizeTimelineState(data_get($event, 'state', 'default'));
			$label = $formatTimelinePayload(data_get($event, 'label'));
			if ($label) {
				$event['label'] = $label;
			}
			$note = $formatTimelinePayload(data_get($event, 'note'));
			$event['note'] = $note;
			if (is_string(data_get($event, 'author')) && trim((string) data_get($event, 'author')) === '') {
				$event['author'] = null;
			}
			if (is_string($event['label'] ?? null) && is_string($event['note'] ?? null) && trim($event['label']) === trim($event['note'])) {
				$event['note'] = null;
			}
			return $event;
		})
		->sortByDesc(function ($event) {
			$timestamp = data_get($event, 'timestamp');
			if ($timestamp instanceof \Illuminate\Support\Carbon) {
				return $timestamp->timestamp;
			}
			if ($timestamp instanceof \DateTimeInterface) {
				return $timestamp->getTimestamp();
			}
			if (is_string($timestamp)) {
				try {
					return \Illuminate\Support\Carbon::parse($timestamp)->timestamp;
				} catch (\Throwable $e) {
					return 0;
				}
			}
			return 0;
		})
		->values();

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
	$orderStatusRaw = data_get($order, 'status', 'draft');
	$orderStatusRaw = $orderStatusRaw === 'draft' ? 'new_order' : $orderStatusRaw;
	$currentStatus = strtolower((string) $orderStatusRaw);
	if ($currentStatus === 'new_order') {
		$currentStatus = 'draft';
	}
	$flowIndex = array_search($currentStatus, $statusFlow, true);
	$currentChipModifier = str_replace('_', '-', $currentStatus);
	$currentDisplayStatus = $currentStatus === 'draft' ? 'new_order' : $currentStatus;
	$currentStatusLabel = $statusOptions[$currentDisplayStatus] ?? ucfirst(str_replace('_', ' ', $currentDisplayStatus));
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

	$paymentsSummary = collect(data_get($order, 'payments_summary', []));
	$financialMetadata = data_get($metadata, 'financial', []);
	$orderGrandTotalAmount = (float) ($paymentsSummary->get('grand_total') ?? data_get($order, 'total_amount', $grandTotal ?? 0));
	$paidOverrideRaw = data_get($financialMetadata, 'total_paid_override');
	$balanceOverrideRaw = data_get($financialMetadata, 'balance_due_override');
	$paidOverrideAmount = is_numeric($paidOverrideRaw) ? (float) $paidOverrideRaw : null;
	$balanceOverrideAmount = is_numeric($balanceOverrideRaw) ? (float) $balanceOverrideRaw : null;
	$totalPaidAmount = $paidOverrideAmount ?? (float) ($paymentsSummary->get('total_paid') ?? data_get($order, 'total_paid', 0));
	$balanceDueAmount = $balanceOverrideAmount ?? (float) ($paymentsSummary->get('balance_due') ?? max($orderGrandTotalAmount - $totalPaidAmount, 0));
	if ($paymentStatus !== 'paid') {
		if ($orderGrandTotalAmount > 0 && $balanceDueAmount <= 0.01 && $totalPaidAmount >= max($orderGrandTotalAmount - 0.01, 0)) {
			$paymentStatus = 'paid';
		} elseif ($totalPaidAmount > 0 && $balanceDueAmount > 0.01) {
			$paymentStatus = 'partial';
		}
	}
	$paymentStatusLabel = ucwords(str_replace('_', ' ', $paymentStatus ?: 'pending'));
	$orderCurrencyCode = data_get($order, 'currency', 'PHP');
	$orderCurrencySymbol = $orderCurrencyCode === 'PHP' ? '₱' : ($orderCurrencyCode . ' ');
	$formattedBalanceDue = $orderCurrencySymbol . number_format(max($balanceDueAmount, 0), 2);
	$formattedGrandTotal = $orderCurrencySymbol . number_format(max($orderGrandTotalAmount, 0), 2);
	$paymentSummaryHighlight = [
		'background' => '#f0f9ff',
		'border' => '#0ea5e9',
		'accent' => '#0c4a6e',
		'title' => 'Payment status',
		'message' => 'Review the latest payment activity below.'
	];
	switch ($paymentStatus) {
		case 'paid':
			$paymentSummaryHighlight = [
				'background' => '#ecfdf5',
				'border' => '#34d399',
				'accent' => '#047857',
				'title' => 'Invoice fully paid',
				'message' => 'All balances are cleared. No further action required.'
			];
			break;
		case 'partial':
			$paymentSummaryHighlight = [
				'background' => '#ede9fe',
				'border' => '#a855f7',
				'accent' => '#5b21b6',
				'title' => 'Payment partially received',
				'message' => 'Balance remaining: ' . $formattedBalanceDue . '.'
			];
			break;
		case 'failed':
			$paymentSummaryHighlight = [
				'background' => '#fee2e2',
				'border' => '#f87171',
				'accent' => '#b91c1c',
				'title' => 'Payment failed',
				'message' => 'Payment attempt failed. Reach out to the customer to retry.'
			];
			break;
		case 'refunded':
			$paymentSummaryHighlight = [
				'background' => '#fef3c7',
				'border' => '#f59e0b',
				'accent' => '#92400e',
				'title' => 'Payment refunded',
				'message' => 'Order payment was refunded. Verify remaining balance totals.'
			];
			break;
		default:
			$paymentSummaryHighlight = [
				'background' => '#fef3c7',
				'border' => '#f59e0b',
				'accent' => '#92400e',
				'title' => 'Payment pending',
				'message' => 'Outstanding balance: ' . $formattedBalanceDue . ' of ' . $formattedGrandTotal . '.'
			];
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
				Placed {{ $placedAt }} · {{ $paymentStatusLabel }} payment · {{ ucfirst($fulfillmentStatus ?: 'processing') }} fulfillment
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
				{{ $paymentStatusLabel }} payment
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
						$displayKey = $statusKey === 'draft' ? 'new_order' : $statusKey;
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
							{{ $statusOptions[$displayKey] ?? ucfirst(str_replace('_', ' ', $displayKey)) }}
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
										$addonLookup = collect($addonValues);

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
										// Prefer any customer-saved draft previews (CustomerTemplateCustom)
										$imagesSource = data_get($item, 'preview_images', data_get($item, 'images', []));
										$customerReviewSvg = null;
										$customerReviewBackImage = null;
										try {
											$customerDraft = null;
											// Use the original Eloquent order model when available (`orderModel`)
											if (isset($orderModel) && ($orderModel->id ?? null) && (data_get($item, 'id') || data_get($item, 'order_item_id'))) {
												$orderItemId = data_get($item, 'id', data_get($item, 'order_item_id'));
												$customerDraft = \App\Models\CustomerTemplateCustom::query()
													->where('order_id', $orderModel->id)
													->where('order_item_id', $orderItemId)
													->latest('id')
													->first();
											}

											if (!$customerDraft && isset($order) && ($order?->customer_id ?? null)) {
												// Fallback: find by customer/product/template match
												$customerDraft = \App\Models\CustomerTemplateCustom::query()
													->where('customer_id', $order->customer_id)
													->where('product_id', data_get($item, 'product_id'))
													->latest('id')
													->first();
											}

											if ($customerDraft) {
												$draftImages = data_get($customerDraft, 'preview_images', data_get($customerDraft, 'preview_images', []));
												if (!empty($draftImages)) {
													$imagesSource = $draftImages;
												} elseif (!empty($customerDraft->preview_image ?? null)) {
													$imagesSource = [$customerDraft->preview_image];
												}
											}
											
											// Look for CustomerReview with design_svg (saved from design studio)
											// template_id can come from:
											// 1. item.template_id (from presenter via product.template_id)
											// 2. item.metadata.template_id
											// 3. item.design_metadata.template_id
											// 4. customerDraft.template_id
											$templateId = data_get($item, 'template_id') 
												?? data_get($item, 'metadata.template_id') 
												?? data_get($item, 'design_metadata.template_id')
												?? ($customerDraft->template_id ?? null);
											$customerId = $order?->customer_id ?? ($orderModel->customer_id ?? null);
											
											if ($templateId && $customerId) {
												$customerReview = \App\Models\CustomerReview::query()
													->where('template_id', $templateId)
													->where('customer_id', $customerId)
													->whereNotNull('design_svg')
													->where('design_svg', '!=', '')
													->latest('updated_at')
													->first();
												
												if ($customerReview && !empty($customerReview->design_svg)) {
													$customerReviewSvg = $customerReview->design_svg;
													// Try to get back image from gallery
													if (!empty($imagesSource) && count($imagesSource) > 1) {
														$backImg = $imagesSource[1] ?? null;
														if (is_array($backImg)) {
															$customerReviewBackImage = $backImg['src'] ?? $backImg['url'] ?? null;
														} else {
															$customerReviewBackImage = $backImg;
														}
													}
												}
											}
										} catch (\Throwable $e) {
											// ignore and fall back to item images
										}

										$images = collect($imagesSource)->filter();
										$itemMaterialBuckets = [
											'paper' => [],
											'addon' => [],
											'other' => [],
										];

										$itemRegisterMaterial = function (string $type, string $label, $quantity, $unitPrice, $total) use (&$itemMaterialBuckets) {
											$typeKey = in_array($type, ['paper', 'addon'], true) ? $type : 'other';
											$normalizedLabel = trim(mb_strtolower($label));
											$key = $normalizedLabel !== '' ? $normalizedLabel : uniqid($typeKey . '_');

											if (!isset($itemMaterialBuckets[$typeKey][$key])) {
												$itemMaterialBuckets[$typeKey][$key] = [
													'label' => $label !== '' ? $label : 'Material',
													'quantity' => 0.0,
													'unit_price' => null,
													'total' => 0.0,
												];
											}

											if (is_numeric($quantity)) {
												$itemMaterialBuckets[$typeKey][$key]['quantity'] += (float) $quantity;
											}

											if (is_numeric($unitPrice) && $itemMaterialBuckets[$typeKey][$key]['unit_price'] === null) {
												$itemMaterialBuckets[$typeKey][$key]['unit_price'] = (float) $unitPrice;
											}

											if (is_numeric($total)) {
												$itemMaterialBuckets[$typeKey][$key]['total'] += (float) $total;
											}
										};

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
										// DO NOT CALCULATE THE BASE PRICE OF THE INVITATION
										// if (!$isEnvelope && !$isGiveaway) {
										//     $groupSums['invitations'] += $lineTotal;
										// }

										if ($isEnvelope) {
											$groupSums['envelopes'] += $lineTotal;
										}

										if ($isGiveaway) {
											$groupSums['giveaways'] += $lineTotal;
										}

										// breakdown rows (paper stock and addons) add to group sums only for invitations
										if (!$isEnvelope && !$isGiveaway) {
											foreach ($breakdown as $brow) {
												$lblRaw = (string) data_get($brow, 'label', '');
												$lbl = strtolower($lblRaw);
												$btotal = data_get($brow, 'total');
												if (!is_numeric($btotal)) {
													$bunit = data_get($brow, 'unit_price');
													$bqty = data_get($brow, 'quantity') ?: 1;
													$btotal = is_numeric($bunit) ? ((float) $bunit * (int) $bqty) : 0;
												}
												$btotal = (float) $btotal;

												$matchesPaper = $lbl !== '' && $paperStockValue && str_contains($lbl, strtolower((string) $paperStockValue));
												$matchesAddon = $addonLookup->contains(function ($v) use ($lbl) {
													return $v !== '' && str_contains($lbl, $v);
												});

												if ($matchesPaper) {
													$groupSums['paper_stock'] += $btotal;
												} elseif ($matchesAddon) {
													$groupSums['addons'] += $btotal;
												} else {
													$groupSums['others'] += $btotal;
												}

												$rowQuantityValue = data_get($brow, 'quantity');
												if (!is_numeric($rowQuantityValue) || (float) $rowQuantityValue <= 0) {
													$rowQuantityValue = $quantity ?: null;
												}

												$rowUnitValue = data_get($brow, 'unit_price');
												if ((!is_numeric($rowUnitValue) || (float) $rowUnitValue === 0.0) && is_numeric($rowQuantityValue) && (float) $rowQuantityValue > 0 && $btotal > 0) {
													$rowUnitValue = $btotal / (float) $rowQuantityValue;
												}

												$materialType = $matchesPaper ? 'paper' : ($matchesAddon ? 'addon' : 'other');
												$registerMaterial($materialType, $lblRaw, $rowQuantityValue, $rowUnitValue, $btotal);
												$itemRegisterMaterial($materialType, $lblRaw, $rowQuantityValue, $rowUnitValue, $btotal);
											}

											if ($paperStockValue && empty($itemMaterialBuckets['paper'])) {
												$paperLabel = is_string($paperStockValue) && trim($paperStockValue) !== ''
													? trim((string) $paperStockValue)
													: 'Paper Stock';
												$paperUnitPrice = $extractMoney(data_get($rawOptions, 'paper_stock_price'));
												$paperTotal = is_numeric($paperUnitPrice) ? (float) $paperUnitPrice * max(1, $quantity) : null;
												$registerMaterial('paper', $paperLabel, $quantity, is_numeric($paperUnitPrice) ? (float) $paperUnitPrice : null, $paperTotal);
												$itemRegisterMaterial('paper', $paperLabel, $quantity, is_numeric($paperUnitPrice) ? (float) $paperUnitPrice : null, $paperTotal);
											}

											if (empty($itemMaterialBuckets['addon']) && !empty($addonValues)) {
												$addonFallbacks = [];
												foreach ($rawOptions as $rawKey => $rawValue) {
													if (!str_contains(strtolower((string) $rawKey), 'addon')) {
														continue;
													}

													$normalizedAddons = $normalizeToArray($rawValue);
													if (!$normalizedAddons) {
														$normalizedAddons = $rawValue !== null ? [$rawValue] : [];
													}

													foreach ($normalizedAddons as $addonEntry) {
														$label = trim($formatAddon($addonEntry));
														if ($label === '' || strtolower($label) === 'none') {
															continue;
														}
														$addonFallbacks[] = $label;
													}
												}

												$addonFallbacks = array_values(array_unique($addonFallbacks));
												foreach ($addonFallbacks as $addonLabel) {
													$registerMaterial('addon', $addonLabel, $quantity, null, null);
													$itemRegisterMaterial('addon', $addonLabel, $quantity, null, null);
												}
											}
										}
									@endphp
									<tr>
										<td>
											<div class="item-cell">
												@php
													$detectOrientation = function ($value) {
														$value = is_string($value) ? strtolower(trim($value)) : '';
														if ($value === '') {
															return null;
														}

														if (str_contains($value, 'back') || str_contains($value, 'reverse') || str_contains($value, 'rear')) {
															return 'back';
														}

														if (str_contains($value, 'front') || str_contains($value, 'cover')) {
															return 'front';
														}

														if (str_contains($value, 'inside')) {
															return 'inside';
														}

														if (str_contains($value, 'outside')) {
															return 'outside';
														}

														return null;
													};

													$labelForOrientation = function (?string $orientation) {
														return match ($orientation) {
															'front' => 'Front design',
															'back' => 'Back design',
															'inside' => 'Inside spread',
															'outside' => 'Outside',
															default => null,
														};
													};

													$galleryEntries = collect();
													$gallerySources = [];

													$pushGalleryImage = function ($src, ?string $orientation = null, ?string $label = null) use (&$galleryEntries, &$gallerySources, $detectOrientation, $labelForOrientation) {
														$src = is_string($src) ? trim($src) : '';
														if ($src === '') {
															return;
														}

														$orientation = $orientation ?: $detectOrientation($label);
														$label = $label ?: $labelForOrientation($orientation);

														if (in_array($src, $gallerySources, true)) {
															return;
														}

														$entry = array_filter([
															'src' => $src,
															'orientation' => $orientation,
															'label' => $label,
														], function ($value) {
															return $value !== null && $value !== '';
														});

														$galleryEntries->push($entry);
														$gallerySources[] = $src;
													};

													$images->each(function ($img, $imageKey) use ($detectOrientation, $pushGalleryImage) {
														$keyOrientation = $detectOrientation($imageKey);

														if (is_string($img)) {
															$pushGalleryImage($img, $keyOrientation);
															return;
														}

														if (is_object($img)) {
															if (method_exists($img, 'toArray')) {
																$img = $img->toArray();
															} elseif (method_exists($img, '__toString')) {
																$pushGalleryImage((string) $img, $keyOrientation);
																return;
															} else {
																$img = (array) $img;
															}
														}

														if (!is_array($img)) {
															return;
														}

														$primarySrc = $img['url'] ?? $img['src'] ?? $img['preview'] ?? null;
														$primaryOrientation = $detectOrientation($img['orientation'] ?? $img['side'] ?? $img['page'] ?? $img['label'] ?? null) ?? $keyOrientation;
														$primaryLabel = $img['label'] ?? $img['title'] ?? $img['description'] ?? null;
														$pushGalleryImage($primarySrc, $primaryOrientation, $primaryLabel);

														foreach ($img as $nestedKey => $nestedValue) {
															if (in_array($nestedKey, ['url', 'src', 'preview', 'label', 'title', 'description', 'orientation', 'side', 'page'], true)) {
																continue;
															}

															if (is_string($nestedValue)) {
																$pushGalleryImage($nestedValue, $detectOrientation($nestedKey));
																continue;
															}

															if (is_object($nestedValue)) {
																if (method_exists($nestedValue, 'toArray')) {
																	$nestedValue = $nestedValue->toArray();
																} else {
																	$nestedValue = (array) $nestedValue;
																}
															}

															if (is_array($nestedValue)) {
																$nestedSrc = $nestedValue['url'] ?? $nestedValue['src'] ?? $nestedValue['preview'] ?? null;
																$nestedOrientation = $detectOrientation($nestedValue['orientation'] ?? $nestedKey) ?? $detectOrientation($nestedKey);
																$nestedLabel = $nestedValue['label'] ?? $nestedValue['title'] ?? null;
																$pushGalleryImage($nestedSrc, $nestedOrientation, $nestedLabel);
															}
														}
													});

													if ($galleryEntries->isEmpty()) {
														$fallbackImage = data_get($item, 'image');
														if (is_string($fallbackImage) && trim($fallbackImage) !== '') {
															$pushGalleryImage($fallbackImage);
														}
													}

													$gallery = $galleryEntries->values();
													// Prepare a client-friendly gallery with normalized URLs so the
													// preview JS doesn't request relative paths that 404.
													$galleryForClient = $gallery->map(function ($entry) {
														$src = is_array($entry) ? ($entry['src'] ?? '') : (is_string($entry) ? $entry : '');
														$src = trim((string) $src);
														if ($src === '') {
															return null;
														}

														if (preg_match('/^https?:\/\//i', $src)) {
															$url = $src;
														} elseif (preg_match('/^\/?storage\//i', $src)) {
															$url = asset(ltrim($src, '/'));
														} elseif (str_starts_with($src, '/')) {
															$url = url($src);
														} else {
															$url = asset('storage/' . ltrim($src, '/'));
														}

														return array_filter([
															'src' => $url,
															'orientation' => $entry['orientation'] ?? null,
															'label' => $entry['label'] ?? null,
														], function ($v) { return $v !== null && $v !== ''; });
													})->filter()->values();
													$itemMaterialsList = collect($itemMaterialBuckets)
														->flatMap(function ($rows, $type) {
															return collect($rows)->map(function ($row) use ($type) {
																return [
																	'type' => $type,
																	'label' => $row['label'] ?? 'Material',
																	'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : null,
																	'unit_price' => isset($row['unit_price']) ? (float) $row['unit_price'] : null,
																	'total' => isset($row['total']) ? (float) $row['total'] : null,
																];
															});
														})
														->filter(function ($row) {
															return (($row['quantity'] ?? 0) > 0) || (($row['total'] ?? 0) > 0) || (($row['unit_price'] ?? 0) > 0);
														})
														->values();

													$primaryImageEntry = $gallery->first();
													$primaryImage = is_array($primaryImageEntry) ? ($primaryImageEntry['src'] ?? null) : (is_string($primaryImageEntry) ? $primaryImageEntry : null);
													$primaryImageLabel = is_array($primaryImageEntry) ? ($primaryImageEntry['label'] ?? null) : null;
													$previewTitle = data_get($item, 'name', 'Custom product');

													// Normalize image URL for browser consumption. Accept absolute URLs, storage paths,
													// and relative paths. Fallback to placeholder when missing.
													$primaryImageUrl = null;
													if (!empty($primaryImage)) {
														$trimmed = trim((string) $primaryImage);
														if (preg_match('/^https?:\/\//i', $trimmed)) {
															$primaryImageUrl = $trimmed;
														} elseif (preg_match('/^\/?storage\//i', $trimmed)) {
															$primaryImageUrl = asset(ltrim($trimmed, '/'));
														} elseif (str_starts_with($trimmed, '/')) {
															$primaryImageUrl = url($trimmed);
														} else {
															// Common case: stored in storage/app/public or relative path like "customerimages/..."
															$primaryImageUrl = asset('storage/' . ltrim($trimmed, '/'));
														}
													} else {
														$primaryImageUrl = asset('images/placeholder.png');
													}
												@endphp
												@if($gallery->isNotEmpty())
													<button
														type="button"
														class="item-cell__thumb-button"
														data-preview-trigger
														data-preview-title="{{ $previewTitle }}"
														data-preview-gallery='@json($galleryForClient)'
														data-preview-materials='@json($itemMaterialsList)'
														aria-label="View artwork preview for {{ $previewTitle }}"
													>
														<img src="{{ $primaryImageUrl }}" alt="{{ $primaryImageLabel ? $previewTitle . ' ' . strtolower($primaryImageLabel) : $previewTitle . ' preview' }}" class="item-cell__thumb">
													</button>
												@endif
												@if(!empty($customerReviewSvg))
													<div class="mt-2">
														<div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Edited Template</div>
														<button
															type="button"
															class="item-cell__svg-button js-admin-svg-preview-trigger"
															data-svg-content="{{ base64_encode($customerReviewSvg) }}"
															data-back-image="{{ $customerReviewBackImage ?? '' }}"
															data-preview-title="{{ $previewTitle }} - Edited Design"
															aria-label="View edited template design for {{ $previewTitle }}"
															style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px; background: #f8fafc; cursor: pointer; display: inline-block; transition: all 0.2s;"
															onmouseover="this.style.boxShadow='0 0 0 2px #a6b7ff'"
															onmouseout="this.style.boxShadow='none'"
														>
															<div class="svg-thumb-container" style="width: 60px; height: 60px; overflow: hidden; pointer-events: none;">
																{!! $customerReviewSvg !!}
															</div>
														</button>
														<div class="text-xs text-gray-400 mt-1">Click to view front & back</div>
													</div>
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

			@php
				// Flatten grouped material requirements for presentation.
				$materialsList = collect($materialRequirements ?? [])
					->flatMap(function ($rows, $type) {
						return collect($rows)->map(function ($row) use ($type) {
							$quantityValue = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
							$unitPriceValue = isset($row['unit_price']) ? (float) $row['unit_price'] : null;
							$totalValue = isset($row['total']) ? (float) $row['total'] : null;

							return [
								'type' => $type,
								'label' => $row['label'] ?? 'Material',
								'quantity' => $quantityValue,
								'unit_price' => $unitPriceValue,
								'total' => $totalValue,
							];
						});
					})
					->filter(function ($row) {
						return ($row['quantity'] ?? 0) > 0 || ($row['total'] ?? 0) > 0;
					})
					->sortByDesc(function ($row) {
						return $row['quantity'] ?? 0;
					})
					->values();
			@endphp

			@if($materialsList->isNotEmpty())
				<article class="ordersummary-card">
					<header class="ordersummary-card__header">
						<h2>Materials needed for production</h2>
						<p class="ordersummary-card__meta">{{ $materialsList->count() }} {{ \Illuminate\Support\Str::plural('material', $materialsList->count()) }} tracked</p>
					</header>
					<div class="materials-grid">
						@foreach($materialsList as $material)
							@php
								$typeLabel = 'Other';
								if ($material['type'] === 'paper') {
									$typeLabel = 'Paper Stock';
								} elseif ($material['type'] === 'addon') {
									$typeLabel = 'Add-on';
								}

								$quantityValue = $material['quantity'] ?? 0;
								$isWholeNumber = $quantityValue > 0 && abs($quantityValue - round($quantityValue)) < 0.01;
								$quantityText = $quantityValue > 0
									? number_format($quantityValue, $isWholeNumber ? 0 : 2)
									: '—';

								$unitPriceValue = $material['unit_price'];
								$totalValue = $material['total'];
							@endphp
							<div class="material-card material-card--{{ $material['type'] }}">
								<span class="material-card__type">{{ $typeLabel }}</span>
								<h3 class="material-card__title">{{ $material['label'] }}</h3>
								<p class="material-card__quantity"><strong>{{ $quantityText }}</strong> units needed</p>
								@if(is_numeric($totalValue) && $totalValue > 0)
									<p class="material-card__cost">Total cost · ₱{{ number_format($totalValue, 2) }}</p>
								@elseif(is_numeric($unitPriceValue) && $unitPriceValue > 0)
									<p class="material-card__cost">Unit cost · ₱{{ number_format($unitPriceValue, 2) }}</p>
								@else
									<p class="material-card__cost">Cost pending</p>
								@endif

							</div>
								@endforeach
							</div>
						</article>
					@endif

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

			@php
				// Get materials that have been deducted from inventory for this order
				$deductedMaterials = \App\Models\ProductMaterial::where('order_id', $orderModel->id)
					->where('source_type', 'custom')
					->where('quantity_used', '>', 0)
					->with('material')
					->get()
					->map(function ($pm) {
						return [
							'material_name' => $pm->material->material_name ?? 'Unknown Material',
							'quantity_used' => $pm->quantity_used,
							'unit' => $pm->unit,
							'material_id' => $pm->material_id,
						];
					});
			@endphp

			@if($deductedMaterials->isNotEmpty())
				<article class="ordersummary-card">
					<header class="ordersummary-card__header">
						<h2>Materials Used & Deducted</h2>
						<p class="ordersummary-card__meta">{{ $deductedMaterials->count() }} {{ \Illuminate\Support\Str::plural('material', $deductedMaterials->count()) }} deducted from inventory</p>
						<div class="ordersummary-card__actions">
							<button type="button" class="btn btn-primary btn-sm" onclick="deductMaterials({{ $orderModel->id }})">
								<i class="fi fi-rr-minus-circle" aria-hidden="true"></i> Deduct Materials Again
							</button>
						</div>
					</header>
					<div class="materials-grid">
						@foreach($deductedMaterials as $material)
							<div class="material-card material-card--deducted">
								<span class="material-card__type">Deducted</span>
								<h3 class="material-card__title">{{ $material['material_name'] }}</h3>
								<p class="material-card__quantity"><strong>{{ number_format($material['quantity_used'], 2) }}</strong> {{ $material['unit'] }} used</p>
							</div>
						@endforeach
					</div>
				</article>
			@else
				<article class="ordersummary-card">
					<header class="ordersummary-card__header">
						<h2>Materials Used & Deducted</h2>
						<p class="ordersummary-card__meta">No materials have been deducted yet</p>
						<div class="ordersummary-card__actions">
							<button type="button" class="btn btn-primary btn-sm" onclick="deductMaterials({{ $orderModel->id }})">
								<i class="fi fi-rr-minus-circle" aria-hidden="true"></i> Deduct Materials
							</button>
						</div>
					</header>
					<div style="padding: 20px; text-align: center; color: #6b7280;">
						<p style="margin: 0; font-size: 16px;">Materials will be deducted from inventory when this order is finalized.</p>
					</div>
				</article>
			@endif

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
				<div class="payment-status-highlight" data-payment-highlight style="background: {{ $paymentSummaryHighlight['background'] }}; border: 2px solid {{ $paymentSummaryHighlight['border'] }}; border-radius: 8px; padding: 16px; margin-bottom: 16px; text-align: center;">
					<div data-payment-highlight-title style="font-size: 14px; font-weight: 600; color: {{ $paymentSummaryHighlight['accent'] }}; margin-bottom: 8px;">{{ $paymentSummaryHighlight['title'] }}</div>
					<span class="status-chip {{ 'status-chip--' . $paymentStatus }}" data-payment-status-chip style="font-size: 16px; padding: 8px 16px;">
						{{ $paymentStatusLabel }}
					</span>
					<p data-payment-highlight-message style="margin: 8px 0 0; font-size: 13px; color: {{ $paymentSummaryHighlight['accent'] }};">{{ $paymentSummaryHighlight['message'] }}</p>
				</div>
				@php
					$payments = collect(data_get($order, 'payments', []));
					$paymentsSummary = isset($paymentsSummary) ? $paymentsSummary : collect(data_get($order, 'payments_summary', []));
					$currencyCode = data_get($order, 'currency', 'PHP');
					$currencySymbol = $currencyCode === 'PHP' ? '₱' : ($currencyCode . ' ');
					$formatCurrency = function ($value) use ($currencySymbol) {
						$numeric = is_numeric($value) ? (float) $value : 0.0;
						return $currencySymbol . number_format($numeric, 2);
					};
					$orderGrandTotal = isset($orderGrandTotalAmount) ? $orderGrandTotalAmount : (float) ($paymentsSummary->get('grand_total') ?? ($grandTotal ?? 0));
					$totalPaid = isset($totalPaidAmount) ? $totalPaidAmount : (float) ($paymentsSummary->get('total_paid') ?? $payments->reduce(function ($carry, $paymentRow) {
						$status = strtolower((string) data_get($paymentRow, 'status', 'pending'));
						if ($status === 'paid') {
							return $carry + (float) data_get($paymentRow, 'amount', 0);
						}
						return $carry;
					}, 0.0));
					$balanceDue = isset($balanceDueAmount) ? $balanceDueAmount : (float) ($paymentsSummary->get('balance_due') ?? max($orderGrandTotal - $totalPaid, 0));
					$latestPaymentAtRaw = $paymentsSummary->get('latest_payment_at');
					$latestPaymentAt = null;
					if ($latestPaymentAtRaw) {
						$latestPaymentAt = isset($formatDateTime) && is_callable($formatDateTime)
							? $formatDateTime($latestPaymentAtRaw)
							: (is_string($latestPaymentAtRaw) ? $latestPaymentAtRaw : null);
					}
					$paymentMethodDisplay = data_get($order, 'payment_method');
					if (!$paymentMethodDisplay && $payments->isNotEmpty()) {
						$paymentMethodDisplay = data_get($payments->first(), 'method');
					}
					$primaryProvider = $payments->isNotEmpty() ? data_get($payments->first(), 'provider') : null;
				@endphp
				<div class="payment-summary-grid">
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Total invoiced</span>
						<span class="payment-summary-grid__value" data-payment-total="grand">{{ $formatCurrency($orderGrandTotal) }}</span>
					</div>
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Total paid</span>
						<span class="payment-summary-grid__value" data-payment-total="paid">{{ $formatCurrency($totalPaid) }}</span>
					</div>
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Balance remaining</span>
						<span class="payment-summary-grid__value" data-payment-total="balance">{{ $formatCurrency($balanceDue) }}</span>
					</div>
					@if($paymentMethodDisplay)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Payment method</span>
							<span class="payment-summary-grid__value">{{ mb_strtoupper($paymentMethodDisplay) }}</span>
						</div>
					@endif
					@if($primaryProvider)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Provider</span>
							<span class="payment-summary-grid__value">{{ ucfirst($primaryProvider) }}</span>
						</div>
					@endif
					@if($latestPaymentAt)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Latest payment</span>
							<span class="payment-summary-grid__value">{{ $latestPaymentAt }}</span>
						</div>
					@endif
				</div>
				@if($orderGrandTotal > 0)
					@if($totalPaid <= 0)
						<div class="payment-alert payment-alert--pending">No payments recorded yet.</div>
					@elseif($balanceDue > 0.01)
						<div class="payment-alert payment-alert--balance">Remaining balance of {{ $formatCurrency($balanceDue) }} is pending.</div>
					@else
						<div class="payment-alert payment-alert--clear">Invoice fully paid.</div>
					@endif
				@endif
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
				@if($payments->isNotEmpty())
					<div class="payment-history">
						<h3>Payment history</h3>
						@foreach($payments as $payment)
							@php
								$paymentAmount = (float) data_get($payment, 'amount', 0);
								$paymentStatus = strtolower((string) data_get($payment, 'status', 'pending'));
								$statusClass = in_array($paymentStatus, ['paid', 'pending', 'partial', 'failed', 'refunded'], true) ? $paymentStatus : 'pending';
								$paymentDateRaw = data_get($payment, 'recorded_at') ?? data_get($payment, 'created_at');
								$paymentDate = null;
								if ($paymentDateRaw) {
									$paymentDate = isset($formatDateTime) && is_callable($formatDateTime)
										? $formatDateTime($paymentDateRaw)
										: (is_string($paymentDateRaw) ? $paymentDateRaw : null);
								}
								$recordedBy = data_get($payment, 'recorded_by.name');
								$method = data_get($payment, 'method');
								$provider = data_get($payment, 'provider');
								$reference = data_get($payment, 'reference');
								$origin = data_get($payment, 'origin');
								$notes = data_get($payment, 'notes');
								$metaPieces = collect([
									$paymentDate ? 'Recorded ' . $paymentDate : null,
									$recordedBy ? 'By ' . $recordedBy : null,
									$method ? 'Method: ' . mb_strtoupper($method) : null,
									$provider ? 'Provider: ' . ucfirst($provider) : null,
									$reference ? 'Ref: ' . $reference : null,
									$origin === 'metadata' ? 'Imported record' : null,
									$notes ? 'Note: ' . $notes : null,
								])->filter()->implode(' · ');
							@endphp
							<div class="payment-history__row">
								<div>
									<div class="payment-history__amount">{{ $formatCurrency($paymentAmount) }}</div>
									@if($metaPieces !== '')
										<div class="payment-history__meta">{{ $metaPieces }}</div>
									@endif
								</div>
								<span class="payment-history__status payment-history__status--{{ $statusClass }}">{{ ucfirst($paymentStatus) }}</span>
							</div>
						@endforeach
					</div>
				@endif
			</section>

		</aside>
	</div>
</main>

<div class="ordersummary-preview-modal" data-preview-modal hidden aria-hidden="true" tabindex="-1">
	<div class="ordersummary-preview-backdrop" data-preview-close></div>
	<div class="ordersummary-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="ordersummaryPreviewTitle">
		<header class="ordersummary-preview-header">
			<div>
				<h2 id="ordersummaryPreviewTitle">Artwork preview</h2>
				<p class="ordersummary-preview-meta"><span data-preview-title></span><span data-preview-label></span><span data-preview-counter></span></p>
			</div>
			<button type="button" class="ordersummary-preview-close" data-preview-close aria-label="Close preview">
				<span aria-hidden="true">&times;</span>
			</button>
		</header>
		<div class="ordersummary-preview-body">
			<img data-preview-image src="" alt="">
		</div>
		<div class="ordersummary-preview-details" data-preview-materials-wrapper hidden>
			<h3>Materials used</h3>
			<ul class="ordersummary-preview-materials" data-preview-materials></ul>
		</div>
		<div class="ordersummary-preview-actions">
			<button type="button" class="btn btn-secondary" data-preview-prev disabled>
				<i class="fi fi-rr-angle-small-left" aria-hidden="true"></i> Previous
			</button>
			<a href="#" class="btn btn-primary" data-preview-download rel="noopener" download>
				<i class="fi fi-rr-download" aria-hidden="true"></i> Download
			</a>
			<button type="button" class="btn btn-secondary" data-preview-next disabled>
				Next <i class="fi fi-rr-angle-small-right" aria-hidden="true"></i>
			</button>
		</div>
	</div>
</div>

<div class="ordersummary-toast" data-toast role="status" aria-live="polite" hidden></div>

<script src="{{ asset('js/admin/ordersummary.js') }}"></script>
<script>
(function () {
	const STORAGE_KEY = 'inkwiseOrderPaymentUpdate';
	const CONSUMER_ID = 'order-summary';
	const KNOWN_CONSUMERS = ['orders-table', 'order-summary'];
	let orderId = null;
	try {
		const root = document.querySelector('.ordersummary-admin-page');
		if (root) {
			orderId = root.getAttribute('data-order-id') || null;
		}
	} catch (error) {
		orderId = null;
	}

	if (!orderId) {
		return;
	}

	const parsePayload = function (raw) {
		if (!raw || typeof raw !== 'string') {
			return null;
		}
		try {
			return JSON.parse(raw);
		} catch (error) {
			return null;
		}
	};

	const persistPayload = function (payload) {
		const consumed = Array.isArray(payload.consumedBy) ? payload.consumedBy.slice() : [];
		if (!consumed.includes(CONSUMER_ID)) {
			consumed.push(CONSUMER_ID);
		}
		payload.consumedBy = consumed;
		const allConsumed = KNOWN_CONSUMERS.every(function (id) {
			return consumed.includes(id);
		});
		try {
			if (allConsumed) {
				window.localStorage.removeItem(STORAGE_KEY);
			} else {
				window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
			}
		} catch (error) {
			console.warn('Unable to persist payment sync payload for order summary.', error);
		}
	};

	const applyPayload = function (payload, shouldReload) {
		if (!payload || String(payload.orderId) !== String(orderId)) {
			return false;
		}
		persistPayload(payload);
		if (shouldReload) {
			window.location.reload();
		}
		return true;
	};

	let initialRaw = null;
	try {
		initialRaw = window.localStorage.getItem(STORAGE_KEY);
	} catch (error) {
		initialRaw = null;
	}
	const initialPayload = parsePayload(initialRaw);
	if (initialPayload) {
		applyPayload(initialPayload, false);
	}

	window.addEventListener('storage', function (event) {
		if (event.storageArea !== window.localStorage) {
			return;
		}
		if (event.key !== STORAGE_KEY) {
			return;
		}
		const payload = parsePayload(event.newValue);
		if (!payload) {
			return;
		}
		applyPayload(payload, true);
	});
})();

document.addEventListener('DOMContentLoaded', function () {
	const modal = document.querySelector('[data-preview-modal]');
	if (!modal) {
		return;
	}

	const imageEl = modal.querySelector('[data-preview-image]');
	const titleEl = modal.querySelector('[data-preview-title]');
	const labelEl = modal.querySelector('[data-preview-label]');
	const counterEl = modal.querySelector('[data-preview-counter]');
	const downloadEl = modal.querySelector('[data-preview-download]');
	const prevBtn = modal.querySelector('[data-preview-prev]');
	const nextBtn = modal.querySelector('[data-preview-next]');
	const closeEls = modal.querySelectorAll('[data-preview-close]');
	const materialsWrap = modal.querySelector('[data-preview-materials-wrapper]');
	const materialsListEl = modal.querySelector('[data-preview-materials]');
	const body = document.body;

	const orientationLabels = {
		front: 'Front design',
		back: 'Back design',
		inside: 'Inside spread',
		outside: 'Outside',
	};

	const materialTypeLabels = {
		paper: 'Paper Stock',
		addon: 'Add-on',
		other: 'Other',
	};

	const labelFromOrientation = function (value) {
		if (!value) {
			return '';
		}
		const key = value.toString().toLowerCase();
		return orientationLabels[key] || value.toString();
	};

	const normalizeGallery = function (input) {
		if (!Array.isArray(input)) {
			return [];
		}

		const seen = new Set();
		const normalized = [];

		input.forEach(function (entry) {
			let src = '';
			let orientation = '';
			let label = '';

			if (typeof entry === 'string') {
				src = entry;
			} else if (entry && typeof entry === 'object') {
				src = entry.src || entry.url || entry.preview || '';
				orientation = entry.orientation || entry.side || entry.page || '';
				label = entry.label || entry.title || entry.description || '';
			}

			src = (src || '').toString().trim();
			if (!src || seen.has(src)) {
				return;
			}

			const orientationKey = orientation ? orientation.toString().toLowerCase() : '';
			const normalizedLabel = label ? label.toString().trim() : '';

			seen.add(src);
			normalized.push({
				src: src,
				orientation: orientationKey || null,
				label: normalizedLabel || labelFromOrientation(orientationKey),
			});
		});

		return normalized;
	};

	const normalizeMaterials = function (input) {
		if (!Array.isArray(input)) {
			return [];
		}

		return input.map(function (entry) {
			if (!entry || typeof entry !== 'object') {
				return null;
			}

			const typeRaw = entry.type;
			const type = typeof typeRaw === 'string' ? typeRaw.toLowerCase() : 'other';
			const label = typeof entry.label === 'string' && entry.label.trim() !== '' ? entry.label.trim() : 'Material';
			const quantityValue = Number.isFinite(entry.quantity) ? entry.quantity : parseFloat(entry.quantity);
			const unitPriceValue = Number.isFinite(entry.unit_price) ? entry.unit_price : parseFloat(entry.unit_price);
			const totalValue = Number.isFinite(entry.total) ? entry.total : parseFloat(entry.total);

			return {
				type: materialTypeLabels[type] ? type : 'other',
				label: label,
				quantity: Number.isFinite(quantityValue) ? quantityValue : null,
				unitPrice: Number.isFinite(unitPriceValue) ? unitPriceValue : null,
				total: Number.isFinite(totalValue) ? totalValue : null,
			};
		}).filter(Boolean);
	};

	const formatQuantity = function (value) {
		if (typeof value !== 'number' || !isFinite(value) || value <= 0) {
			return '';
		}
		const rounded = Math.round(value);
		if (Math.abs(rounded - value) < 0.01) {
			return String(rounded);
		}
		return value.toFixed(2);
	};

	const formatCurrency = function (value) {
		if (typeof value !== 'number' || !isFinite(value) || value <= 0) {
			return '';
		}
		return '₱' + value.toLocaleString('en-PH', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});
	};

	const renderMaterials = function () {
		if (!materialsListEl || !materialsWrap) {
			return;
		}

		materialsListEl.innerHTML = '';

		if (!state.materials.length) {
			materialsWrap.setAttribute('hidden', 'hidden');
			return;
		}

		materialsWrap.removeAttribute('hidden');
		const fragment = document.createDocumentFragment();

		state.materials.forEach(function (material) {
			const typeLabel = materialTypeLabels[material.type] || materialTypeLabels.other;
			const quantityText = formatQuantity(material.quantity);
			const totalText = formatCurrency(material.total);
			const unitText = formatCurrency(material.unitPrice);

			const segments = [typeLabel + ' · ' + material.label];
			if (quantityText) {
				segments.push(quantityText + ' units');
			}
			if (totalText) {
				segments.push(totalText);
			} else if (unitText) {
				segments.push('Unit ' + unitText);
			}

			const item = document.createElement('li');
			item.textContent = segments.join(' – ');
			fragment.appendChild(item);
		});

		materialsListEl.appendChild(fragment);
	};

	const state = {
		images: [],
		index: 0,
		title: 'Order preview',
		materials: []
	};

	const sanitizeFileName = function (value) {
		const safeBase = (value || 'preview')
			.toString()
			.trim()
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-');
		return safeBase.replace(/^-+|-+$/g, '') || 'preview';
	};

	const updatePreview = function () {
		if (!state.images.length) {
			return;
		}

		const current = state.images[state.index];
		const currentSrc = current.src;
		const currentLabel = current.label || labelFromOrientation(current.orientation);
		imageEl.src = currentSrc;
		const baseAlt = state.title || 'Order preview image';
		imageEl.alt = currentLabel ? baseAlt + ' – ' + currentLabel : baseAlt + ' ' + (state.index + 1);

		if (titleEl) {
			titleEl.textContent = state.title;
		}

		if (labelEl) {
			labelEl.textContent = currentLabel || '';
		}

		if (counterEl) {
			counterEl.textContent = state.images.length > 1
				? ' (' + (state.index + 1) + ' of ' + state.images.length + ')'
				: '';
		}

		if (downloadEl) {
			downloadEl.href = currentSrc;
			const baseName = sanitizeFileName(state.title);
			const extensionMatch = (currentSrc || '').split('?')[0].split('.').pop();
			const normalizedExt = extensionMatch ? extensionMatch.toLowerCase() : null;
			const extension = normalizedExt && normalizedExt.length <= 5 ? normalizedExt : 'png';
			const labelSlug = sanitizeFileName(currentLabel);
			const nameParts = [baseName, state.index + 1];
			if (labelSlug && labelSlug !== 'preview') {
				nameParts.push(labelSlug);
			}
			downloadEl.setAttribute('download', nameParts.join('-') + '.' + extension);
		}

		if (prevBtn) {
			prevBtn.disabled = state.index === 0;
		}

		if (nextBtn) {
			nextBtn.disabled = state.index >= state.images.length - 1;
		}

		renderMaterials();
	};

	const openPreview = function (images, title, materials) {
		const normalizedImages = normalizeGallery(images);
		if (!normalizedImages.length) {
			return;
		}

		state.images = normalizedImages;
		state.index = 0;
		state.title = title || 'Order preview';
		state.materials = normalizeMaterials(materials);

		modal.removeAttribute('hidden');
		modal.setAttribute('aria-hidden', 'false');
		body.classList.add('ordersummary-modal-open');
		updatePreview();
		try {
			modal.focus({ preventScroll: true });
		} catch (error) {
			modal.focus();
		}
	};

	const closePreview = function () {
		modal.setAttribute('hidden', 'hidden');
		modal.setAttribute('aria-hidden', 'true');
		body.classList.remove('ordersummary-modal-open');
		state.images = [];
		state.index = 0;
		state.materials = [];
		if (labelEl) {
			labelEl.textContent = '';
		}
		if (materialsListEl) {
			materialsListEl.innerHTML = '';
		}
		if (materialsWrap) {
			materialsWrap.setAttribute('hidden', 'hidden');
		}
	};

	document.querySelectorAll('[data-preview-trigger]').forEach(function (trigger) {
		trigger.addEventListener('click', function () {
			try {
				const galleryRaw = trigger.getAttribute('data-preview-gallery') || '[]';
				const materialsRaw = trigger.getAttribute('data-preview-materials') || '[]';
				const galleryParsed = JSON.parse(galleryRaw);
				const materialsParsed = JSON.parse(materialsRaw);
				openPreview(galleryParsed, trigger.getAttribute('data-preview-title'), materialsParsed);
			} catch (error) {
				console.error('Unable to open preview', error);
			}
		});
	});

	if (prevBtn) {
		prevBtn.addEventListener('click', function () {
			if (state.index > 0) {
				state.index -= 1;
				updatePreview();
			}
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', function () {
			if (state.index < state.images.length - 1) {
				state.index += 1;
				updatePreview();
			}
		});
	}

	closeEls.forEach(function (el) {
		el.addEventListener('click', closePreview);
	});

	modal.addEventListener('click', function (event) {
		if (event.target === modal || event.target.classList.contains('ordersummary-preview-backdrop')) {
			closePreview();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (modal.hasAttribute('hidden')) {
			return;
		}

		if (event.key === 'Escape') {
			closePreview();
			return;
		}

		if (event.key === 'ArrowLeft' && state.index > 0) {
			state.index -= 1;
			updatePreview();
		} else if (event.key === 'ArrowRight' && state.index < state.images.length - 1) {
			state.index += 1;
			updatePreview();
		}
	});
});
</script>

<style>
/* SVG Thumbnail Styles */
.svg-thumb-container svg {
	width: 100%;
	height: 100%;
	max-width: 60px;
	max-height: 60px;
	object-fit: contain;
}

.item-cell__svg-button:hover {
	border-color: #a6b7ff !important;
}

/* Admin SVG Preview Modal */
.admin-svg-modal {
	position: fixed;
	inset: 0;
	z-index: 9999;
	display: none;
	align-items: center;
	justify-content: center;
	background: rgba(0, 0, 0, 0.8);
	padding: 1rem;
}

.admin-svg-modal.active {
	display: flex;
}

.admin-svg-modal__frame {
	position: relative;
	max-width: 900px;
	width: 100%;
	max-height: 90vh;
	background: #fff;
	border-radius: 16px;
	box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
	overflow: hidden;
	display: flex;
	flex-direction: column;
}

.admin-svg-modal__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 1rem 1.5rem;
	border-bottom: 1px solid #e5e7eb;
}

.admin-svg-modal__header h3 {
	margin: 0;
	font-size: 1.125rem;
	font-weight: 600;
	color: #111827;
}

.admin-svg-modal__view-label {
	font-size: 0.75rem;
	font-weight: 600;
	color: #7c3aed;
	background: #ede9fe;
	padding: 0.25rem 0.75rem;
	border-radius: 9999px;
	margin-left: 0.75rem;
}

.admin-svg-modal__close {
	width: 2.5rem;
	height: 2.5rem;
	border-radius: 50%;
	background: #f3f4f6;
	border: none;
	cursor: pointer;
	font-size: 1.5rem;
	line-height: 1;
	color: #6b7280;
	transition: all 0.2s;
}

.admin-svg-modal__close:hover {
	background: #e5e7eb;
	color: #111827;
}

.admin-svg-modal__body {
	flex: 1;
	overflow: auto;
	padding: 1.5rem;
	background: #f8fafc;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	min-height: 400px;
}

.admin-svg-modal__content {
	display: flex;
	align-items: center;
	justify-content: center;
}

.admin-svg-modal__content svg {
	max-width: 100%;
	max-height: 70vh;
	width: auto;
	height: auto;
}

.admin-svg-modal__content img {
	max-width: 100%;
	max-height: 70vh;
	width: auto;
	height: auto;
	object-fit: contain;
	border-radius: 8px;
}

.admin-svg-modal__nav {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	width: 2.5rem;
	height: 2.5rem;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.9);
	border: none;
	cursor: pointer;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #374151;
	transition: all 0.2s;
	z-index: 10;
}

.admin-svg-modal__nav:hover {
	background: #fff;
	box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.admin-svg-modal__nav--prev {
	left: 0.5rem;
}

.admin-svg-modal__nav--next {
	right: 0.5rem;
}

.admin-svg-modal__nav.hidden {
	display: none;
}
</style>

<script>
// Admin SVG Preview Modal for edited templates
(function() {
	// Create modal structure
	const modal = document.createElement('div');
	modal.className = 'admin-svg-modal';
	modal.id = 'admin-svg-preview-modal';
	
	modal.innerHTML = `
		<div class="admin-svg-modal__frame">
			<div class="admin-svg-modal__header">
				<div style="display: flex; align-items: center;">
					<h3 id="admin-svg-modal-title">Edited Design</h3>
					<span class="admin-svg-modal__view-label" id="admin-svg-view-label">Front</span>
				</div>
				<button type="button" class="admin-svg-modal__close" id="admin-svg-modal-close">&times;</button>
			</div>
			<div class="admin-svg-modal__body">
				<button type="button" class="admin-svg-modal__nav admin-svg-modal__nav--prev hidden" id="admin-svg-prev">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
					</svg>
				</button>
				<div class="admin-svg-modal__content" id="admin-svg-content"></div>
				<button type="button" class="admin-svg-modal__nav admin-svg-modal__nav--next hidden" id="admin-svg-next">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
					</svg>
				</button>
			</div>
		</div>
	`;
	
	document.body.appendChild(modal);
	
	const contentEl = document.getElementById('admin-svg-content');
	const titleEl = document.getElementById('admin-svg-modal-title');
	const viewLabelEl = document.getElementById('admin-svg-view-label');
	const closeBtn = document.getElementById('admin-svg-modal-close');
	const prevBtn = document.getElementById('admin-svg-prev');
	const nextBtn = document.getElementById('admin-svg-next');
	
	let state = {
		currentView: 0, // 0 = front (SVG), 1 = back (image)
		svgContent: '',
		backImageUrl: ''
	};
	
	function updateView() {
		if (state.currentView === 0) {
			contentEl.innerHTML = state.svgContent;
			viewLabelEl.textContent = 'Front';
		} else {
			if (state.backImageUrl) {
				contentEl.innerHTML = `<img src="${state.backImageUrl}" alt="Back view" />`;
			} else {
				contentEl.innerHTML = '<div style="color: #9ca3af; text-align: center; padding: 2rem;">Back view not available</div>';
			}
			viewLabelEl.textContent = 'Back';
		}
		updateNavButtons();
	}
	
	function updateNavButtons() {
		const hasBack = !!state.backImageUrl;
		prevBtn.classList.toggle('hidden', state.currentView === 0);
		nextBtn.classList.toggle('hidden', state.currentView === 1 || !hasBack);
	}
	
	function openModal(svgContent, backUrl, title) {
		state.svgContent = svgContent;
		state.backImageUrl = backUrl || '';
		state.currentView = 0;
		
		if (title) {
			titleEl.textContent = title;
		}
		
		updateView();
		modal.classList.add('active');
		document.body.style.overflow = 'hidden';
	}
	
	function closeModal() {
		modal.classList.remove('active');
		document.body.style.overflow = '';
		contentEl.innerHTML = '';
		state.svgContent = '';
		state.backImageUrl = '';
		state.currentView = 0;
	}
	
	// Event listeners
	closeBtn.addEventListener('click', closeModal);
	
	modal.addEventListener('click', function(e) {
		if (e.target === modal) {
			closeModal();
		}
	});
	
	prevBtn.addEventListener('click', function() {
		if (state.currentView > 0) {
			state.currentView = 0;
			updateView();
		}
	});
	
	nextBtn.addEventListener('click', function() {
		if (state.currentView < 1 && state.backImageUrl) {
			state.currentView = 1;
			updateView();
		}
	});
	
	document.addEventListener('keydown', function(e) {
		if (!modal.classList.contains('active')) return;
		
		if (e.key === 'Escape') {
			closeModal();
			return;
		}
		
		if (e.key === 'ArrowLeft' && state.currentView > 0) {
			state.currentView = 0;
			updateView();
		}
		
		if (e.key === 'ArrowRight' && state.currentView < 1 && state.backImageUrl) {
			state.currentView = 1;
			updateView();
		}
	});
	
	// Attach click handlers to SVG preview triggers
	document.querySelectorAll('.js-admin-svg-preview-trigger').forEach(function(trigger) {
		trigger.addEventListener('click', function() {
			const svgBase64 = trigger.getAttribute('data-svg-content');
			const backUrl = trigger.getAttribute('data-back-image') || '';
			const title = trigger.getAttribute('data-preview-title') || 'Edited Design';
			
			if (!svgBase64) return;
			
			try {
				const svgContent = atob(svgBase64);
				openModal(svgContent, backUrl, title);
			} catch (err) {
				console.error('Failed to decode SVG content', err);
			}
		});
	});
})();

// Function to handle material deduction
function deductMaterials(orderId) {
	if (!confirm('Are you sure you want to deduct materials from inventory for this order? This action cannot be undone.')) {
		return;
	}

	const button = event.target.closest('button');
	const originalText = button.innerHTML;
	
	// Disable button and show loading
	button.disabled = true;
	button.innerHTML = '<i class="fi fi-rr-spinner" aria-hidden="true"></i> Processing...';

	fetch(`/ordersummary/${orderId}/deduct-materials`, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
		}
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			alert('Materials have been successfully deducted from inventory.');
			// Reload the page to show updated material usage
			window.location.reload();
		} else {
			alert('Failed to deduct materials: ' + (data.message || 'Unknown error'));
			// Re-enable button
			button.disabled = false;
			button.innerHTML = originalText;
		}
	})
	.catch(error => {
		console.error('Error:', error);
		alert('An error occurred while deducting materials. Please try again.');
		// Re-enable button
		button.disabled = false;
		button.innerHTML = originalText;
	});
}
</script>
@endsection
