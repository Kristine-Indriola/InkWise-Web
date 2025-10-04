<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Finalize Order — InkWise</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-finalstep.css') }}">
	<script src="{{ asset('js/customer/orderflow-finalstep.js') }}" defer></script>
</head>
<body data-product-id="{{ $product->id ?? '' }}">
@php
	// Minimal, robust view setup for finalstep — resolve product/template if provided
	$product = $product ?? null;
	$proof = $proof ?? null;

	try {
		$req = request();
		if (!$product) {
			if ($req->has('product_id')) {
				try {
					$product = \App\Models\Product::with(['uploads', 'product_images', 'template', 'paperStocks', 'addons'])->find($req->get('product_id'));
				} catch (\Throwable $_e) {
					try {
						$row = \Illuminate\Support\Facades\DB::table('products')->where('id', $req->get('product_id'))->first();
						if ($row) $product = $row;
					} catch (\Throwable $_e) {
						// ignore
					}
				}
			}

			if (!$product && $req->has('template_id')) {
				try {
					$templateRef = \App\Models\Template::with(['preview_front', 'preview_back'])->find($req->get('template_id')) ?? ($templateRef ?? null);
				} catch (\Throwable $_e) {
					try {
						$tpl = \Illuminate\Support\Facades\DB::table('templates')->where('id', $req->get('template_id'))->first();
						if ($tpl) $templateRef = $tpl;
					} catch (\Throwable $_e) {
						// ignore
					}
				}
			}
		}
	} catch (\Throwable $e) {
		// swallow errors in view, proceed with fallbacks
	}

	$uploads = $product->uploads ?? collect();
	$images = $product->product_images ?? $product->images ?? optional($proof)->images ?? null;
	$templateRef = $product->template ?? ($templateRef ?? optional($proof)->template ?? null);

	$frontImage = $finalArtwork['front'] ?? ($finalArtworkFront ?? null);
	$backImage = $finalArtwork['back'] ?? ($finalArtworkBack ?? null);

	$resolveImage = function ($candidate) {
		if (!$candidate) return null;
		if (preg_match('/^(https?:)?\/\//i', $candidate) || str_starts_with($candidate, '/')) return $candidate;
		try { return Illuminate\Support\Facades\Storage::url($candidate); } catch (\Throwable $e) { return null; }
	};

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

	$quantityNote = 'Select bulk quantities in increments of 10.';
	if ($minQuantity !== null && $maxQuantity !== null) {
		$quantityNote = $minQuantity === $maxQuantity
			? 'Available bulk quantity: ' . number_format($minQuantity) . '.'
			: 'Select bulk quantities from ' . number_format($minQuantity) . ' up to ' . number_format($maxQuantity) . ' (increments of 10).';
	}

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
@endphp
<main class="finalstep-shell" data-storage-key="inkwise-finalstep" data-envelope-url="{{ $envelopeUrl }}" data-save-url="{{ $finalStepSaveUrl }}" data-fallback-samples="false" data-product-id="{{ $product->id ?? '' }}" data-product-name="{{ $resolvedProductName }}">
	<header class="finalstep-header">
		<div class="finalstep-header__content">
			<a href="{{ $reviewUrl }}" class="finalstep-header__back" aria-label="Back to review">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
				Back to review
			</a>
			<h1>Finalize your order</h1>
			<p>Review your artwork, choose finishing touches, and confirm the final details before checkout.</p>
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
							<img src="{{ $frontImage }}" alt="Front of your design">
						</div>
						<div class="card-face back">
							<img src="{{ $backImage }}" alt="Back of your design">
						</div>
					</div>
				</div>
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
					<p>Pick quantities, paper stocks, and optional finishes. We’ll calculate totals automatically.</p>
				</header>
				<form id="finalOrderForm" class="finalstep-form" novalidate>
					<div class="form-fixed-section">
						<div class="form-group">
							<label for="quantitySelect">Quantity (bulk — in tens)</label>
							<select id="quantitySelect" name="quantity" required>
								@foreach ($quantityOptions as $option)
									<option
										value="{{ $option->value }}"
										data-price="{{ $option->price }}"
										{{ $selectedQuantity && (string) $selectedQuantity === (string) $option->value ? 'selected' : '' }}
									>
										{{ $option->label }} — ₱{{ number_format($option->price, 2) }}
									</option>
								@endforeach
							</select>
							<p class="bulk-note">{{ $quantityNote }}</p>
						</div>
					</div>

					<div class="form-scroll-section">
						<div class="form-group paper-stocks-group">
							<label>Paper stock</label>
							<p class="selection-hint">Choose your preferred stock.</p>
							<div class="feature-grid small">
								@forelse($paperStocks as $stock)
									<button
										type="button"
										class="feature-card selectable-card paper-stock-card"
										data-id="{{ $stock->id }}"
										data-price="{{ $stock->price ?? 0 }}"
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
							<label>Add-ons</label>
							<p class="selection-hint">Optional finishes and trim options.</p>
							@forelse($addonGroups as $group)
								<div class="addon-section" data-addon-type="{{ $group->type }}">
									<h4 class="addon-title">{{ $group->label }}</h4>
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
						<div class="order-meta">
							<div class="order-total">
								<span class="meta-label">Total</span>
								<span class="meta-value" data-order-total>₱0.00</span>
							</div>
						</div>

						<div class="delivery-info">
							<span class="meta-label">Estimated delivery</span>
							<span class="meta-value">{{ $estimatedDeliveryDate }}</span>
						</div>

						<button type="button" id="addToCartBtn" class="primary-action" data-envelope-url="{{ $envelopeUrl }}">Add to cart</button>
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
			window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
		});
	</script>
@endif
</body>
</html>
