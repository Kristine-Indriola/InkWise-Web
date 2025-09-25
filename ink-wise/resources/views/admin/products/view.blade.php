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
@if($isAjax)
    <div id="product-slide-panel" class="product-slide-panel" role="dialog" aria-label="Product details for {{ $product->name ?? 'Product' }}" aria-modal="true">
        <div class="panel-backdrop" id="panel-backdrop" tabindex="-1"></div>
        <aside class="panel" role="document">
            <header class="panel-header">
                <div class="panel-title-wrap">
                    <h2 class="panel-title">{{ $product->name ?? 'Product' }}</h2>
                    <div class="panel-meta">{{ $product->event_type ?? '-' }} • {{ $product->product_type ?? '-' }}</div>
                </div>
                <button id="close-panel" class="btn-close" aria-label="Close product details">✕</button>
            </header>
            <div class="panel-body">
                <div class="panel-grid">
                    <div class="panel-image">
                        <img id="panel-main-image" src="{{ $mainImage }}" alt="{{ $product->name ?? 'Product image' }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/no-image.png') }}';">

                        <div class="thumbnails">
                            @foreach($gallery as $i => $thumb)
                                <button class="thumb {{ $i === 0 ? 'selected' : '' }}" type="button" data-src="{{ $thumb }}" aria-label="View image {{ $i + 1 }}">
                                    <img src="{{ $thumb }}" alt="{{ $product->name ?? 'Thumbnail' }} {{ $i + 1 }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/no-image.png') }}';">
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="panel-info">
                        <div class="meta-row">
                            <div class="meta-left">
                                <div class="product-sku">SKU: <strong>{{ $product->sku ?? '-' }}</strong></div>
                                <div class="product-category">{{ $product->event_type ?? '-' }} · {{ $product->product_type ?? '-' }}</div>
                            </div>
                            <div class="meta-right">
                                <div class="status-badge {{ $product && $product->status ? 'status-' . \Illuminate\Support\Str::slug($product->status) : '' }}">{{ ucfirst($product->status ?? 'unknown') }}</div>
                            </div>
                        </div>

                        <div class="price-row">
                            <div class="price-item large"><strong>₱{{ number_format($product->selling_price ?? 0, 2) }}</strong><div class="price-sub">Selling</div></div>
                            <div class="price-item"><strong>{{ $product->quantity_ordered ?? 0 }}</strong><div class="price-sub">Qty</div></div>
                            <div class="price-item"><strong>{{ ($product->materials ? $product->materials->count() : 0) + ($product->inks ? $product->inks->count() : 0) }}</strong><div class="price-sub">Components</div></div>
                        </div>

                        <div class="description">{!! $product->description ?? '<em>No description</em>' !!}</div>

                        <div class="lists">
                            <section aria-labelledby="materials-heading">
                                <h3 id="materials-heading">Materials</h3>
                                @if($product && $product->materials && $product->materials->count())
                                    <table class="panel-table">
                                        <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Cost</th></tr></thead>
                                        <tbody>
                                        @foreach($product->materials as $mat)
                                            <tr>
                                                <td>{{ $mat->item }}</td>
                                                <td>{{ $mat->qty ?? 0 }}</td>
                                                <td>₱{{ number_format($mat->unit_price ?? 0,2) }}</td>
                                                <td>₱{{ number_format($mat->cost ?? 0,2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="muted">No materials listed.</p>
                                @endif
                            </section>

                            <section aria-labelledby="inks-heading">
                                <h3 id="inks-heading">Inks</h3>
                                @if($product && $product->inks && $product->inks->count())
                                    <table class="panel-table">
                                        <thead><tr><th>Item</th><th>Usage</th><th>Cost/ml</th><th>Total</th></tr></thead>
                                        <tbody>
                                        @foreach($product->inks as $ink)
                                            <tr>
                                                <td>{{ $ink->item }}</td>
                                                <td>{{ $ink->usage ?? 0 }} ml</td>
                                                <td>₱{{ number_format($ink->cost_per_ml ?? 0,2) }}</td>
                                                <td>₱{{ number_format($ink->total_cost ?? 0,2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="muted">No inks listed.</p>
                                @endif
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                @if($product)
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary">Edit</a>
                @endif
                <button type="button" class="btn btn-secondary" id="panel-close-secondary">Close</button>
            </footer>
        </aside>
    </div>
@endif
