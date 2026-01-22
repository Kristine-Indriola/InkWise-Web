<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>My Cart — InkWise</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-ordersummary.css') }}">
	<script src="{{ asset('js/customer/orderflow-ordersummary.js') }}" defer></script>

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
		@import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
	</style>

	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
	<link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
	<link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">
	<script src="{{ asset('js/customer/customer.js') }}" defer></script>
	<script src="{{ asset('js/customer/template.js') }}" defer></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
</head>
<body>
@php
$resolveRoute = static function (string $name, string $fallbackPath) {
	try {
		return route($name);
	} catch (\Throwable $exception) {
		return url($fallbackPath);
	}
};

$envelopeUrl = $resolveRoute('order.envelope', '/order/envelope');
$summaryUrl = $resolveRoute('order.summary', '/order/summary');
$summaryJsonUrl = $resolveRoute('order.summary.json', '/order/summary.json');
$summaryClearUrl = $resolveRoute('order.summary.clear', '/order/summary');
$envelopeClearUrl = $resolveRoute('order.envelope.clear', '/order/envelope');
$giveawayClearUrl = $resolveRoute('order.giveaways.clear', '/order/giveaways');
$giveawaysUrl = $resolveRoute('order.giveaways', '/order/giveaways');
$finalStepUrl = $resolveRoute('order.finalstep', '/order/finalstep');
$checkoutUrl = $resolveRoute('customer.cart.checkout', '/cart/checkout');
$envelopeStoreUrl = $resolveRoute('order.envelope.store', '/order/envelope');
$giveawayStoreUrl = $resolveRoute('order.giveaways.store', '/order/giveaways');
$summarySyncUrl = $resolveRoute('order.summary.sync', '/order/summary/sync');
$cartRoute = $resolveRoute('order.addtocart', '/order/addtocart');
$searchValue = request('query', '');

$hasEnvelope = (bool) data_get($orderSummary, 'hasEnvelope', !empty(data_get($orderSummary, 'envelope')));
$hasGiveaway = (bool) data_get($orderSummary, 'hasGiveaway', !empty(data_get($orderSummary, 'giveaway')));

// Topbar nav variables (from invitations layout)
$resolvedInvitationType = $invitationType
	?? (request()->routeIs('templates.corporate.*') ? 'Corporate'
		: (request()->routeIs('templates.baptism.*') ? 'Baptism'
			: (request()->routeIs('templates.birthday.*') ? 'Birthday'
				: 'Wedding')));

$eventRoutes = [
	'wedding' => [
		'label' => 'Wedding',
		'invitations' => route('templates.wedding.invitations'),
		'giveaways' => route('templates.wedding.giveaways'),
	],
	'corporate' => [
		'label' => 'Corporate',
		'invitations' => route('templates.corporate.invitations'),
		'giveaways' => route('templates.corporate.giveaways'),
	],
	'baptism' => [
		'label' => 'Baptism',
		'invitations' => route('templates.baptism.invitations'),
		'giveaways' => route('templates.baptism.giveaways'),
	],
	'birthday' => [
		'label' => 'Birthday',
		'invitations' => route('templates.birthday.invitations'),
		'giveaways' => route('templates.birthday.giveaways'),
	],
];

$currentEventKey = strtolower($resolvedInvitationType);
if (! array_key_exists($currentEventKey, $eventRoutes)) {
	$currentEventKey = 'wedding';
}
$currentEventRoutes = $eventRoutes[$currentEventKey];

$navLinks = [];
foreach ($eventRoutes as $key => $config) {
	$navLinks[] = [
		'key' => $key,
		'label' => $config['label'],
		'route' => $config['invitations'],
		'isActive' => $key === $currentEventKey,
	];
}

$favoritesEnabled = \Illuminate\Support\Facades\Route::has('customer.favorites');
@endphp

	@include('partials.topbar')
	<main
		class="os-shell ordersummary-shell"
		style="padding-top: 160px;"
		data-storage-key="inkwise-finalstep"
		data-envelopes-url="{{ $envelopeUrl }}"
		data-summary-url="{{ $summaryUrl }}"
		data-summary-api="{{ $summaryJsonUrl }}"
		data-summary-clear-url="{{ $summaryClearUrl }}"
		data-envelope-clear-url="{{ $envelopeClearUrl }}"
		data-giveaway-clear-url="{{ $giveawayClearUrl }}"
		data-checkout-url="{{ $checkoutUrl }}"
		data-has-envelope="{{ $hasEnvelope ? 'true' : 'false' }}"
		data-has-giveaway="{{ $hasGiveaway ? 'true' : 'false' }}"
		data-edit-url="{{ $finalStepUrl }}"
		data-giveaways-url="{{ $giveawaysUrl }}"
		data-envelope-store-url="{{ $envelopeStoreUrl }}"
		data-giveaway-store-url="{{ $giveawayStoreUrl }}"
		data-summary-sync-url="{{ $summarySyncUrl }}"
	>
		<div class="os-backbar">
			<a href="{{ route('dashboard') }}" class="os-back-giveaways" aria-label="Back to dashboard">
				<svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
				Back to Dashboard
			</a>
		</div>

		@if($cartItems->isEmpty())
		<div class="os-empty" data-empty-state>
			<div class="os-empty-card">
				<h2>Your cart is empty</h2>
				<p>You haven't added any items to your cart yet. Start browsing our templates to find the perfect invitation.</p>
				<a href="{{ route('templates.wedding.invitations') }}" class="btn primary">Browse Templates</a>
			</div>
		</div>
		@else
		<div class="os-layout" data-summary-wrapper>
			<section class="os-detail-column" data-summary-grid>
				@foreach($cartItems as $item)
				<article class="os-card os-product-preview">
					<header class="os-card-header">
						<h2>{{ $item->product->name ?? 'Custom Product' }}</h2>
						<button type="button" class="os-preview-remove" data-remove-item="{{ $item->id }}">Remove</button>
					</header>
					<div class="os-preview-content">
						<div class="os-preview-left">
							<div class="os-preview-frame" data-preview-frame>
								@if($item->product && $item->product->template)
									<img src="{{ asset($item->product->template->front_image ?? 'images/placeholder.png') }}" alt="Product preview" data-preview-image>
								@else
									<img src="{{ asset('images/placeholder.png') }}" alt="Product preview" data-preview-image>
								@endif
							</div>
							<div class="os-preview-meta">
								<a href="{{ route('order.finalstep', ['product' => $item->product_id]) }}" class="os-preview-link" data-preview-edit>Edit design</a>
							</div>
						</div>
						<div class="os-preview-right">
							<h3 class="os-preview-title">{{ $item->product->name ?? 'Custom Product' }}</h3>
							<div class="os-preview-quantity">
								<label for="quantity-{{ $item->id }}">Quantity</label>
								<select id="quantity-{{ $item->id }}" data-item-id="{{ $item->id }}" data-update-quantity>
									@for($i = 1; $i <= 200; $i++)
										<option value="{{ $i }}" {{ $item->quantity == $i ? 'selected' : '' }}>{{ $i }}</option>
									@endfor
								</select>
							</div>
							<div class="os-preview-price">
								<p>Unit Price: ₱{{ number_format($item->unit_price, 2) }}</p>
								<p>Total: ₱{{ number_format($item->total_price, 2) }}</p>
							</div>
						</div>
					</div>
				</article>
				@endforeach
			</section>

			<aside class="os-sidebar-column">
				<div class="os-card os-order-summary">
					<header class="os-card-header">
						<h2>Order Summary</h2>
					</header>
					<div class="os-summary-content">
						<dl class="os-summary-list">
							<div class="os-summary-item">
								<dt>Items ({{ $cartItems->count() }})</dt>
								<dd>₱{{ number_format($totalAmount, 2) }}</dd>
							</div>
							<div class="os-summary-item">
								<dt>Shipping</dt>
								<dd>₱0.00</dd>
							</div>
							<div class="os-summary-item">
								<dt>Tax</dt>
								<dd>₱0.00</dd>
							</div>
							<div class="os-summary-total">
								<dt>Total</dt>
								<dd>₱{{ number_format($totalAmount, 2) }}</dd>
							</div>
						</dl>
						<a href="{{ $checkoutUrl }}" class="os-checkout-btn">Proceed to Checkout</a>
					</div>
				</div>
			</aside>
		</div>
		@endif
	</main>

	<script>
		document.addEventListener('DOMContentLoaded', () => {
			// Handle remove item
			document.querySelectorAll('[data-remove-item]').forEach(button => {
				button.addEventListener('click', async (e) => {
					const itemId = e.target.dataset.removeItem;
					if (!itemId) return;

					if (!confirm('Are you sure you want to remove this item from your cart?')) return;

					try {
						const response = await fetch(`/order/cart/items/${itemId}`, {
							method: 'DELETE',
							headers: {
								'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
								'Accept': 'application/json',
							},
						});

						if (response.ok) {
							location.reload();
						} else {
							alert('Failed to remove item. Please try again.');
						}
					} catch (error) {
						console.error('Error removing item:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});

			// Handle quantity update (if needed)
			document.querySelectorAll('[data-update-quantity]').forEach(select => {
				select.addEventListener('change', async (e) => {
					const itemId = e.target.dataset.itemId;
					const quantity = e.target.value;

					try {
						const response = await fetch(`/order/cart/items/${itemId}`, {
							method: 'PATCH',
							headers: {
								'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
								'Content-Type': 'application/json',
								'Accept': 'application/json',
							},
							body: JSON.stringify({ quantity }),
						});

						if (response.ok) {
							location.reload();
						} else {
							alert('Failed to update quantity. Please try again.');
						}
					} catch (error) {
						console.error('Error updating quantity:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		});
	</script>
</body>
</html>