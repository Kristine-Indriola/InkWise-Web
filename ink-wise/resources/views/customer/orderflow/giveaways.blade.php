<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Giveaway Selection — InkWise</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-giveaways.css') }}">
	<link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
	<link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
</head>
<body>
	@php
		$hasProducts = isset($products) && $products->count();
	@endphp
	<main class="giveaways-shell"
		  data-summary-url="{{ route('order.summary') }}"
		  data-summary-api="{{ route('order.summary') }}"
		  data-storage-key="inkwise-finalstep">
		<header class="giveaways-header">
			<div class="giveaways-header__content">
				<a href="{{ route('order.envelope') }}" class="giveaways-header__back" aria-label="Back to envelope options">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
					Back to envelopes
				</a>
				<h1>Choose wedding giveaways</h1>
				<p>Curate thoughtful tokens to complement your invitation set. Pick from our handcrafted options or upload your inspiration for a custom quote.</p>
			</div>
		</header>

		<div class="giveaways-layout">
			<div class="giveaways-main">
				<section class="catalog" aria-label="Available giveaways">
					@if($hasProducts)
						<div id="giveawaysGrid" class="giveaways-grid" role="list">
							@foreach($products as $product)
							@php
								$uploads = $product->uploads ?? collect();
								if (!($uploads instanceof \Illuminate\Support\Collection)) {
									$uploads = collect($uploads);
								}
								$firstUpload = $uploads->first();

								$previewSrc = null;
								if ($firstUpload && str_starts_with($firstUpload->mime_type ?? '', 'image/')) {
									$previewSrc = \Illuminate\Support\Facades\Storage::disk('public')->url('uploads/products/' . $product->id . '/' . $firstUpload->filename);
								}

								if (!$previewSrc && $product->images) {
									$imageRecord = $product->images;
									$candidate = $imageRecord->front ?? $imageRecord->preview ?? $imageRecord->back ?? null;
									if ($candidate) {
										$previewSrc = \App\Support\ImageResolver::url($candidate);
									}
								}

								if (!$previewSrc && $product->image) {
									$previewSrc = \App\Support\ImageResolver::url($product->image);
								}

								if (!$previewSrc && $product->template) {
									$templatePreview = $product->template->preview_front
										?? $product->template->front_image
										?? $product->template->preview
										?? $product->template->image
										?? null;

									if ($templatePreview) {
										$previewSrc = preg_match('/^(https?:)?\/\//i', $templatePreview) || str_starts_with($templatePreview, '/')
											? $templatePreview
											: \Illuminate\Support\Facades\Storage::url($templatePreview);
									}
								}

								if (!$previewSrc) {
									$previewSrc = asset('images/no-image.png');
								}

								$primaryBulk = $product->bulkOrders?->sortBy('min_qty')->first();
								$unitPrice = $product->base_price
									?? $primaryBulk?->price_per_unit
									?? optional($product->template)->base_price
									?? 0;

								$minQty = $primaryBulk?->min_qty ?: 50;
								$stepQty = max(1, $primaryBulk?->min_qty ?: 10);
								$leadTime = $product->lead_time ?? ($product->template->lead_time ?? 'Made to order');
								$eventLabel = $product->event_type ?: 'General';
								$materials = $product->materials?->map(function ($materialItem) {
									return $materialItem->material->material_name
										?? $materialItem->item
										?? $materialItem->type;
								})->filter()->unique()->take(3)->implode(', ');
								$description = \Illuminate\Support\Str::limit(strip_tags($product->description ?? 'Handmade giveaway curated for memorable celebrations.'), 160);
								$defaultQty = max($minQty, 25);
							@endphp
							<article class="giveaway-card"
									 role="listitem"
									 data-product-id="{{ $product->id }}"
									 data-product-name="{{ e($product->name) }}"
									 data-event-type="{{ e($eventLabel) }}"
									 data-lead-time="{{ e($leadTime) }}"
									 data-product-price="{{ $unitPrice }}"
									 data-product-image="{{ e($previewSrc) }}"
									 data-default-qty="{{ $defaultQty }}"
									 data-step="{{ $stepQty }}"
									 data-description="{{ e($description) }}">
								<div class="giveaway-card__media">
									<button type="button"
											class="favorite-toggle"
											aria-label="Save {{ $product->name }}"
											data-product-id="{{ $product->id }}">
										<svg viewBox="0 0 24 24" aria-hidden="true">
											<path d="M12 21s-6.5-4.35-9-8.5C1.33 9.5 2.15 6 5 4.8 7.38 3.77 9.55 4.89 12 7.4c2.45-2.51 4.62-3.63 7-2.6 2.85 1.2 3.68 4.7 2 7.7-2.5 4.15-9 8.5-9 8.5Z" />
										</svg>
									</button>
									<img src="{{ $previewSrc }}"
										 alt="{{ $product->name }} giveaway preview"
										 class="giveaway-card__image preview-trigger"
										 data-preview-url="{{ route('product.preview', $product->id) }}"
										 loading="lazy">
								</div>
								<div class="giveaway-card__body">
									<div class="giveaway-card__meta-top">
										<span class="meta-badge">{{ $eventLabel }}</span>
										@if(!empty($product->theme_style))
											<span class="meta-chip">{{ $product->theme_style }}</span>
										@endif
									</div>
									<h2>{{ $product->name }}</h2>
									<p>{{ $description }}</p>
									<ul class="meta-list">
										<li><strong>Lead time:</strong> {{ $leadTime }}</li>
										@if($materials)
											<li><strong>Materials:</strong> {{ $materials }}</li>
										@endif
										@if($product->date_available)
											<li><strong>Available:</strong> {{ \Illuminate\Support\Carbon::parse($product->date_available)->format('M d, Y') }}</li>
										@endif
									</ul>
									<div class="giveaway-card__pricing">
										@if($unitPrice)
											<div class="price-label">₱{{ number_format($unitPrice, 2) }} <span>/ piece</span></div>
										@else
											<div class="price-label is-muted">Pricing on request</div>
										@endif
										<label class="quantity-control">
											<span>Quantity</span>
											<input type="number"
												   min="{{ max(1, $stepQty) }}"
												   step="{{ max(1, $stepQty) }}"
												   value="{{ $defaultQty }}"
												   class="giveaway-card__quantity"
												   inputmode="numeric"
												   pattern="[0-9]*">
										</label>
										<div class="qty-total" data-total-display>
											{{ $defaultQty }} pcs — ₱{{ number_format(($unitPrice ?: 0) * $defaultQty, 2) }}
										</div>
									</div>
									<div class="giveaway-card__actions">
										<button type="button" class="giveaway-card__action giveaway-card__select">Add to order</button>
										<button type="button" class="giveaway-card__action secondary preview-trigger" data-preview-url="{{ route('product.preview', $product->id) }}">Quick preview</button>
									</div>
								</div>
							</article>
							@endforeach
						</div>
						<div class="giveaways-empty is-dynamic" id="giveawaysEmptyState" hidden>
							<h2>No giveaways match your filters</h2>
							<p>Adjust your search or upload inspiration below so we can craft something custom for you.</p>
						</div>
					@else
						<div class="giveaways-empty" id="giveawaysEmptyState">
							<h2>No giveaways available right now</h2>
							<p>We’re refreshing our collections. Check back soon or upload your inspiration below for a custom quote.</p>
						</div>
					@endif
				</section>

			</div>

			<aside class="giveaways-sidebar" aria-label="Selected giveaway summary">
				<div class="summary-card">
					<header class="summary-card__header">
						<h2>Your giveaway selection</h2>
						<span class="summary-badge" id="giveawaysStatusBadge">Pending</span>
					</header>
					<div class="summary-card__body" id="giveawaySummaryBody">
						<p class="summary-placeholder">Choose a giveaway to see its details here.</p>
					</div>
					<button type="button" id="giveawaysRemoveSelection" class="summary-remove" hidden>Remove selection</button>
				</div>

				<div class="summary-actions">
					<button type="button" id="skipGiveawaysBtn" class="btn-secondary" data-target="{{ route('order.summary') }}">Skip giveaways</button>
					<button type="button" id="giveawaysContinueBtn" class="btn-primary" data-target="{{ route('order.summary') }}" disabled>Continue to order summary</button>
				</div>
				<p class="summary-note">You can revisit this step before finalizing your order. Your progress is saved automatically.</p>
			</aside>
		</div>

		<div id="giveawayToast" class="giveaway-toast" aria-live="polite" hidden></div>
	</main>

	<div id="productPreviewOverlay" class="preview-overlay" role="dialog" aria-modal="true" aria-hidden="true">
		<div class="preview-frame-wrapper">
			<div class="preview-frame-header">
				<button type="button" class="preview-close-btn" id="productPreviewClose" aria-label="Close preview">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
					Close
				</button>
			</div>
			<div class="preview-frame-body">
				<iframe id="productPreviewFrame" title="Product preview"></iframe>
			</div>
		</div>
	</div>

	<script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
	<script src="{{ asset('js/customer/orderflow-giveaways.js') }}" defer></script>
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
