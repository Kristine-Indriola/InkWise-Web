@php
    // Allow callers to override detection by passing $isAjax; fallback to request detection
    $isAjax = isset($isAjax) ? (bool) $isAjax : (request()->ajax() || request()->wantsJson() || (request()->header('X-Requested-With') === 'XMLHttpRequest'));
    $product = $product ?? null;

    // Robust image/gallery resolution: prefer files on the public storage disk, allow a gallery JSON or single image path
    $placeholder = asset('images/no-image.png');
    $gallery = [];

    // If a gallery JSON field exists, try decode it (array of paths)
    if (!empty($product->gallery)) {
        $decoded = @json_decode($product->gallery, true);
        if (is_array($decoded) && count($decoded)) {
            foreach ($decoded as $p) {
                if (!$p) continue;
                $gallery[] = \App\Support\ImageResolver::url($p);
            }
        }
    }

    // Fallback to images array (Eloquent cast) or images relationship
    if (empty($gallery) && isset($product->images) && is_iterable($product->images)) {
        foreach ($product->images as $p) {
            if (!$p) continue;
            $gallery[] = \App\Support\ImageResolver::url($p);
        }
    }

    // Final fallback to single image field
    if (empty($gallery) && !empty($product->image)) {
        $gallery[] = \App\Support\ImageResolver::url($product->image);
    }

    // Ensure at least one image
    if (empty($gallery)) {
        $gallery[] = $placeholder;
    }

    $mainImage = $gallery[0] ?? $placeholder;
@endphp

{{-- This file is a pure panel partial intended for AJAX injection. Do not extend layouts or include page-level assets here. --}}
{{-- Product slide panel removed from view partial. This file can still be used as an
    AJAX fragment for direct injection where needed. If you need to render a
    standalone modal later, restore the panel markup from git history. --}}

@if($isAjax)
    <div class="product-view-fragment">
        {{-- Simple content fragment (no slide panel wrapper) --}}
        <div class="product-view-content">
            <h2>{{ $product->name ?? 'Product' }}</h2>
            <p class="muted">SKU: <strong>{{ $product->sku ?? '-' }}</strong></p>
            <div class="description">{!! $product->description ?? '<em>No description</em>' !!}</div>
        </div>
    </div>
@endif
