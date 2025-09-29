<div 
    class="product-card"
    role="listitem"
    tabindex="0"
    data-view-url="{{ route('admin.products.view', $product->id) }}"
    data-id="{{ $product->id }}"
    data-name="{{ e($product->name) }}"
    data-description="{{ e(strip_tags($product->description ?? '')) }}"
    data-event-type="{{ e($product->event_type ?? '-') }}"
    data-product-type="{{ e($product->product_type ?? '-') }}"
    data-theme-style="{{ e($product->theme_style ?? '-') }}"
    data-item="{{ e($product->item ?? '-') }}"
    data-material-type="{{ e($product->type ?? '-') }}"
    data-color="{{ e($product->color ?? '-') }}"
    data-size="{{ e($product->size ?? '-') }}"
    data-weight="{{ e($product->weight ? $product->weight . ' GSM' : '-') }}"
    data-unit-price="{{ $product->unit_price ?? '' }}"
    data-min-order="{{ $product->min_order_qty ?? '' }}"
    data-lead-time="{{ e($product->lead_time ?? '-') }}"
    data-stock="{{ e($product->stock_availability ?? '-') }}"
    data-image="{{ \App\Support\ImageResolver::url($product->image) }}"
>
    <div class="product-card-media">
        <img src="{{ \App\Support\ImageResolver::url($product->image) }}" alt="{{ $product->name }}" class="product-card-thumb" loading="lazy">
    </div>
    <div class="product-card-body">
        <h3 class="product-card-title">{{ $product->name }}</h3>
        @if($product->description)
            <p class="product-card-desc">{{ Str::limit(strip_tags($product->description), 80) }}</p>
        @endif
        <div class="product-card-meta">
            <span class="meta-item">{{ $product->event_type ?? '-' }}</span>
            <span class="meta-sep">•</span>
            <span class="meta-item">{{ $product->product_type ?? '-' }}</span>
            <span class="meta-sep">•</span>
            <span class="meta-item">{{ $product->theme_style ?? '-' }}</span>
        </div>
        @if($product->item || $product->type || $product->color)
            <div class="product-card-material">
                <small><strong>Material:</strong> {{ $product->item ?? '-' }}
                @if($product->type) ({{ $product->type }}) @endif
                @if($product->color) - {{ $product->color }} @endif
                @if($product->size) - {{ $product->size }} @endif
                @if($product->weight) - {{ $product->weight }} GSM @endif</small>
            </div>
        @endif
        @if($product->lead_time || $product->stock_availability)
            <div class="product-card-production">
                <small>
                    @if($product->lead_time)<strong>Lead Time:</strong> {{ $product->lead_time }} @endif
                    @if($product->stock_availability) • <strong>Stock:</strong> {{ $product->stock_availability }} @endif
                    @if($product->min_order_qty) • <strong>Min Order:</strong> {{ $product->min_order_qty }} @endif
                </small>
            </div>
        @endif
    </div>
    <div class="product-card-footer">
        <div class="price">₱{{ number_format($product->unit_price ?? 0, 2) }}</div>
        <div class="qty">Min Order: {{ $product->min_order_qty ?? 'N/A' }}</div>
        <div class="status-wrap"><span class="status status-active">Active</span></div>
        <div class="card-actions">
            <a href="{{ route('admin.products.view', $product->id) }}" class="btn-view" title="View {{ $product->name }}" aria-label="View {{ $product->name }}"><i class="fi fi-sr-eye"></i></a>
            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-update" title="Edit {{ $product->name }}" aria-label="Edit {{ $product->name }}"><i class="fa-solid fa-pen-to-square"></i></a>
            <button type="button" class="btn-upload" data-id="{{ $product->id }}" title="Upload" aria-label="Upload assets for {{ $product->name }}"><i class="fa-solid fa-upload"></i></button>
            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;" class="ajax-delete-form" data-id="{{ $product->id }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn-delete ajax-delete" data-id="{{ $product->id }}" title="Delete {{ $product->name }}" aria-label="Delete {{ $product->name }}"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </div>
</div>
