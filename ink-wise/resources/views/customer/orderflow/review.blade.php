<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Design Review &mdash; InkWise</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/review.css') }}">
	<script src="{{ asset('js/customer/review.js') }}" defer></script>
</head>
<body>
@php
	$product = $product ?? null;
	$proof = $proof ?? null;
	$uploads = $product->uploads ?? collect();
	$images = $product->product_images ?? $product->images ?? optional($proof)->images ?? null;
	$templateRef = $product->template ?? optional($proof)->template ?? null;

	$frontImage = $finalArtwork['front'] ?? $finalArtworkFront ?? null;
	$backImage = $finalArtwork['back'] ?? $finalArtworkBack ?? null;

	$resolveImage = function ($candidate) {
		if (!$candidate) {
			return null;
		}

		if (preg_match('/^(https?:)?\/\//i', $candidate) || str_starts_with($candidate, '/')) {
			return $candidate;
		}

		try {
			return Illuminate\Support\Facades\Storage::url($candidate);
		} catch (\Throwable $e) {
			return null;
		}
	};

	if (!$frontImage && $images) {
		$frontImage = $resolveImage($images->final_front ?? $images->front ?? $images->preview ?? null);
	}

	if (!$backImage && $images) {
		$backImage = $resolveImage($images->final_back ?? $images->back ?? null);
	}

	if (!$frontImage && $templateRef) {
		$frontImage = $resolveImage($templateRef->preview_front ?? $templateRef->front_image ?? $templateRef->preview ?? $templateRef->image ?? null);
	}

	if (!$backImage && $templateRef) {
		$backImage = $resolveImage($templateRef->preview_back ?? $templateRef->back_image ?? null);
	}

	if (!$frontImage && $uploads->isNotEmpty()) {
		$first = $uploads->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($first) {
			$frontImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . $first->filename);
		}
	}

	if (!$backImage && $uploads->count() > 1) {
		$second = $uploads->skip(1)->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($second) {
			$backImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . $second->filename);
		}
	}

	$fallbackImage = asset('images/placeholder.png');
	$frontImage = $frontImage ?? $fallbackImage;
	$backImage = $backImage ?? $frontImage;

	$placeholderItems = collect($placeholderItems ?? $unfilledPlaceholders ?? [])
		->filter()
		->values();

	if ($placeholderItems->isEmpty()) {
		$placeholderItems = collect([
			'Front: BROOKLYN, NY',
			'Front: KENDRA AND ANDREW',
			'Front: 06.28.26',
		]);
	}

	$continueHref = $continueHref ?? $continueUrl ?? route('order.finalstep');
	$editHref = $editHref ?? route('design.edit');
@endphp

	<div class="review-shell">
		<section class="preview-card">
			<div class="preview-layout">
				<div class="review-panel">
					<div class="sidebar-heading">
						<h1>Review your design</h1>
						<p>It will be printed like this preview. Make sure you are happy before continuing.</p>
					</div>

					<ul class="review-checklist">
						<li>Are the text and images clear and easy to read?</li>
						<li>Do the design elements fit in the safety area?</li>
						<li>Does the background fill out to the edges?</li>
						<li>Is everything spelled correctly?</li>
					</ul>

					<div class="warning-card" role="alert">
						<strong>Empty items won't be printed</strong>
						<span>We noticed you didn't edit the following placeholder items.</span>
						<span class="sticky-notice">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path d="M12 9V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
								<path d="M12 17H12.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
								<path d="M10.29 3.85997L2.82002 17C2.64552 17.3028 2.55312 17.6475 2.55127 17.9986C2.54942 18.3497 2.63816 18.6953 2.8096 18.9998C2.98105 19.3044 3.2299 19.5561 3.53053 19.7307C3.83117 19.9053 4.17349 19.9965 4.52002 20H19.48C19.8265 19.9965 20.1688 19.9053 20.4695 19.7307C20.7701 19.5561 21.019 19.3044 21.1904 18.9998C21.3619 18.6953 21.4506 18.3497 21.4487 17.9986C21.4469 17.6475 21.3545 17.3028 21.18 17L13.71 3.85997C13.5318 3.56654 13.279 3.32849 12.9788 3.17099C12.6787 3.01348 12.3422 2.94223 12.0037 2.96504C11.6652 2.98785 11.3398 3.10479 11.061 3.30328C10.7822 3.50176 10.5616 3.77434 10.42 4.08997" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<span>Warning: Any unchanged sample text or images will not be printed.</span>
						</span>

						<button type="button"
								class="placeholder-toggle"
								data-placeholder-toggle="#placeholderList"
								aria-expanded="false"
								aria-controls="placeholderList"
								data-label-expanded="Hide placeholder items"
								data-label-collapsed="View placeholder items">
							<span data-placeholder-label>View placeholder items</span>
							<span class="placeholder-count">(<span data-placeholder-count>0</span>)</span>
						</button>

						<ul id="placeholderList" class="placeholder-list" data-expanded="false">
							@foreach($placeholderItems as $item)
								<li>{{ $item }}</li>
							@endforeach
						</ul>
					</div>
				</div>

				<div class="preview-display">
					<div class="preview-header">
						<h2>Final artwork preview</h2>
						<div class="preview-toggle" role="group" aria-label="Preview sides">
							<button type="button" class="active" data-face="front" aria-pressed="true">Front</button>
							<button type="button" data-face="back" aria-pressed="false">Back</button>
						</div>
					</div>

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

					<div class="preview-meta">
						<div><span class="meta-label">Product:</span> {{ $product->name ?? optional($templateRef)->name ?? 'Custom Invitation' }}</div>
						@if(isset($product->size))
							<div><span class="meta-label">Size:</span> {{ $product->size }}</div>
						@endif
						@if(isset($product->paper_stock))
							<div><span class="meta-label">Paper stock:</span> {{ $product->paper_stock }}</div>
						@endif
					</div>
				</div>
			</div>
		</section>

		<section class="confirm-card">
			<label>
				<input type="checkbox" id="approvalCheckbox">
				<span>I have reviewed and approve my design.</span>
			</label>

			<div class="confirm-actions">
	    <button type="button"
	    	id="continueBtn"
	    	data-href="{{ $continueHref ?? '' }}"
	    	disabled
	    >
					Continue
				</button>

				<a href="{{ $editHref }}" class="edit-link">Edit my design</a>
			</div>
		</section>
	</div>

	<div id="reviewToast" class="review-toast" role="status" aria-live="polite"></div>
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
