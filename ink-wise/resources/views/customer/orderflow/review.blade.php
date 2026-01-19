<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
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
	$summaryData = $orderSummary ?? [];
	if (is_array($summaryData) && !isset($summaryData['design'])) {
		$summaryData['design'] = $summaryData['metadata']['design'] ?? [];
	}

	// Normalize preview images to handle both camelCase and snake_case keys
	$summaryPreviewImages = [];
	if (is_array($summaryData)) {
		$summaryPreviewImages = $summaryData['previewImages'] ?? $summaryData['preview_images'] ?? [];
		if (!is_array($summaryPreviewImages)) {
			$summaryPreviewImages = [];
		}
		// Promote snake_case to camelCase so window.summaryData matches
		if (!isset($summaryData['previewImages']) && isset($summaryData['preview_images'])) {
			$summaryData['previewImages'] = $summaryPreviewImages;
		}
		// Ensure a primary previewImage exists for consumers expecting a single image
		if (empty($summaryData['previewImage']) && !empty($summaryPreviewImages[0])) {
			$summaryData['previewImage'] = $summaryPreviewImages[0];
		}
	}

	if (!$frontImage && is_array($summaryData) && !empty($summaryData['previewImage'])) {
		$frontImage = $summaryData['previewImage'];
	}

	if (!$frontImage && !empty($summaryPreviewImages[0])) {
		$frontImage = $summaryPreviewImages[0];
	}

	if ((!$backImage || $backImage === $frontImage) && !empty($summaryPreviewImages[1])) {
		$backImage = $summaryPreviewImages[1];
	}

	if (empty($backImage) && !empty($summaryPreviewImages[0])) {
		$backImage = $summaryPreviewImages[0];
	}

	$resolveImage = function ($candidate) {
		if (!$candidate) {
			return null;
		}

		// Handle data URLs (base64 encoded images from design studio)
		if (str_starts_with($candidate, 'data:')) {
			return $candidate;
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

	// Normalize any selected previews so relative saved paths resolve via /storage/
	$frontImage = $resolveImage($frontImage);
	$backImage = $resolveImage($backImage);

	// Load from REVIEW2 folder if available
	$review2Path = 'templates/REVIEW2';
	if (Illuminate\Support\Facades\Storage::disk('public')->exists($review2Path . '/preview.png')) {
		$backImage = Illuminate\Support\Facades\Storage::url($review2Path . '/preview.png');
	}
	if (Illuminate\Support\Facades\Storage::disk('public')->exists($review2Path . '/design.svg')) {
		$svgContent = Illuminate\Support\Facades\Storage::disk('public')->get($review2Path . '/design.svg');
		$backImage = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
	}

	// DEBUG - Start
	\Log::debug('BLADE DEBUG - customerReview check', [
		'has_customerReview' => $customerReview ? 'YES' : 'NO',
		'customerReview_id' => $customerReview?->id ?? 'NULL',
		'design_svg_length' => $customerReview ? strlen($customerReview->design_svg ?? '') : 0,
		'frontImage_BEFORE' => substr($frontImage ?? 'NULL', 0, 100),
	]);
	// DEBUG - End
	
	if ($customerReview && !empty($customerReview->design_svg)) {
		$frontImage = 'data:image/svg+xml;base64,' . base64_encode($customerReview->design_svg);
		\Log::debug('BLADE - Using design_svg for frontImage', [
			'frontImage_length' => strlen($frontImage),
			'frontImage_starts_with' => substr($frontImage, 0, 50),
		]);
	}
	
	// Log what frontImage is AFTER the customerReview check
	\Log::debug('BLADE DEBUG - frontImage AFTER customerReview block', [
		'frontImage_starts_with' => substr($frontImage ?? 'NULL', 0, 100),
	]);

	// Validate that customer review preview_image exists and is a valid SVG before using it
	if ($customerReview && empty($customerReview->design_svg) && !empty($customerReview->preview_image)) {
		$previewPath = $customerReview->preview_image;
		$isValidPreview = false;
		$svgContent = null;

		// Check if the file exists in storage and is a valid SVG (starts with < or has proper header)
		if (Illuminate\Support\Facades\Storage::disk('public')->exists($previewPath)) {
			try {
				$contents = Illuminate\Support\Facades\Storage::disk('public')->get($previewPath);
				// Check if it's a valid SVG (should start with '<' or '<?xml')
				$trimmed = ltrim($contents);
				if (str_starts_with($trimmed, '<') || str_starts_with($trimmed, '<?xml')) {
					$isValidPreview = true;
					$svgContent = $contents;
				}
			} catch (\Throwable $e) {
				// File read failed, not valid
			}
		}

		if ($isValidPreview && $svgContent) {
			// Convert SVG to base64 data URL to avoid cross-origin issues with external image references
			$frontImage = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
		}
	}

	// Load back SVG from template if available
	$backSvg = null;
	if ($templateRef && !empty($templateRef->back_svg_path)) {
		$backSvgPath = storage_path('app/public/' . $templateRef->back_svg_path);
		if (file_exists($backSvgPath)) {
			$backSvg = file_get_contents($backSvgPath);
		}
	}

	if (!$frontImage && $uploads->isNotEmpty()) {
		$first = $uploads->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($first) {
			$frontImage = $resolveImage('uploads/products/' . ($product->id ?? 'generic') . '/' . $first->filename) ?? asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . $first->filename);
		}
	}

	if (!$backImage && $uploads->count() > 1) {
		$second = $uploads->skip(1)->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($second) {
			$backImage = $resolveImage('uploads/products/' . ($product->id ?? 'generic') . '/' . $second->filename) ?? asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . $second->filename);
		}
	}

	$fallbackImage = asset('images/placeholder.png');
	$frontImage = $frontImage ?? $fallbackImage;
	$backImage = $backImage ?? $frontImage;

	$placeholderSource = $placeholderItems ?? $unfilledPlaceholders ?? data_get($summaryData, 'placeholders', []);
	$placeholderItems = collect($placeholderSource)
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
	$lastEditedAt = $lastEditedAt ?? null;
	$editHref = $editHref ?? route('design.edit');
	$customerReview = $customerReview ?? null;
	if (!$lastEditedAt && $customerReview) {
		$lastEditedAt = optional($customerReview->updated_at ?? $customerReview->created_at)->toIso8601String();
	}
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
					</div>
				</div>

				<div class="preview-display">
					<div class="preview-header">
						<h2>Final artwork preview</h2>
						@if($lastEditedAt)
							<div class="preview-timestamp">
								<span class="timestamp-label">Last updated:</span>
								<time datetime="{{ $lastEditedAt }}" title="{{ \Carbon\Carbon::parse($lastEditedAt)->format('l, F j, Y \a\t g:i A') }}">
									{{ \Carbon\Carbon::parse($lastEditedAt)->diffForHumans() }}
								</time>
							</div>
						@endif
						<div class="preview-toggle" role="group" aria-label="Preview sides">
							<button type="button" class="active" data-face="front" aria-pressed="true">Front</button>
							<button type="button" data-face="back" aria-pressed="false">Back</button>
						</div>
					</div>

					<div class="card-flip">
						<div class="inner">
							<div class="card-face front">
								<img src="{{ $frontImage }}" alt="Front of your design" loading="lazy" decoding="async">
							</div>
							<div class="card-face back">
								@if(!empty($backSvg))
									{{-- Embed back SVG directly --}}
									<div class="svg-container" style="width: 100%; height: 100%; pointer-events: none;">
										{!! $backSvg !!}
									</div>
								@else
									<img src="{{ $backImage }}" alt="Back of your design" loading="lazy" decoding="async">
								@endif
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

			@if($customerReview)
				<div class="customer-review">
					<h2>Your saved review</h2>
					@if(!is_null($customerReview->rating))
						<p><strong>Rating:</strong> {{ $customerReview->rating }}/5</p>
					@endif
					@if(!empty($customerReview->review_text))
						<p class="review-text">{{ $customerReview->review_text }}</p>
					@else
						<p class="review-text text-muted">No review text saved.</p>
					@endif
					<p class="review-date">Last updated: {{ optional($customerReview->updated_at ?? $customerReview->created_at)->format('M d, Y h:i A') }}</p>
				</div>
			@endif

			<div class="confirm-actions">
	    <button type="button"
	    	id="continueBtn"
	    	data-href="{{ $continueHref ?? '' }}"
	    	data-save-url="{{ route('order.review.continue') }}"
	    	disabled
	    >
					Continue
				</button>

				<a href="{{ $editHref }}" class="edit-link">Edit my design</a>
			</div>
		</section>
	</div>

	<div id="reviewToast" class="review-toast" role="status" aria-live="polite"></div>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const summaryData = {!! \Illuminate\Support\Js::from($orderSummary ?? []) !!};
			window.summaryData = summaryData;
			try { window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData)); } catch (e) {}

			// Fallback: replace preview images with saved template from sessionStorage if available
			try {
				const saved = JSON.parse(window.sessionStorage.getItem('inkwise-finalstep') || window.sessionStorage.getItem('inkwise-saved-template') || 'null');
				const frontImg = document.querySelector('.card-flip .card-face.front img');
				const backImg = document.querySelector('.card-flip .card-face.back img');
				const resolve = (candidate) => {
					if (!candidate || typeof candidate !== 'string') return '';
					const trimmed = candidate.trim();
					if (!trimmed) return '';
					if (/^(data:|https?:|\/\/|\/)/i.test(trimmed)) return trimmed;
					return '/storage/' + trimmed.replace(/^\/+/, '');
				};

				if (saved && frontImg) {
					const previewImages = saved.previewImages || saved.preview_images || [];
					const primary = saved.previewImage || saved.preview_image || previewImages[0] || saved.preview || (saved.template && saved.template.preview_image) || null;
					const back = previewImages[1] || saved.back_preview || null;
					const resolvedFront = resolve(primary);
					if (resolvedFront) frontImg.src = resolvedFront;
					if (backImg) {
						const resolvedBack = resolve(back);
						if (resolvedBack) backImg.src = resolvedBack;
					}
				}
			} catch (e) {
				// non-fatal
			}
		});
	</script>
</body>
</html>
