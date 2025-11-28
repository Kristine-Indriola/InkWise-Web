<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add to Cart — InkWise</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-addtocart.css') }}">
</head>
<body data-product-id="{{ $product->id ?? '' }}">
@php
	// Minimal view setup for addtocart
	$product = $product ?? null;
	$proof = $proof ?? null;

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

	$resolvedProductName = $product->name ?? optional($templateRef)->name ?? 'Custom Invitation';

	try {
		$envelopeUrl = route('order.envelope');
	} catch (\Throwable $_e) {
		$envelopeUrl = url('/order/envelope');
	}

	try {
		$finalStepUrl = route('order.finalstep');
	} catch (\Throwable $_e) {
		$finalStepUrl = url('/order/finalstep');
	}
@endphp
<main class="addtocart-shell" data-storage-key="inkwise-addtocart" data-envelope-url="{{ $envelopeUrl }}" data-product-id="{{ $product->id ?? '' }}" data-product-name="{{ $resolvedProductName }}">
	<header class="addtocart-header">
		<div class="addtocart-header__content">
			<a href="{{ $finalStepUrl }}" class="addtocart-header__back" aria-label="Back to final step">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
				Back to options
			</a>
			<h1>Add to cart</h1>
			<p>Review your final design and add it to your cart to proceed with envelope options.</p>
		</div>
	</header>

	<div class="addtocart-layout">
		<section class="addtocart-preview" data-product-name="{{ $resolvedProductName }}">
			<div class="addtocart-card preview-card">
				<header class="preview-card__header">
					<span class="preview-card__badge">Final artwork</span>
					<div class="preview-toggle" role="group" aria-label="Preview sides">
						<button type="button" class="active" data-side="front" aria-pressed="true">Front</button>
						<button type="button" data-side="back" aria-pressed="false">Back</button>
					</div>
				</header>

				<div class="card-flip">
					<div class="inner">
						<div class="card-face front">
							<img src="{{ $frontImage }}" alt="Front design preview" loading="lazy">
						</div>
						<div class="card-face back">
							<img src="{{ $backImage }}" alt="Back design preview" loading="lazy">
						</div>
					</div>
				</div>

				<div class="preview-meta">
					<dl>
						<div class="meta-item">
							<dt class="meta-label">Product</dt>
							<dd class="meta-value">{{ $resolvedProductName }}</dd>
						</div>
						@if($orderSummary && isset($orderSummary['quantity']))
						<div class="meta-item">
							<dt class="meta-label">Quantity</dt>
							<dd class="meta-value">{{ number_format($orderSummary['quantity']) }}</dd>
						</div>
						@endif
						@if($orderSummary && isset($orderSummary['totalAmount']))
						<div class="meta-item">
							<dt class="meta-label">Total</dt>
							<dd class="meta-value">₱{{ number_format($orderSummary['totalAmount'], 2) }}</dd>
						</div>
						@endif
					</dl>
				</div>
			</div>
		</section>

		<section class="addtocart-panel">
			<div class="addtocart-card action-card">
				<div class="action-card__content">
					<h2>Ready to add to cart?</h2>
					<p>Your custom design is ready. Click the button below to add it to your cart and continue with envelope options.</p>

					<div class="action-buttons">
						<a href="{{ $finalStepUrl }}" class="secondary-action">Back to options</a>
						<button type="button" id="addToCartBtn" class="primary-action" data-envelope-url="{{ $envelopeUrl }}">Add to cart</button>
					</div>
				</div>
			</div>
		</section>
	</div>

	<div id="addToCartToast" class="addtocart-toast" role="status" aria-live="polite" hidden></div>
</main>

@if(!empty($orderSummary))
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
		window.sessionStorage.setItem('inkwise-addtocart', JSON.stringify(summaryData));
	});
</script>
@endif

<script src="{{ asset('js/customer/orderflow-addtocart.js') }}" defer></script>
</body>
</html>
