@extends('layouts.admin')

@section('title', 'Order Summary')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/ordersummary.css') }}">
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
@endphp

<main
	class="ordersummary-admin-page admin-page-shell"
	data-order-id="{{ $orderId ?? '' }}"
	data-order-number="{{ $orderNumber }}"
	data-export-url="{{ $exportUrl }}"
	data-print-url="{{ $printUrl }}"
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
										$options = collect(data_get($item, 'options', []))
											->filter(fn ($value) => filled($value))
											->map(function ($value, $key) {
												return ucfirst(str_replace('_', ' ', $key)) . ': ' . (is_string($value) ? $value : json_encode($value));
											})
											->values();
										$images = collect(data_get($item, 'preview_images', data_get($item, 'images', [])))->filter();
										$quantity = (int) data_get($item, 'quantity', 1);
										$unitPrice = (float) data_get($item, 'unit_price', data_get($item, 'price', 0));
										$lineTotal = (float) data_get($item, 'total', $quantity * $unitPrice);
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
						<dt>Subtotal</dt>
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
					<div>
						<dt>Tax</dt>
						<dd data-money>{{ number_format($tax, 2) }}</dd>
					</div>
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
