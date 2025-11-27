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
    data-image="{{ \App\Support\ImageResolver::url([
        $product->image,
        optional($product->images)->front ?? optional($product->images)->preview,
        optional($product->template)->front_image,
        optional($product->template)->preview_front,
        optional($product->template)->preview,
    ]) }}"
>
    @php
        // front image fallback: envelope image -> product images -> product.image -> template front
        $imgRec = $product->images ?? $product->product_images ?? null;
        $frontThumb = \App\Support\ImageResolver::url([
            optional($product->envelope)->envelope_image,
            $imgRec ? ($imgRec->front ?? $imgRec->preview ?? null) : null,
            $product->image,
            optional($product->template)->front_image,
            optional($product->template)->preview_front,
            optional($product->template)->preview,
            optional($product->template)->image,
        ]);

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
        <div class="product-select">
            <input type="checkbox" class="product-checkbox" value="{{ $product->id }}" id="product-{{ $product->id }}" aria-label="Select {{ $product->name }} for bulk actions">
            <label for="product-{{ $product->id }}" class="checkbox-label"></label>
        </div>
        <img src="{{ $frontThumb }}" alt="{{ $product->name }}" class="product-card-thumb" loading="lazy">
    </div>

    <div class="product-card-body">
        <h3 class="product-card-title">
            @if($product->product_type === 'Giveaway')
                {{ $product->name }}
            @else
                {{ $product->name }}
            @endif
        </h3>
        @if($product->description)
            <p class="product-card-desc">{{ Str::limit(strip_tags($product->description), 80) }}</p>
        @endif

        <div class="product-card-meta">
            <span class="meta-item">{{ $evType }}</span>
            <span class="meta-sep">-</span>
            <span class="meta-item">{{ $prodType }}</span>
            <span class="meta-sep">-</span>
            @if(in_array($prodType, ['Envelope', 'Giveaway']))
                @php
                    $materialName = '—';
                    if ($prodType === 'Envelope' && $product->envelope && $product->envelope->material) {
                        $materialName = $product->envelope->material->material_name ?? $product->envelope->envelope_material_name ?? '—';
                    } elseif ($product->paperStocks && $product->paperStocks->first() && $product->paperStocks->first()->material) {
                        $materialName = $product->paperStocks->first()->material->name ?? '—';
                    } elseif ($product->materials && $product->materials->first() && $product->materials->first()->material) {
                        $materialName = $product->materials->first()->material->material_name ?? '—';
                    }
                @endphp
                <span class="meta-item">{{ $materialName }}</span>
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
                class="btn-upload {{ $product->uploads->count() > 0 ? 'published' : '' }}"
                data-id="{{ $product->id }}"
                data-upload-url="{{ route('admin.products.upload', $product->id) }}"
                title="{{ $product->uploads->count() > 0 ? 'Published to customer pages' : 'Upload' }}"
                aria-label="{{ $product->uploads->count() > 0 ? 'Published to customer pages' : 'Upload assets for ' . $product->name }}"
                {{ $product->uploads->count() > 0 ? 'disabled' : '' }}
            >
                @if($product->uploads->count() > 0)
                    <i class="fas fa-check"></i>
                @else
                    <i class="fa-solid fa-upload"></i>
                @endif
            </button>
            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;" class="ajax-delete-form" data-id="{{ $product->id }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn-delete ajax-delete" data-id="{{ $product->id }}" data-name="{{ $product->name }}" title="Delete {{ $product->name }}" aria-label="Delete {{ $product->name }}">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span class="sr-only">Delete {{ $product->name }}</span>
                </button>
            </form>
        </div>
    </div>

</div>
