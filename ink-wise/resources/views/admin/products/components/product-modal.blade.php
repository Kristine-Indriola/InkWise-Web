<link rel="stylesheet" href="{{ asset('css/admin-css/product-modal.css') }}">

@props(['product'])

<div class="product-data" style="display: none;">
    <div class="floating-content">
        <div class="floating-header">
            <h4>{{ $product->name }}</h4>
            <button class="floating-close" aria-label="Close">&times;</button>
        </div>
        <div class="floating-body">
            <div class="floating-section">
                <h5>Basic Information</h5>
                <dl>
                    <div><dt>Name</dt><dd>{{ e($product->name) }}</dd></div>
                    <div><dt>Event Type</dt><dd>{{ e($product->event_type ?? '-') }}</dd></div>
                    <div><dt>Product Type</dt><dd>{{ e($product->product_type ?? '-') }}</dd></div>
                    <div><dt>Theme / Style</dt><dd>{{ e($product->theme_style ?? '-') }}</dd></div>
                </dl>
                <div class="floating-description">
                    <h6>Description</h6>
                    <p>{{ e(strip_tags($product->description ?? 'No description provided.')) }}</p>
                </div>
            </div>
            <div class="floating-section">
                <h5>Materials</h5>
                <dl>
                    <div><dt>Item</dt><dd>{{ e($product->item ?? '-') }}</dd></div>
                    <div><dt>Type</dt><dd>{{ e($product->type ?? '-') }}</dd></div>
                    <div><dt>Color</dt><dd>{{ e($product->color ?? '-') }}</dd></div>
                    <div><dt>Size</dt><dd>{{ e($product->size ?? '-') }}</dd></div>
                    <div><dt>Weight (GSM)</dt><dd>{{ e($product->weight ? $product->weight . ' GSM' : '-') }}</dd></div>
                </dl>
            </div>
            <div class="floating-section">
                <h5>Production & Pricing</h5>
                <dl>
                    <div><dt>Minimum Order</dt><dd>{{ e($product->min_order_qty ?? '-') }}</dd></div>
                    <div><dt>Lead Time</dt><dd>{{ e($product->lead_time ?? '-') }}</dd></div>
                    <div><dt>Stock</dt><dd>{{ e($product->stock_availability ?? '-') }}</dd></div>
                    <div><dt>Unit Price</dt><dd>₱{{ number_format($product->unit_price ?? 0, 2) }}</dd></div>
                </dl>
            </div>
            <div class="floating-media">
                <img src="{{ e(\App\Support\ImageResolver::url($product->image)) }}" alt="{{ e($product->name) }}" />
            </div>
        </div>
    </div>
</div>