<div 
    class="product-card"
    role="listitem"
    tabindex="0"
    data-view-url="{{ route('admin.products.view', $product->id) }}"
    data-id="{{ $product->id }}"
    data-name="{{ e($product->name) }}"
    data-description="{{ e(strip_tags($product->description ?? '')) }}"
    data-event-type="{{ e($product->event_type ?? optional($product->template)->event_type ?? '-') }}"
    data-product-type="{{ e($product->product_type ?? optional($product->template)->product_type ?? '-') }}"
    data-theme-style="{{ e($product->theme_style ?? optional($product->template)->theme_style ?? '-') }}"
    data-unit-price="{{ $product->base_price ?? $product->unit_price ?? '' }}"
    data-image="{{ \App\Support\ImageResolver::url($product->image) }}"
>
    @php
        // front image fallback: envelope image -> product images -> product.image -> template front
        $frontThumb = '';
        $imgRec = $product->images ?? $product->product_images ?? null;

        // For envelopes, check envelope image first
        if ($product->product_type === 'Envelope' && $product->envelope && $product->envelope->envelope_image) {
            $frontThumb = \Illuminate\Support\Facades\Storage::url($product->envelope->envelope_image);
        } elseif ($imgRec && (!empty($imgRec->front) || !empty($imgRec->preview))) {
            $frontThumb = \App\Support\ImageResolver::url($imgRec->front ?? $imgRec->preview ?? null);
        } elseif (!empty($product->image)) {
            $frontThumb = \App\Support\ImageResolver::url($product->image);
        } elseif ($product->template ?? null) {
            $t = $product->template;
            $tFront = $t->front_image ?? $t->preview_front ?? $t->image ?? null;
            if ($tFront) {
                $frontThumb = preg_match('/^(https?:)?\/\//i', $tFront) || strpos($tFront, '/') === 0
                    ? $tFront
                    : \Illuminate\Support\Facades\Storage::url($tFront);
            }
        }

        $evType = $product->event_type ?? optional($product->template)->event_type ?? '—';
        $prodType = $product->product_type ?? optional($product->template)->product_type ?? '—';
        $theme = $product->theme_style ?? optional($product->template)->theme_style ?? '—';
        $basePrice = null;

        // Handle pricing based on product type
        if ($prodType === 'Envelope' && $product->envelope) {
            $basePrice = $product->envelope->price_per_unit;
        } else {
            $basePrice = $product->base_price ?? $product->unit_price ?? optional($product->template)->base_price ?? optional($product->template)->unit_price ?? null;
        }
    @endphp

    <div class="product-card-media">
        <img src="{{ $frontThumb }}" alt="{{ $product->name }}" class="product-card-thumb" loading="lazy">
    </div>

    <div class="product-card-body">
        <h3 class="product-card-title">{{ $product->name }}</h3>
        @if($product->description)
            <p class="product-card-desc">{{ Str::limit(strip_tags($product->description), 80) }}</p>
        @endif

        <div class="product-card-meta">
            <span class="meta-item">{{ $evType }}</span>
            <span class="meta-sep">•</span>
            <span class="meta-item">{{ $prodType }}</span>
            <span class="meta-sep">•</span>
            @if($prodType === 'Envelope' && $product->envelope)
                <span class="meta-item">{{ $product->envelope->material->material_name ?? $product->envelope->envelope_material_name ?? '—' }}</span>
            @else
                <span class="meta-item">{{ $theme }}</span>
            @endif
        </div>
    </div>

    <div class="product-card-footer">
        <div class="price">{{ $basePrice !== null ? '₱' . number_format($basePrice, 2) : '—' }}</div>
        <div class="card-actions">
            <a href="{{ route('admin.products.view', $product->id) }}" class="btn-view" title="View {{ $product->name }}" aria-label="View {{ $product->name }}"><i class="fi fi-sr-eye"></i></a>
            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-update" title="Edit {{ $product->name }}" aria-label="Edit {{ $product->name }}"><i class="fa-solid fa-pen-to-square"></i></a>
            <button
                type="button"
                class="btn-upload"
                data-id="{{ $product->id }}"
                data-upload-url="{{ route('admin.products.upload', $product->id) }}"
                title="Upload"
                aria-label="Upload assets for {{ $product->name }}"
            >
                <i class="fa-solid fa-upload"></i>
            </button>
            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;" class="ajax-delete-form" data-id="{{ $product->id }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn-delete ajax-delete" data-id="{{ $product->id }}" title="Delete {{ $product->name }}" aria-label="Delete {{ $product->name }}"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </div>

</div>
