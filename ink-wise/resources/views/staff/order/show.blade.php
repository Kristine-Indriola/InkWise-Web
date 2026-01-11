@extends('layouts.staffapp')

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

		.order-stage-chip--pending-awaiting-materials {
			background: #fef3c7;
			color: #92400e;
		}

		.order-stage-chip--in-production {
			background: #fef3c7;
			color: #b45309;
		}

		.order-stage-chip--confirmed {
			background: #dcfce7;
			color: #15803d;
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
			.payment-info-grid {
				display: grid;
				gap: 16px;
			}

			@media (min-width: 900px) {
				.payment-info-grid {
					grid-template-columns: 2fr 1fr;
				}
			}

			.payment-info-card {
				background: #ffffff;
				border: 1px solid #e5e7eb;
				border-radius: 10px;
				padding: 20px;
			}

			.payment-info-card__title {
				margin: 0 0 12px;
				font-size: 16px;
				font-weight: 600;
				color: #111827;
			}

			.payment-info-card dl {
				margin: 0;
				display: grid;
				gap: 12px;
			}

			.payment-info-card dt {
				font-size: 12px;
				text-transform: uppercase;
				letter-spacing: 0.04em;
				color: #6b7280;
				font-weight: 600;
				margin-bottom: 4px;
			}

			.payment-info-card dd {
				margin: 0;
				font-size: 14px;
				font-weight: 600;
				color: #111827;
				word-break: break-word;
			}

			.payment-info-card__text {
				margin: 0;
				color: #374151;
				font-size: 14px;
				line-height: 1.6;
			}

			.payment-info-card__empty {
				color: #9ca3af;
				font-size: 14px;
				line-height: 1.6;
				margin: 0;
			}

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
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 18px;
			box-shadow: 0 10px 26px rgba(15, 23, 42, 0.04);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		.material-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);
		}

		.material-card__type {
			display: inline-flex;
			align-items: center;
			padding: 4px 12px;
			border-radius: 9999px;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			background: #ede9fe;
			color: #6d28d9;
		}

		.material-card__title {
			margin: 16px 0 8px;
			font-size: 18px;
			font-weight: 600;
			color: #0f172a;
		}

		.material-card__quantity {
			margin: 0 0 6px;
			color: #475569;
			font-size: 14px;
		}

		.material-card__quantity strong {
			font-size: 20px;
			color: #1f2937;
		}

		.material-card__cost {
			margin: 0;
			color: #334155;
			font-size: 14px;
			font-weight: 600;
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

		.item-cell__thumb-fallback {
			display: inline-block;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
		}

		.item-cell__thumb-fallback img {
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

		.ordersummary-toast {
			position: fixed;
			bottom: 24px;
			right: 24px;
			background: rgba(15, 23, 42, 0.92);
			color: #f8fafc;
			padding: 14px 18px;
			border-radius: 8px;
			box-shadow: 0 18px 36px rgba(15, 23, 42, 0.38);
			font-size: 14px;
			opacity: 0;
			transform: translateY(16px);
			pointer-events: none;
			transition: opacity 0.25s ease, transform 0.25s ease;
		}

		.ordersummary-toast[data-show="true"] {
			opacity: 1;
			transform: translateY(0);
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
	$initialPaymentStatus = $paymentStatus;
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
	// Build timeline from order activities
	$activities = collect(data_get($order, 'activities', []));
	
	// Add order creation event
	$timeline = collect([
		[
			'label' => 'Order Created',
			'author' => data_get($order, 'user.name') ?? 'Customer',
			'state' => 'default',
			'note' => 'Order was placed by customer',
			'timestamp' => data_get($order, 'created_at'),
		]
	]);
	
	// Add activity events
	foreach ($activities as $activity) {
		$activityType = data_get($activity, 'activity_type');
		$label = match($activityType) {
			'status_updated' => 'Status Updated',
			'payment_updated' => 'Payment Updated',
			default => ucfirst(str_replace('_', ' ', $activityType))
		};
		
		$userName = data_get($activity, 'user_name') ?? 'System';
		$userRole = data_get($activity, 'user_role');
		$authorDisplay = $userName . ($userRole ? ' (' . ucfirst($userRole) . ')' : '');
		
		$timeline->push([
			'label' => $label,
			'author' => $authorDisplay,
			'state' => $activityType === 'status_updated' ? data_get($activity, 'new_value', 'default') : 'default',
			'note' => data_get($activity, 'description'),
			'timestamp' => data_get($activity, 'created_at'),
		]);
	}
	
	// Sort timeline by timestamp (newest first)
	$timeline = $timeline->sortByDesc(function ($event) {
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
		$ordersIndexUrl = route('staff.order_list.index');
	} catch (\Throwable $e) {
		$ordersIndexUrl = url('/staff/order-list');
	}

	try {
		$statusManageUrl = $order ? route('staff.orders.status.edit', ['id' => data_get($order, 'id')]) : null;
	} catch (\Throwable $e) {
		$statusManageUrl = null;
	}

	try {
		$paymentManageUrl = $order ? route('staff.orders.payment.edit', ['order' => data_get($order, 'id')]) : null;
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

	$paymentStatusOptions = [
		'pending' => 'Pending',
		'paid' => 'Paid',
		'partial' => 'Partial',
		'failed' => 'Failed',
		'refunded' => 'Refunded',
	];
	$currentPaymentStatusLabel = $paymentStatusOptions[$paymentStatus] ?? ucfirst(str_replace('_', ' ', $paymentStatus));

	$payments = collect(data_get($order, 'payments', []));
	$paymentsSummary = collect(data_get($order, 'payments_summary', []));
	$currencyCode = data_get($order, 'currency', 'PHP');
	$currencySymbol = $currencyCode === 'PHP' ? '₱' : ($currencyCode . ' ');
	$formatCurrencyAmount = function ($value) use ($currencySymbol) {
		$numeric = is_numeric($value) ? (float) $value : 0.0;
		return $currencySymbol . number_format($numeric, 2);
	};
	$orderGrandTotal = (float) ($paymentsSummary->get('grand_total') ?? data_get($order, 'grand_total', $grandTotal));
	if ($orderGrandTotal <= 0 && $grandTotal > 0) {
		$orderGrandTotal = (float) $grandTotal;
	}
	$metadataRaw = data_get($order, 'metadata');
	if (is_string($metadataRaw) && $metadataRaw !== '') {
		$decodedMetadata = json_decode($metadataRaw, true);
		$metadata = json_last_error() === JSON_ERROR_NONE && is_array($decodedMetadata) ? $decodedMetadata : [];
	} elseif (is_array($metadataRaw)) {
		$metadata = $metadataRaw;
	} else {
		$metadata = [];
	}
	$financialMetadata = data_get($metadata, 'financial', []);
	$paidOverrideRaw = data_get($financialMetadata, 'total_paid_override');
	$balanceOverrideRaw = data_get($financialMetadata, 'balance_due_override');
	$paidOverride = is_numeric($paidOverrideRaw) ? (float) $paidOverrideRaw : null;
	$balanceOverride = is_numeric($balanceOverrideRaw) ? (float) $balanceOverrideRaw : null;
	$totalPaid = $paidOverride ?? (float) ($paymentsSummary->get('total_paid') ?? $payments->reduce(function ($carry, $paymentRow) {
		$status = strtolower((string) data_get($paymentRow, 'status', 'pending'));
		if ($status === 'paid') {
			return $carry + (float) data_get($paymentRow, 'amount', 0);
		}
		return $carry;
	}, 0.0));
	$balanceDue = $balanceOverride ?? (float) ($paymentsSummary->get('balance_due') ?? max($orderGrandTotal - $totalPaid, 0));
	if ($paymentStatus !== 'paid') {
		if ($orderGrandTotal > 0 && $balanceDue <= 0.01 && $totalPaid >= max($orderGrandTotal - 0.01, 0)) {
			$paymentStatus = 'paid';
		} elseif ($totalPaid > 0 && $balanceDue > 0.01) {
			$paymentStatus = 'partial';
		} elseif ($initialPaymentStatus === 'paid' && $balanceDue > 0.01) {
			$paymentStatus = 'partial';
		}
	}
	$latestPaymentAtRaw = $paymentsSummary->get('latest_payment_at');
	$latestPaymentDisplay = $latestPaymentAtRaw ? $formatDateTime($latestPaymentAtRaw) : null;
	$primaryPaymentMethod = data_get($order, 'payment_method');
	if (!$primaryPaymentMethod && $payments->isNotEmpty()) {
		$primaryPaymentMethod = data_get($payments->first(), 'method');
	}
	$primaryPaymentProvider = $payments->isNotEmpty() ? data_get($payments->first(), 'provider') : null;
	$paymentCount = $payments->count();

	$paymentNote = $metadata['payment_note'] ?? null;

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

	@php
		$savedTemplate = data_get($order, 'metadata.template') ?? null;
		$savedGallery = [];
		if ($savedTemplate) {
			if (!empty($savedTemplate['preview_images']) && is_array($savedTemplate['preview_images'])) {
				$savedGallery = $savedTemplate['preview_images'];
			} elseif (!empty($savedTemplate['preview_image'])) {
				$savedGallery = [$savedTemplate['preview_image']];
			}
		}
	@endphp

	@if($savedTemplate && !empty($savedGallery))
		<section class="status-info-card" style="margin-top:12px;">
			<h3 style="margin:0 0 8px 0;">Saved Template</h3>
			<div style="display:flex;gap:12px;align-items:center;">
				<img src="{{ $savedTemplate['preview_image'] ?? ($savedGallery[0] ?? '') }}" alt="Saved template preview" style="max-width:220px;max-height:160px;object-fit:contain;border:1px solid #e6eef9;border-radius:8px;">
				<div>
					<div style="font-weight:600;margin-bottom:6px;">{{ $savedTemplate['template_name'] ?? 'Saved template' }}</div>
					<div style="color:#475569;font-size:0.95rem;margin-bottom:8px;">This template was saved by the customer.</div>
					<button type="button" class="btn btn-primary" data-preview-trigger data-preview-title="{{ $savedTemplate['template_name'] ?? 'Saved template' }}" data-preview-gallery='@json($savedGallery)' data-preview-materials='[]'>
						<i class="fi fi-rr-download" aria-hidden="true"></i> Preview / Download
					</button>
				</div>
			</div>
		</section>
	@endif

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
					$displayKey = $statusKey === 'draft' ? 'new_order' : $statusKey;
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

										$rawImages = data_get($item, 'preview_images', data_get($item, 'images', []));
										if ($rawImages instanceof \Illuminate\Support\Collection) {
											$rawImages = $rawImages->all();
										}
										if (is_string($rawImages) && trim($rawImages) !== '') {
											$decodedGallery = json_decode($rawImages, true);
											$rawImages = json_last_error() === JSON_ERROR_NONE ? $decodedGallery : [trim($rawImages)];
										}
										if (is_object($rawImages)) {
											if (method_exists($rawImages, 'toArray')) {
												$rawImages = $rawImages->toArray();
											} else {
												$rawImages = (array) $rawImages;
											}
										}
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
										$images = collect($rawImages)->filter(function ($value) {
											if (is_string($value)) {
												return trim($value) !== '';
											}
											return $value !== null;
										});
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

													$processImageEntry = null;
													$processImageEntry = function ($entry, $imageKey = null) use (&$processImageEntry, $detectOrientation, $pushGalleryImage) {
														if ($entry instanceof \Illuminate\Support\Collection) {
															$entry = $entry->all();
														}

														if ($entry instanceof \Illuminate\Contracts\Support\Arrayable) {
															$entry = $entry->toArray();
														}

														if (is_object($entry)) {
															if (method_exists($entry, 'toArray')) {
																$entry = $entry->toArray();
															} elseif ($entry instanceof \JsonSerializable) {
																$entry = $entry->jsonSerialize();
															} else {
																$entry = (array) $entry;
															}
														}

														if (is_string($entry)) {
															$trimmed = trim($entry);
															if ($trimmed === '') {
																return;
															}

															$firstChar = substr($trimmed, 0, 1);
															$lastChar = substr($trimmed, -1);
															if (($firstChar === '{' && $lastChar === '}') || ($firstChar === '[' && $lastChar === ']')) {
																$decoded = json_decode($trimmed, true);
																if (json_last_error() === JSON_ERROR_NONE) {
																	if (is_array($decoded)) {
																		if (!\Illuminate\Support\Arr::isAssoc($decoded)) {
																			foreach ($decoded as $decodedKey => $decodedValue) {
																				$processImageEntry($decodedValue, is_string($decodedKey) ? $decodedKey : $imageKey);
																			}
																			return;
																		}

																		$entry = $decoded;
																	} elseif (is_string($decoded)) {
																		$trimmed = trim($decoded);
																	}
																}
															}

															if (!is_array($entry)) {
																$pushGalleryImage($trimmed ?? $entry, $detectOrientation($imageKey));
																return;
															}
														}

														if (!is_array($entry)) {
															return;
														}

														$primarySrc = $entry['url'] ?? $entry['src'] ?? $entry['preview'] ?? $entry['thumb'] ?? $entry['path'] ?? null;
														$primaryOrientation = $detectOrientation($entry['orientation'] ?? $entry['side'] ?? $entry['page'] ?? $entry['label'] ?? null) ?? $detectOrientation($imageKey);
														$primaryLabel = $entry['label'] ?? $entry['title'] ?? $entry['description'] ?? null;
														$pushGalleryImage($primarySrc, $primaryOrientation, $primaryLabel);

														foreach ($entry as $nestedKey => $nestedValue) {
															if (in_array($nestedKey, ['url', 'src', 'preview', 'thumb', 'path', 'label', 'title', 'description', 'orientation', 'side', 'page'], true)) {
																continue;
															}

															$processImageEntry($nestedValue, is_string($nestedKey) ? $nestedKey : $imageKey);
														}
													};

													$images->each(function ($img, $imageKey) use ($processImageEntry) {
														$processImageEntry($img, $imageKey);
													});

													if ($galleryEntries->isEmpty()) {
														$fallbackImage = data_get($item, 'image');
														if (is_string($fallbackImage) && trim($fallbackImage) !== '') {
															$pushGalleryImage($fallbackImage);
														}
													}

													$gallery = $galleryEntries->values();
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
													if (!$primaryImage) {
														$primaryImage = collect($rawImages)
															->map(function ($img) {
																if (is_string($img)) {
																	return trim($img);
																}
																if (is_array($img)) {
																	return $img['url'] ?? $img['src'] ?? $img['preview'] ?? null;
																}
																if ($img instanceof \Illuminate\Contracts\Support\Arrayable) {
																	$img = $img->toArray();
																	return $img['url'] ?? $img['src'] ?? $img['preview'] ?? null;
																}
																if (is_object($img)) {
																	$img = (array) $img;
																	return $img['url'] ?? $img['src'] ?? $img['preview'] ?? null;
																}
																return null;
															})
															->filter(function ($value) {
																return is_string($value) && $value !== '';
															})
															->first();
													}
													if (!$primaryImage) {
														$maybeImage = data_get($item, 'image');
														$primaryImage = is_string($maybeImage) && trim($maybeImage) !== '' ? trim($maybeImage) : null;
													}
													$previewTitle = data_get($item, 'name', 'Custom product');
												@endphp
												@if($gallery->isNotEmpty())
													<button
														type="button"
														class="item-cell__thumb-button"
														data-preview-trigger
														data-preview-title="{{ $previewTitle }}"
														data-preview-gallery='@json($gallery)'
														data-preview-materials='@json($itemMaterialsList)'
														aria-label="View artwork preview for {{ $previewTitle }}"
													>
														<img src="{{ $primaryImage }}" alt="{{ $primaryImageLabel ? $previewTitle . ' ' . strtolower($primaryImageLabel) : $previewTitle . ' preview' }}" class="item-cell__thumb">
													</button>
												@elseif($primaryImage)
													<div class="item-cell__thumb-fallback">
														<img src="{{ $primaryImage }}" alt="{{ $previewTitle }} preview" class="item-cell__thumb">
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
							<a href="{{ route('staff.messages.index') }}?start_conversation={{ $customerId }}" class="btn btn-secondary btn-sm">
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
					<h2>Payment details</h2>
				</header>
				<div class="payment-info-grid">
					<div class="payment-info-card">
						<h3 class="payment-info-card__title">Transaction overview</h3>
						<dl>
							<dt>Order number</dt>
							<dd>{{ $orderNumber }}</dd>
							<dt>Customer</dt>
							<dd>{{ $customerName }}</dd>
							<dt>Order date</dt>
							<dd>{{ $placedAt ?? 'Not available' }}</dd>
							<dt>Total amount</dt>
							<dd>{{ $formatCurrencyAmount($orderGrandTotal) }}</dd>
							<dt>Amount paid</dt>
							<dd>{{ $formatCurrencyAmount($totalPaid) }}</dd>
							<dt>Balance due</dt>
							<dd>{{ $formatCurrencyAmount($balanceDue) }}</dd>
							<dt>Payment status</dt>
							<dd>{{ $currentPaymentStatusLabel }}</dd>
							<dt>Last updated</dt>
							<dd>{{ $lastUpdatedDisplay ?? 'Not available' }}</dd>
							@if($primaryPaymentMethod)
								<dt>Payment method</dt>
								<dd>{{ mb_strtoupper($primaryPaymentMethod) }}</dd>
							@endif
							@if($primaryPaymentProvider)
								<dt>Payment provider</dt>
								<dd>{{ ucfirst($primaryPaymentProvider) }}</dd>
							@endif
							@if($latestPaymentDisplay)
								<dt>Latest payment</dt>
								<dd>{{ $latestPaymentDisplay }}</dd>
							@endif
							@if($paymentNote)
								<dt>Payment note</dt>
								<dd>{{ $paymentNote }}</dd>
							@endif
						</dl>
					</div>
					<div class="payment-info-card">
						<h3 class="payment-info-card__title">Summary</h3>
						<div class="payment-info-card__text">
							@if($balanceDue > 0)
								<p>This order has an outstanding balance of <strong>{{ $formatCurrencyAmount($balanceDue) }}</strong>.</p>
								@if($paymentStatus === 'pending')
									<p>Payment is still pending. Update the status once payment is confirmed.</p>
								@elseif($paymentStatus === 'partial')
									<p>Partial payments have been recorded. Collect the remaining balance to mark this order as fully paid.</p>
								@endif
							@else
								<p>This order is fully paid. All amounts have been received.</p>
							@endif

							@if($paymentCount > 0)
								<p><strong>{{ $paymentCount }}</strong> payment transaction{{ $paymentCount > 1 ? 's' : '' }} recorded.</p>
							@else
								<p>No payment transactions have been recorded yet.</p>
							@endif

							@if($primaryPaymentMethod || $primaryPaymentProvider)
								<p>
									@if($primaryPaymentMethod)
										Primary method: <strong>{{ mb_strtoupper($primaryPaymentMethod) }}</strong>
									@endif
									@if($primaryPaymentMethod && $primaryPaymentProvider)
										 · 
									@endif
									@if($primaryPaymentProvider)
										Provider: <strong>{{ ucfirst($primaryPaymentProvider) }}</strong>
									@endif
								</p>
							@endif
						</div>
					</div>
				</div>
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
							<div class="rating-comment" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
								<p style="margin: 0; font-style: italic; color: #374151; font-size: 16px; line-height: 1.5;">"{{ $ratingComment }}"</p>
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
				<div class="payment-summary-grid">
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Total invoiced</span>
						<span class="payment-summary-grid__value">{{ $formatCurrencyAmount($orderGrandTotal) }}</span>
					</div>
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Total paid</span>
						<span class="payment-summary-grid__value">{{ $formatCurrencyAmount($totalPaid) }}</span>
					</div>
					<div class="payment-summary-grid__item">
						<span class="payment-summary-grid__label">Balance remaining</span>
						<span class="payment-summary-grid__value">{{ $formatCurrencyAmount($balanceDue) }}</span>
					</div>
					@if($primaryPaymentMethod)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Payment method</span>
							<span class="payment-summary-grid__value">{{ mb_strtoupper($primaryPaymentMethod) }}</span>
						</div>
					@endif
					@if($primaryPaymentProvider)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Provider</span>
							<span class="payment-summary-grid__value">{{ ucfirst($primaryPaymentProvider) }}</span>
						</div>
					@endif
					@if($latestPaymentDisplay)
						<div class="payment-summary-grid__item">
							<span class="payment-summary-grid__label">Latest payment</span>
							<span class="payment-summary-grid__value">{{ $latestPaymentDisplay }}</span>
						</div>
					@endif
				</div>
				@if($orderGrandTotal > 0)
					@if($totalPaid <= 0)
						<div class="payment-alert payment-alert--pending">No payments recorded yet.</div>
					@elseif($balanceDue > 0.01)
						<div class="payment-alert payment-alert--balance">Remaining balance of {{ $formatCurrencyAmount($balanceDue) }} is pending.</div>
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
							$sidebarSubtotal = $computedSidebarSubtotal ?: $subtotal;
							$sidebarGrandTotal = $orderGrandTotal > 0 ? $orderGrandTotal : $sidebarSubtotal;
						@endphp
						<dd data-money>{{ number_format($sidebarSubtotal, 2) }}</dd>
					</div>
					<div class="sidebar-totals__total">
						<dt>Total due</dt>
						<dd data-grand-total data-money>{{ number_format($sidebarGrandTotal, 2) }}</dd>
					</div>
				</dl>
				@if($payments->isNotEmpty())
					<div class="payment-history">
						<h3>Payment history</h3>
						@foreach($payments as $payment)
							@php
								$paymentAmount = (float) data_get($payment, 'amount', 0);
								$paymentStatusRow = strtolower((string) data_get($payment, 'status', 'pending'));
								$statusClass = in_array($paymentStatusRow, ['paid', 'pending', 'partial', 'failed', 'refunded'], true) ? $paymentStatusRow : 'pending';
								$paymentDateRaw = data_get($payment, 'recorded_at') ?? data_get($payment, 'created_at');
								$paymentDate = $paymentDateRaw ? $formatDateTime($paymentDateRaw) : null;
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
									<div class="payment-history__amount">{{ $formatCurrencyAmount($paymentAmount) }}</div>
									@if($metaPieces !== '')
										<div class="payment-history__meta">{{ $metaPieces }}</div>
									@endif
								</div>
								<span class="payment-history__status payment-history__status--{{ $statusClass }}">{{ ucfirst($paymentStatusRow) }}</span>
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
				type: ['paper', 'addon'].includes(type) ? type : 'other',
				label: label,
				quantity: Number.isFinite(quantityValue) ? quantityValue : null,
				unit_price: Number.isFinite(unitPriceValue) ? unitPriceValue : null,
				total: Number.isFinite(totalValue) ? totalValue : null,
			};
		}).filter(Boolean);
	};

	const describeMaterial = function (entry) {
		const typeLabel = materialTypeLabels[entry.type] || 'Other';
		const quantityLabel = Number.isFinite(entry.quantity) ? (entry.quantity % 1 === 0 ? entry.quantity.toFixed(0) : entry.quantity.toFixed(2)) : '—';
		let costLabel = 'Cost pending';
		if (Number.isFinite(entry.total) && entry.total > 0) {
			costLabel = 'Total ₱' + entry.total.toFixed(2);
		} else if (Number.isFinite(entry.unit_price) && entry.unit_price > 0) {
			costLabel = 'Unit ₱' + entry.unit_price.toFixed(2);
		}

		return typeLabel + ' · ' + entry.label + ' · ' + quantityLabel + ' units · ' + costLabel;
	};

	let gallery = [];
	let currentIndex = 0;
	let previewTitle = '';
	let materialsList = [];
	let lastFocusedElement = null;

	const closeModal = function () {
		modal.setAttribute('hidden', '');
		modal.setAttribute('aria-hidden', 'true');
		body.classList.remove('ordersummary-modal-open');
		gallery = [];
		materialsList = [];
		currentIndex = 0;
		imageEl.removeAttribute('src');
		downloadEl.removeAttribute('href');
		downloadEl.removeAttribute('download');
		materialsListEl.innerHTML = '';
		materialsWrap.setAttribute('hidden', '');
		if (lastFocusedElement) {
			lastFocusedElement.focus();
			lastFocusedElement = null;
		}
	};

	const updateMaterialsList = function () {
		materialsListEl.innerHTML = '';
		if (!materialsList.length) {
			materialsWrap.setAttribute('hidden', '');
			return;
		}

		materialsWrap.removeAttribute('hidden');
		materialsList.forEach(function (entry) {
			const li = document.createElement('li');
			li.textContent = describeMaterial(entry);
			materialsListEl.appendChild(li);
		});
	};

	const updatePreview = function () {
		if (!gallery.length) {
			return;
		}

		const current = gallery[currentIndex];
		imageEl.src = current.src;
		imageEl.alt = previewTitle + (current.label ? ' ' + current.label.toLowerCase() : ' preview');
		titleEl.textContent = previewTitle;
		labelEl.textContent = current.label ? ' · ' + current.label : '';
		counterEl.textContent = gallery.length > 1 ? ' · ' + (currentIndex + 1) + ' of ' + gallery.length : '';
		downloadEl.href = current.src;
		downloadEl.download = (previewTitle || 'Order') + '-' + (current.label || 'artwork') + '.png';

		prevBtn.disabled = currentIndex === 0;
		nextBtn.disabled = currentIndex === gallery.length - 1;
	};

	const openModal = function (trigger) {
		const galleryData = trigger.getAttribute('data-preview-gallery');
		const title = trigger.getAttribute('data-preview-title') || 'Artwork';
		const materialsData = trigger.getAttribute('data-preview-materials');

		try {
			gallery = normalizeGallery(JSON.parse(galleryData));
		} catch (error) {
			gallery = [];
		}

		try {
			materialsList = normalizeMaterials(JSON.parse(materialsData));
		} catch (error) {
			materialsList = [];
		}

		if (!gallery.length) {
			return;
		}

		previewTitle = title;
		currentIndex = 0;
		updatePreview();
		updateMaterialsList();
		modal.removeAttribute('hidden');
		modal.setAttribute('aria-hidden', 'false');
		body.classList.add('ordersummary-modal-open');
		lastFocusedElement = trigger;
		modal.focus();

		// Ensure download button will download all pages when multiple images exist
		downloadEl.addEventListener('click', function (evt) {
			if (!gallery || !Array.isArray(gallery) || gallery.length <= 1) return; // default single-file behavior
			evt.preventDefault();
			gallery.forEach(function (entry, i) {
				try {
					const a = document.createElement('a');
					a.href = entry.src;
					const safeLabel = (entry.label || 'page').replace(/[^a-z0-9-_\.]/gi, '_');
					a.download = (previewTitle || 'Order') + '-' + safeLabel + '-' + (i + 1) + '.png';
					document.body.appendChild(a);
					a.click();
					a.remove();
				} catch (err) {
					console.error('Download failed for gallery item', err);
				}
			});
		});
	};

	document.querySelectorAll('[data-preview-trigger]').forEach(function (trigger) {
		trigger.addEventListener('click', function (event) {
			event.preventDefault();
			openModal(trigger);
		});
	});

	prevBtn.addEventListener('click', function () {
		if (currentIndex <= 0) {
			return;
		}
		currentIndex -= 1;
		updatePreview();
	});

	nextBtn.addEventListener('click', function () {
		if (currentIndex >= gallery.length - 1) {
			return;
		}
		currentIndex += 1;
		updatePreview();
	});

	closeEls.forEach(function (button) {
		button.addEventListener('click', closeModal);
	});

	modal.addEventListener('click', function (event) {
		if (event.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
			event.preventDefault();
			closeModal();
		}

		if (event.key === 'ArrowLeft' && !modal.hasAttribute('hidden')) {
			event.preventDefault();
			prevBtn.click();
		}

		if (event.key === 'ArrowRight' && !modal.hasAttribute('hidden')) {
			event.preventDefault();
			nextBtn.click();
		}
	});
});
</script>
@endsection
