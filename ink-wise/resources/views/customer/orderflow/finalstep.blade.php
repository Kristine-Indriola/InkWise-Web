<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Finalize Order — Inkwise</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
		@import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
	</style>
	@vite(['resources/css/app.css'])
	<link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-finalstep.css') }}">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<style>
		.flatpickr-calendar {
			background: white;
			box-shadow: 0 10px 25px rgba(0,0,0,0.1);
		}
		.flatpickr-day {
			color: black !important;
		}
		.flatpickr-day:hover {
			background: #e0f2fe;
		}
		.flatpickr-day.selected {
			background: #4f46e5;
			color: white !important;
		}
		.flatpickr-months .flatpickr-month {
			color: black;
		}
		.flatpickr-weekday {
			color: #6b7280;
		}

		.nav-icon-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2.75rem;
			height: 2.75rem;
			border-radius: 9999px;
			border: 1px solid #dbeafe;
			background-color: rgba(255, 255, 255, 0.92);
			color: #f472b6;
			transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
			box-shadow: 0 12px 24px rgba(166, 183, 255, 0.25);
		}

		.nav-icon-button:hover {
			transform: translateY(-1px);
			background-color: #fdf2ff;
		}

		.nav-icon-button:focus-visible {
			outline: 2px solid #a6b7ff;
			outline-offset: 2px;
		}

		.nav-icon-button[aria-disabled="true"] {
			opacity: 0.6;
			pointer-events: none;
		}

		.nav-icon-button i {
			font-size: 1.15rem;
		}

		@media (max-width: 1024px) {
			.nav-icon-button {
				box-shadow: none;
			}
		}

		/* Payment summary styling */
		.payment-summary {
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(10px);
			border: 1px solid rgba(15, 23, 42, 0.08);
			border-radius: 1rem;
			padding: 1.5rem;
			box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
		}

		.payment-summary h3 {
			margin-bottom: 1rem;
			font-weight: 600;
			color: #0f172a;
		}

		.payment-summary dl {
			margin: 0;
		}

		.payment-summary dt {
			color: #64748b;
		}

		.payment-summary dd {
			font-weight: 500;
			color: #0f172a;
		}

		.payment-summary .border-t {
			border-top-color: #e2e8f0;
		}

		.price-label {
			display: block;
			font-size: 0.875rem;
			color: #64748b;
			margin-bottom: 0.25rem;
		}

		.price-value {
			display: block;
			font-size: 2rem;
			font-weight: 700;
			color: #0f172a;
		}

		.notice-icon {
			flex-shrink: 0;
			width: 2rem;
			height: 2rem;
			color: #d97706;
		}

		.notice-content {
			flex: 1;
		}

		.notice-title {
			font-size: 1rem;
			font-weight: 600;
			color: #92400e;
			margin: 0 0 0.5rem 0;
		}

		.notice-text {
			font-size: 0.875rem;
			color: #a16207;
			margin: 0;
			line-height: 1.5;
		}

	</style>
	<script src="{{ asset('js/customer/orderflow-finalstep.js') }}" defer></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="finalstep-body bg-white" data-product-id="{{ $product->id ?? '' }}" style="overflow-y: auto; height: auto; padding-top: 0;">
@php
	$req = request();
	$product = $product ?? null;
	$proof = $proof ?? null;
	$templateRef = $templateRef ?? null;
	$order = $order ?? null;
	$orderSummary = $orderSummary ?? [];
	$finalArtwork = $finalArtwork ?? [];
	$finalArtworkFront = $finalArtworkFront ?? null;
	$finalArtworkBack = $finalArtworkBack ?? null;
	$quantityOptions = $quantityOptions ?? [];
	$paperStocks = $paperStocks ?? [];
	$addonGroups = $addonGroups ?? [];
	$estimatedDeliveryDate = $estimatedDeliveryDate ?? null;
	$estimatedDeliveryDays = $estimatedDeliveryDays ?? null;
	$processingDays = $processingDays ?? null;
	$minQty = $minQty ?? 10;
@endphp
@php 
	$uploads = $product->uploads ?? collect();
	$images = $product->product_images ?? $product->images ?? optional($proof)->images ?? null;
	$templateRef = $product->template ?? ($templateRef ?? optional($proof)->template ?? null);

	$frontImage = $finalArtwork['front'] ?? ($finalArtworkFront ?? null);
	$backImage = $finalArtwork['back'] ?? ($finalArtworkBack ?? null);

	$resolveImage = function ($candidate) {
		if (!$candidate) return null;
		if (str_starts_with($candidate, 'data:')) return $candidate;
		if (preg_match('/^(https?:)?\/\//i', $candidate) || str_starts_with($candidate, '/')) return $candidate;
		try { return \Illuminate\Support\Facades\Storage::url($candidate); } catch (\Throwable $e) { return null; }
	};

	$summaryPayload = is_array($orderSummary ?? null) ? $orderSummary : [];
	$summaryImages = $summaryPayload['preview_images'] ?? $summaryPayload['previewImages'] ?? null;
	if (!isset($summaryPayload['previewImage']) && is_array($summaryImages) && !empty($summaryImages[0])) {
		$summaryPayload['previewImage'] = $summaryImages[0];
	}

	if (!$frontImage && is_array($summaryImages) && !empty($summaryImages[0])) {
		$frontImage = $resolveImage($summaryImages[0]);
	}

	if (!$backImage && is_array($summaryImages) && !empty($summaryImages[1])) {
		$backImage = $resolveImage($summaryImages[1]);
	}

	if (!$frontImage && !empty($summaryPayload['previewImage'])) {
		$frontImage = $resolveImage($summaryPayload['previewImage']);
	}

	if (!$backImage && !empty($summaryPayload['back_preview'])) {
		$backImage = $resolveImage($summaryPayload['back_preview']);
	}

	if (!$frontImage && $images) {
		$frontImage = $resolveImage($images->final_front ?? $images->front ?? $images->preview ?? null);
	}
	if (!$backImage && $images) {
		$backImage = $resolveImage($images->final_back ?? $images->back ?? null);
	}
	if (!$frontImage && isset($templateRef)) {
		$frontImage = $resolveImage($templateRef->preview_front ?? $templateRef->front_image ?? $templateRef->preview ?? $templateRef->image ?? null);
	}
	if (!$backImage && isset($templateRef)) {
		$backImage = $resolveImage($templateRef->preview_back ?? $templateRef->back_image ?? null);
	}

	// Normalize any chosen previews so relative paths (e.g., customer/designs/final-previews/*) resolve correctly
	$frontImage = $resolveImage($frontImage);
	$backImage = $resolveImage($backImage);

	if (!$frontImage && $uploads->isNotEmpty()) {
		$first = $uploads->firstWhere(fn($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($first) {
			$frontImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . ($first->filename ?? ''));
		}
	}
	if (!$backImage && $uploads->count() > 1) {
		$second = $uploads->skip(1)->firstWhere(fn($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($second) {
			$backImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . ($second->filename ?? ''));
		}
	}

	$fallbackImage = asset('images/placeholder.png');
	$frontImage = $frontImage ?? $fallbackImage;
	$backImage = $backImage ?? $frontImage;

	$selectedQuantity = optional(optional($order)->items->first())->quantity ?? null;

	$quantityOptions = collect($quantityOptions ?? [])->map(function ($option) {
		return (object) $option;
	});

	if ($quantityOptions->isEmpty()) {
		$quantityOptions = collect(range(1, 20))->map(function ($i) {
			$value = $i * 10;
			return (object) [
				'label' => number_format($value),
				'value' => $value,
				'price' => round($value * max(6 - floor($i / 4), 3), 2),
			];
		});
	}

	$quantityValues = $quantityOptions->pluck('value')->filter(fn($value) => $value !== null);
	if ($quantityValues->isNotEmpty()) {
		$minQuantity = (int) $quantityValues->min();
		$maxQuantity = (int) $quantityValues->max();
	} else {
		$minQuantity = null;
		$maxQuantity = null;
	}

	// Show a simple minimum-order note instead of the previous bulk-range wording
	$effectiveMin = $minQuantity ?? $minQty ?? 10;
	$quantityNote = 'Select a quantities. Minimum order is ' . number_format(10);

	$paperStocks = collect($paperStocks ?? [])->map(function ($stock, $i) use ($fallbackImage) {
		$stock = (object) $stock;
		$stock->id = $stock->id ?? ('paper_' . $i);
		$stock->name = $stock->name ?? ($stock->label ?? 'Paper Stock');
		$stock->price = isset($stock->price) ? $stock->price : null;
		$stock->image = $stock->image ?? $fallbackImage;
		return $stock;
	})->values();

	$initialPaperSelection = $paperStocks->first(fn ($stock) => $stock->selected ?? false);
	$initialPaperStockId = $initialPaperSelection->id ?? '';
	$initialPaperStockPrice = $initialPaperSelection->price ?? 0;

	$addonGroups = collect($addonGroups ?? [])->map(function ($group, $i) use ($fallbackImage) {
		$group = (object) $group;
		$group->type = $group->type ?? ('additional_' . $i);
		$group->label = $group->label ?? \Illuminate\Support\Str::headline($group->type);
		$group->items = collect($group->items ?? [])->map(function ($item, $j) use ($fallbackImage, $group) {
			$item = (object) $item;
			$item->id = $item->id ?? ($group->type . '_' . $j);
			$item->name = $item->name ?? 'Add-on';
			$item->price = isset($item->price) ? $item->price : null;
			$item->image = $item->image ?? $fallbackImage;
			$item->type = $item->type ?? $group->type;
			return $item;
		})->all();
		return $group;
	})->values();

	$estimatedDeliveryDate = isset($estimatedDeliveryDate)
		? $estimatedDeliveryDate
		: \Carbon\Carbon::now()->addWeekdays(5)->format('F j, Y');
	$resolvedProductName = $product->name ?? optional($templateRef)->name ?? 'Custom Invitation';
// Resolve envelope route safely - prioritize customer-facing route, then fallback to admin endpoints
	try {
		$envelopeUrl = route('order.envelope');
	} catch (\Throwable $_ePrimary) {
		try {
			$envelopeUrl = route('products.create.envelope');
		} catch (\Throwable $_eAdmin) {
			try {
				$envelopeUrl = route('create.envelope');
			} catch (\Throwable $_eAlt) {
				// final fallback to the customer envelope URL path
				$envelopeUrl = url('/order/envelope');
			}
		}
	}

	try {
		$giveawaysUrl = route('order.giveaways');
	} catch (\Throwable $_eGiveaways) {
		$giveawaysUrl = url('/order/giveaways');
	}

	try {
		$reviewUrl = route('order.review');
	} catch (\Throwable $_eReview) {
		$reviewUrl = url('/order/review');
	}

	try {
		$finalStepSaveUrl = route('order.finalstep.save');
	} catch (\Throwable $_eSave) {
		$finalStepSaveUrl = url('/order/finalstep/save');
	}

	// Define date variables for pickup date input
	$estimatedDeliveryMinDate = \Carbon\Carbon::now()->addDay()->format('Y-m-d');
	$estimatedDeliveryMaxDate = \Carbon\Carbon::now()->addMonths(3)->format('Y-m-d');
	$estimatedDeliveryDateFormatted = \Carbon\Carbon::now()->addWeekdays(5)->format('Y-m-d');

	// Define pricing variables
	$minQty = 10;
@endphp
@php
	$processingDays = $estimatedDeliveryDays ?? ($processingDays ?? null) ?? 7;
@endphp

@php
    $formatMoney = static fn ($amount) => '₱' . number_format((float) ($amount ?? 0), 2);
    $invitationSubtotal = (float) ($orderSummary['subtotalAmount'] ?? 0);
    $extras = $orderSummary['extras'] ?? [];
    $envelopeTotal = (float) ($extras['envelope'] ?? 0);
    $giveawayTotal = (float) ($extras['giveaway'] ?? 0);
    $paperExtras = (float) ($extras['paper'] ?? 0);
    $addonsExtra = (float) ($extras['addons'] ?? 0);
    $shipping = (float) ($orderSummary['shippingFee'] ?? 0);
    $tax = (float) ($orderSummary['taxAmount'] ?? 0);
    $grandTotal = (float) ($orderSummary['totalAmount'] ?? 0);

    // Calculate invitation total (base + paper + addons)
    $invitationTotalCalc = $invitationSubtotal + $paperExtras + $addonsExtra;

    // Calculate envelope total
    $envelopeTotalCalc = $envelopeTotal;

    // Calculate giveaway total
    $giveawayTotalCalc = $giveawayTotal;
@endphp
<main class="finalstep-shell" data-storage-key="inkwise-finalstep" data-envelope-url="{{ $envelopeUrl }}" data-cart-url="{{ route('order.addtocart') }}" data-save-url="{{ $finalStepSaveUrl }}" data-fallback-samples="false" data-product-id="{{ $product->id ?? '' }}" data-product-name="{{ $resolvedProductName }}" data-processing-days="{{ $processingDays }}">
	<header class="finalstep-header">
		<div class="finalstep-header__content">
			<a href="{{ $reviewUrl }}" class="finalstep-header__back" aria-label="Back to review">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
				Back to review
			</a>
			<h1>Finalize your order</h1>
			<p>Review your artwork, choose finishing touches, and confirm the final details before checkout.</p>

			<!-- Saved template preview (moved into artwork preview card) -->
		</div>
	</header>


	<div class="finalstep-layout">
		<section class="finalstep-preview" data-product-name="{{ $resolvedProductName }}">
			<div class="finalstep-card preview-card">
				<header class="preview-card__header">
					<span class="preview-card__badge">Artwork preview</span>
					<div class="preview-toggle" role="group" aria-label="Preview sides">
						<button type="button" class="active" data-face="front" aria-pressed="true">Front</button>
						<button type="button" data-face="back" aria-pressed="false">Back</button>
					</div>
				</header>
				<div class="card-flip">
					<div class="inner">
						<div class="card-face front">
							@if(isset($customerReview) && !empty($customerReview->design_svg))
								{{-- Embed SVG directly - img src doesn't work with SVGs containing external resources --}}
								<div class="svg-container" style="width: 100%; height: 100%; pointer-events: none;">
									{!! $customerReview->design_svg !!}
								</div>
							@else
								<img src="{{ $frontImage }}" alt="Front of your design">
							@endif
						</div>
						<div class="card-face back">
							<img src="{{ $backImage }}" alt="Back of your design">
						</div>
					</div>
				</div>
			
				<!-- saved-template container removed; previews will replace artwork images directly -->
				<ul class="preview-meta">
					<li><span class="meta-label">Product</span><span class="meta-value">{{ $resolvedProductName }}</span></li>
					@if(isset($product->size))
						<li><span class="meta-label">Size</span><span class="meta-value">{{ $product->size }}</span></li>
					@endif
					@if(isset($product->paper_stock))
						<li><span class="meta-label">Paper stock</span><span class="meta-value">{{ $product->paper_stock }}</span></li>
					@endif
				</ul>
			</div>
		</section>

		<section class="finalstep-panel">
			<div class="finalstep-card form-card">
				<header class="form-card__header">
					<h2>Order details</h2>
					<p>Pick quantities, paper stocks, and optional finishes. We'll calculate totals automatically.</p>
				</header>
				<form id="finalOrderForm" class="finalstep-form" novalidate>
					<div class="form-fixed-section">
						<div class="form-group">
							<label for="quantityInput">Quantity</label>
							<div class="quantity-price-row">
								<input type="number" id="quantityInput" name="quantity" value="{{ $selectedQuantity ?? $minQty }}" min="{{ $minQty }}" {{ $maxQty ? 'max="' . $maxQty . '"' : '' }} required>
								<div class="price-display">
									<span class="meta-label">Total:</span>
									<span id="priceDisplay" class="meta-value">{{ $formatMoney($itemTotal ?? 0) }}</span>
								</div>
							</div>
							<div id="quantityError" class="error-message" style="display: none;">Quantity must be at least {{ $minQty }}</div>
							<p class="bulk-note">{{ $quantityNote }}</p>
						</div>
					</div>

					<div class="form-scroll-section">
						<div class="form-group paper-stocks-group">
							<label>Paper stock</label>
							<p class="selection-hint">Choose your preferred stock.</p>
							<!-- Paper availability hidden per UX request -->
							<div id="stockError" class="error-message" style="display:none; margin-top:6px;"></div>
							<div class="feature-grid small">
								@forelse($paperStocks as $stock)
									<button
										type="button"
										class="feature-card selectable-card paper-stock-card"
										data-id="{{ $stock->id }}"
										data-price="{{ $stock->price ?? 0 }}"
										data-available="{{ $stock->available ?? $stock->quantity ?? $stock->stock ?? 0 }}"
										aria-pressed="{{ $stock->selected ? 'true' : 'false' }}"
									>
										<div class="feature-card-media">
											<img src="{{ $stock->image }}" alt="{{ $stock->name }}">
										</div>
										<div class="feature-card-info">
											<span class="feature-card-title">{{ $stock->name }}</span>
											<span class="feature-card-price">
												@if($stock->price !== null)
													₱{{ number_format($stock->price, 2) }}
												@else
													On request
												@endif
											</span>
										</div>
									</button>
								@empty
									<p class="muted">No paper stocks available for this product.</p>
								@endforelse
							</div>
							<input type="hidden" name="paper_stock_id" id="paperStockId" value="{{ $initialPaperStockId }}">
							<input type="hidden" name="paper_stock_price" id="paperStockPrice" value="{{ $initialPaperStockPrice ?? 0 }}">
						</div>

						<div class="form-group addons-group">
							@forelse($addonGroups as $group)
								@if(strtolower(trim($group->label ?? '')) === 'size')
									@continue
								@endif
								<div class="addon-section" data-addon-type="{{ $group->type }}">
									<h4 class="addon-title">{{ trim(str_ireplace('Additional', '', $group->label)) }}</h4>
									<div class="feature-grid small addon-grid" data-addon-type="{{ $group->type }}">
										@forelse($group->items as $addon)
											<button
												type="button"
												class="feature-card selectable-card addon-card"
												data-id="{{ $addon->id }}"
												data-price="{{ $addon->price ?? 0 }}"
												data-type="{{ $addon->type }}"
												aria-pressed="{{ $addon->selected ? 'true' : 'false' }}"
											>
												<div class="feature-card-media">
													<img src="{{ $addon->image }}" alt="{{ $addon->name }}">
												</div>
												<div class="feature-card-info">
													<span class="feature-card-title">{{ $addon->name }}</span>
													<span class="feature-card-price">
														@if($addon->price !== null)
															₱{{ number_format($addon->price, 2) }}
														@else
															On request
														@endif
													</span>
												</div>
											</button>
										@empty
											<p class="muted">No add-ons available for this category.</p>
										@endforelse
									</div>
								</div>
							@empty
								<p class="muted">No add-ons available for this product.</p>
							@endforelse
						</div>
					</div>

					<div class="form-fixed-section form-fixed-section--bottom">
						<div class="payment-summary">
							<dl>
								<div class="mt-4 flex items-center justify-between text-base font-semibold border-t border-slate-200 pt-2">
									<dt class="text-slate-900">Total due</dt>
									<dd class="text-slate-900" data-order-total>{{ $formatMoney($grandTotal) }}</dd>
								</div>
							</dl>
						</div>

						<div class="delivery-info">
							<div class="delivery-group">
								<label for="estimatedDate" class="meta-label">Estimated day</label>
								<div class="delivery-inputs">
									<div class="delivery-row">
										<input type="date" id="estimatedDate" name="estimated_date" value="{{ $estimatedDeliveryDateFormatted }}" required>
										<span id="estimatedDateFinalLabel" class="final-date-label" aria-hidden="true"></span>
									</div>
									<p id="estimatedDateHint" class="date-hint">Choose an estimated date at least 10 days from today, up to 1 month ahead. Past dates are not allowed.</p>
									<div id="estimatedDateError" class="date-error" style="display: none;" aria-live="polite"></div>
									<p id="estimatedArrival" class="arrival-label" style="display:none;" aria-live="polite"></p>
								</div>
							</div>
						</div>

						<div class="action-buttons">
							<button type="button" id="addToCartBtn" class="primary-action" data-envelope-url="{{ $envelopeUrl }}" data-cart-url="{{ route('order.addtocart') }}">Add to cart</button>
							<a id="continueToCheckoutBtn" data-continue-checkout href="{{ $envelopeUrl }}" class="btn btn-secondary">Continue to checkout</a>
							<div id="paperSelectError" class="error-message" style="display:none; margin-left:8px;">Please select a paper type to continue.</div>
						</div>
					</div>
				</form>
			</div>
		</section>
	</div>

	<div id="finalStepToast" class="finalstep-toast" role="status" aria-live="polite" hidden></div>
</main>
@if(!empty($orderSummary))
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
			try {
				const minSummary = {
					productId: summaryData.productId ?? summaryData.product_id ?? null,
					quantity: summaryData.quantity ?? null,
					paymentMode: summaryData.paymentMode ?? summaryData.payment_mode ?? null,
					totalAmount: summaryData.totalAmount ?? summaryData.total_amount ?? null,
					shippingFee: summaryData.shippingFee ?? summaryData.shipping_fee ?? null,
					order_id: summaryData.order_id ?? summaryData.orderId ?? null,
				};
				window.sessionStorage.setItem('order_summary_payload', JSON.stringify(minSummary));
			} catch (e) {
				console.warn('Failed to save minimal order_summary_payload to sessionStorage:', e);
			}
		});
	</script>
@endif
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const dropdownControllers = [];
		const navToggle = document.getElementById('navToggle');
		const mobilePanel = document.getElementById('mobileNavPanel');

		const registerDropdown = ({ triggerId, menuId }) => {
			const trigger = document.getElementById(triggerId);
			const menu = document.getElementById(menuId);
			if (!trigger || !menu) return null;

			const indicator = trigger.querySelector('svg');

			const open = () => {
				menu.classList.remove('hidden');
				trigger.setAttribute('aria-expanded', 'true');
				indicator?.classList.add('rotate-180');
			};

			const close = () => {
				menu.classList.add('hidden');
				trigger.setAttribute('aria-expanded', 'false');
				indicator?.classList.remove('rotate-180');
			};

			trigger.addEventListener('click', (event) => {
				event.stopPropagation();
				const willOpen = menu.classList.contains('hidden');
				closeAllDropdowns();
				if (willOpen) open();
			});

			return { trigger, menu, close };
		};

		const closeAllDropdowns = () => dropdownControllers.forEach((controller) => controller.close());

		[
			{ triggerId: 'categoryToggle', menuId: 'categoryMenu' },
			{ triggerId: 'mobileCategoryToggle', menuId: 'mobileCategoryMenu' },
			{ triggerId: 'userDropdownBtn', menuId: 'userDropdownMenu' },
		].forEach((config) => {
			const controller = registerDropdown(config);
			if (controller) dropdownControllers.push(controller);
		});

		document.addEventListener('click', (event) => {
			dropdownControllers.forEach((controller) => {
				if (controller.menu.classList.contains('hidden')) return;
				if (controller.menu.contains(event.target) || controller.trigger.contains(event.target)) return;
				controller.close();
			});
		});

		document.addEventListener('keydown', (event) => {
			if (event.key !== 'Escape') return;
			closeAllDropdowns();
			if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
				mobilePanel.classList.add('hidden');
				navToggle?.setAttribute('aria-expanded', 'false');
			}
		});

		navToggle?.addEventListener('click', () => {
			const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
			navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
			mobilePanel?.classList.toggle('hidden', isOpen);
			if (!isOpen) closeAllDropdowns();
		});

		const storageKey = 'inkwise-finalstep';
		const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
		const navIcons = Array.from(document.querySelectorAll('.nav-icon-button'));

		const serverHasOrder = async () => {
			try {
				const response = await fetch('/order/summary.json', { method: 'GET', headers: { Accept: 'application/json' }, credentials: 'same-origin' });
				return response.ok;
			} catch (_error) {
				return false;
			}
		};

		const createOrderFromSummary = async (summary) => {
			if (!summary) return false;
			const productId = summary.productId ?? summary.product_id;
			if (!productId) return false;
			const quantity = Number(summary.quantity ?? 10);
			try {
				const response = await fetch('/order/cart/items', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
						...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
					},
					credentials: 'same-origin',
					body: JSON.stringify({ product_id: Number(productId), quantity }),
				});
				return response.ok;
			} catch (_error) {
				return false;
			}
		};

		navIcons.forEach((icon) => {
			if (icon.getAttribute('aria-disabled') === 'true') {
				icon.setAttribute('data-was-aria-disabled', 'true');
				icon.removeAttribute('aria-disabled');
				icon.style.pointerEvents = 'auto';
				icon.setAttribute('tabindex', '0');
				icon.setAttribute('role', 'button');
				icon.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						icon.click();
					}
				});
			}

			icon.addEventListener('click', async (event) => {
				event.preventDefault();
				try {
					if (await serverHasOrder()) {
						window.location.href = '/order/addtocart';
						return;
					}

					let summary = null;
					try {
						const raw = window.sessionStorage.getItem(storageKey);
						summary = raw ? JSON.parse(raw) : null;
					} catch (_error) {
						summary = null;
					}

					if (summary && (summary.productId || summary.product_id)) {
						const created = await createOrderFromSummary(summary);
						if (created) {
							window.location.href = '/order/addtocart';
							return;
						}
					}

					const href = icon.getAttribute('href');
					if (href && href !== '#') {
						window.location.href = href;
						return;
					}

					window.location.href = '/order/addtocart';
				} catch (_error) {
					window.location.href = '/order/addtocart';
				}
			});
		});
	});
</script>
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const quantityInput = document.getElementById('quantityInput');
		const priceDisplay = document.getElementById('priceDisplay');
		const quantityError = document.getElementById('quantityError');
		const paperStockPriceInput = document.getElementById('paperStockPrice');
		const addToCartBtn = document.getElementById('addToCartBtn');

		const MIN_QTY = 10;

		const updatePriceDisplay = () => {
			const qty = Math.max(0, parseInt(quantityInput.value) || 0);
			const perUnit = Number(paperStockPriceInput?.value ?? 0) || 0;
			const total = Number((perUnit * qty).toFixed(2));
			priceDisplay.textContent = '₱' + total.toFixed(2);

			if (qty < MIN_QTY) {
				quantityError.style.display = 'block';
				if (addToCartBtn) addToCartBtn.setAttribute('disabled', 'true');
			} else {
				quantityError.style.display = 'none';
				if (addToCartBtn) addToCartBtn.removeAttribute('disabled');
			}
		};

		quantityInput.addEventListener('input', updatePriceDisplay);

		// Update when paper selection changes
		document.querySelectorAll('.paper-stock-card').forEach((card) => {
			card.addEventListener('click', () => {
				const price = Number(card.dataset.price ?? 0);
				if (paperStockPriceInput) paperStockPriceInput.value = String(price);
				updatePriceDisplay();
			});
		});

		// Initial update
		updatePriceDisplay();
	});
</script>

<!-- Pre-order Confirmation Modal -->
<div id="preOrderModal" class="modal" style="display: none;" role="dialog" aria-labelledby="preOrderTitle" aria-describedby="preOrderMessage" aria-hidden="true">
	<div class="modal-backdrop" tabindex="-1"></div>
	<div class="modal-content">
		<h2 id="preOrderTitle" class="modal-title">Pre-order Confirmation</h2>
		<p id="preOrderMessage" class="modal-message">Pre-order: The selected material is currently unavailable. You may choose another paper type, or proceed with a pre-order with an estimated delivery of 15 days.</p>
		<div class="modal-actions">
			<button id="preOrderConfirm" class="primary-action" type="button">Confirm</button>
			<button id="preOrderCancel" class="secondary-action" type="button">Cancel</button>
		</div>
	</div>
</div>

</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function () {
	// no DOM container required; we will replace the artwork images directly

	let saved = null;
	try {
		// Prefer explicit saved-template key
		saved = JSON.parse(window.sessionStorage.getItem('inkwise-saved-template') || 'null');
	} catch (err) {
		saved = null;
	}

	// Fallbacks: finalstep summary, order_summary_payload, or legacy keys
	if (!saved) {
		const keys = ['inkwise-finalstep', 'order_summary_payload', 'inkwise-addtocart'];
		for (const k of keys) {
			try {
				const summary = JSON.parse(window.sessionStorage.getItem(k) || 'null');
				if (summary) {
					if (summary.template) { saved = summary.template; break; }
					if (summary.metadata && summary.metadata.template) { saved = summary.metadata.template; break; }
					if (summary.previewImage || (Array.isArray(summary.preview_images) && summary.preview_images.length)) {
						saved = {
							template_name: summary.productName || summary.template_name || 'Saved template',
							preview_image: summary.previewImage || (Array.isArray(summary.preview_images) ? summary.preview_images[0] : null),
							preview_images: summary.preview_images || summary.previewImages || [],
						};
						break;
					}
				}
			} catch (e) {
				// ignore parse errors
			}
		}
	}

	if (!saved) return;

	const resolvePreviewUrl = (candidate) => {
		if (!candidate || typeof candidate !== 'string') return '';
		const trimmed = candidate.trim();
		if (trimmed === '') return '';
		if (/^(data:|https?:|\/\/|\/)/i.test(trimmed)) return trimmed;
		// assume storage path -> prefix with /storage/
		return '/storage/' + trimmed.replace(/^\/+/, '');
	};

	const candidate = saved.preview_image || (Array.isArray(saved.preview_images) ? saved.preview_images[0] : '') || saved.preview || saved.previewImage || '';
	const resolvedCandidate = resolvePreviewUrl(candidate);

	let backCandidate = '';

	// Replace artwork preview images in the preview card with the saved preview(s)
		try {
			const frontImg = document.querySelector('.card-flip .card-face.front img');
			const backImg = document.querySelector('.card-flip .card-face.back img');
		if (frontImg && resolvedCandidate) {
			frontImg.src = resolvedCandidate;
		}

		// If a second preview exists, use it for the back face
		if (Array.isArray(saved.preview_images) && saved.preview_images.length > 1) {
			backCandidate = resolvePreviewUrl(saved.preview_images[1]);
		} else if (saved.back_preview) {
			backCandidate = resolvePreviewUrl(saved.back_preview);
		}

		if (backImg) {
			if (backCandidate) {
				backImg.src = backCandidate;
			} else {
				// No explicit back preview — keep the server-provided back image (do nothing)
			}
			// Ensure the back face is visible so users can toggle to it
			backImg.closest('.card-face')?.classList.remove('hidden');
		}
	} catch (e) {
		console.warn('Failed to replace preview images with saved template', e);
	}

	// Persist a small finalstep payload to sessionStorage so other pages (review/checkout)
	// can detect and render the customer's edited template preview.
	try {
		const finalStepKey = 'inkwise-finalstep';
		const savedTemplateKey = 'inkwise-saved-template';
		const productName = document.querySelector('main')?.dataset?.productName || document.title || 'Saved template';
		const previewImagesArr = [];
		if (Array.isArray(saved?.preview_images) && saved.preview_images.length) {
			for (const p of saved.preview_images) {
				const r = resolvePreviewUrl(p);
				if (r) previewImagesArr.push(r);
			}
		}
		if (resolvedCandidate && !previewImagesArr.length) previewImagesArr.push(resolvedCandidate);
		if (backCandidate && previewImagesArr.length === 1 && previewImagesArr[0] !== backCandidate) previewImagesArr.push(backCandidate);

		const finalstepPayload = {
			templateName: productName,
			previewImage: previewImagesArr[0] || resolvedCandidate || '',
			previewImages: previewImagesArr,
			metadata: {
				template: { name: productName }
			}
		};

		try { window.sessionStorage.setItem(finalStepKey, JSON.stringify(finalstepPayload)); } catch (e) { /* ignore storage errors */ }
		try {
			const short = { id: null, name: finalstepPayload.templateName, preview: finalstepPayload.previewImage };
			window.sessionStorage.setItem(savedTemplateKey, JSON.stringify(short));
			window.savedCustomerTemplate = short;
		} catch (e) { /* ignore */ }
	} catch (e) {
		// non-fatal
	}

	// If the client-side preview is still the example placeholder, try to fetch
	// the server-side session summary which may contain persisted preview images.
	(async () => {
		try {
			const resp = await fetch('/order/summary.json', { method: 'GET', headers: { Accept: 'application/json' }, credentials: 'same-origin' });
			if (!resp.ok) return;
			const json = await resp.json();
			const srv = json?.data ?? null;
			if (!srv) return;
			const srvImages = Array.isArray(srv.previewImages)
				? srv.previewImages.filter(Boolean) 
				: (Array.isArray(srv.preview_images) ? srv.preview_images.filter(Boolean) : []);
			const srvPrimary = srv.previewImage || srv.preview_image || srvImages[0] || null;
			if (!srvPrimary) return;
			// ignore obvious example placeholders
			if (String(srvPrimary).includes('example.com')) return;

			const resolved = (function (candidate) {
				if (!candidate) return '';
				if (/^(data:|https?:|\/\/|\/)/i.test(candidate)) return candidate;
				return '/storage/' + String(candidate).replace(/^\/+/, '');
			})(srvPrimary);

			if (resolved) {
				try { window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(Object.assign({}, JSON.parse(window.sessionStorage.getItem('inkwise-finalstep') || '{}'), { previewImage: resolved, previewImages: srvImages.map((s) => s && String(s).includes('example.com') ? null : (s && /^(data:|https?:|\/\/|\/)/i.test(s) ? s : '/storage/' + String(s).replace(/^\/+/, ''))).filter(Boolean) }))); } catch (e) { /* ignore */ }
				try { window.sessionStorage.setItem('inkwise-saved-template', JSON.stringify({ id: null, name: srv.productName || document.title || 'Saved template', preview: resolved })); } catch (e) { /* ignore */ }
				const frontImg = document.querySelector('.card-flip .card-face.front img');
				const backImg = document.querySelector('.card-flip .card-face.back img');
				if (frontImg) frontImg.src = resolved;
				if (backImg && srvImages.length > 1) backImg.src = (srvImages[1].includes('example.com') ? backImg.src : (srvImages[1]));
			}
		} catch (e) {
			// non-fatal
		}
	})();
	});
	</script>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		// Accessible modal helpers for preOrderModal
		window.showPreOrderModal = function () {
			const modal = document.getElementById('preOrderModal');
			if (!modal) return;
			modal.removeAttribute('aria-hidden');
			try { modal.inert = false; } catch (e) { /* inert may not be supported */ }
			modal.style.display = 'block';
			const btn = modal.querySelector('#preOrderConfirm');
			if (btn) btn.focus();
		};

		window.hidePreOrderModal = function (returnFocusSelector) {
			const modal = document.getElementById('preOrderModal');
			if (!modal) return;
			modal.setAttribute('aria-hidden', 'true');
			try { modal.inert = true; } catch (e) { /* inert may not be supported */ }
			modal.style.display = 'none';
			if (returnFocusSelector) {
				const opener = document.querySelector(returnFocusSelector);
				if (opener) opener.focus();
			}
		};

		// Wire modal action buttons
		const confirmBtn = document.getElementById('preOrderConfirm');
		const cancelBtn = document.getElementById('preOrderCancel');
		if (confirmBtn) {
			confirmBtn.addEventListener('click', function () {
				hidePreOrderModal();
			});
		}
		if (cancelBtn) {
			cancelBtn.addEventListener('click', function () {
				hidePreOrderModal();
			});
		}

		// Attach open handlers to any element that may open the modal
		document.querySelectorAll('[data-open-preorder]').forEach(el => {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				window.showPreOrderModal();
			});
		});
	});
	</script>
