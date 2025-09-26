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

<<<<<<< HEAD
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
=======
{{-- This file is a pure modal partial intended for AJAX injection. Keep it self-contained. --}}
@if($isAjax)
    {{-- Inline CSS so the injected partial is self-contained --}}
    <style>
        .product-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 11000; }
        .product-modal .modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.45); backdrop-filter: blur(1px); }
        .product-modal .modal-content { position: relative; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(2,6,23,0.2); width: 92%; max-width: 1100px; max-height: 90vh; overflow: auto; z-index: 11010; display: flex; flex-direction: column; padding: 16px; }
        .product-modal .modal-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:8px; }
        .product-modal .modal-body { display:flex; gap:18px; align-items:flex-start; padding-top:6px; }
        .product-modal .modal-image { width:44%; min-width:240px; }
        .product-modal .modal-image img#panel-main-image { width:100%; height:auto; border-radius:6px; display:block; object-fit:contain; background:#f8fafc; }
        .product-modal .thumbnails { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
        .product-modal .thumbnails .thumb { border: 1px solid transparent; background:transparent; padding:2px; border-radius:6px; cursor:pointer; }
        .product-modal .thumbnails .thumb.selected { border-color:#3b82f6; box-shadow:0 2px 6px rgba(59,130,246,0.12); }
        .product-modal .thumbnails img { width:56px; height:56px; object-fit:cover; border-radius:4px; display:block; }
        .product-modal .modal-info { flex:1; min-width:260px; }
        .product-modal .modal-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
        .product-modal .btn-close { background:transparent; border:none; font-size:1.1rem; cursor:pointer; padding:6px; }
        @media (max-width:800px) {
            .product-modal .modal-body { flex-direction:column; }
            .product-modal .modal-image { width:100%; }
        }
    </style>

    <div id="product-slide-panel" class="product-modal" role="dialog" aria-label="Product details for {{ $product->name ?? 'Product' }}" aria-modal="true">
        <div class="modal-backdrop" id="panel-backdrop" tabindex="-1" aria-hidden="true"></div>
        <div class="modal-content" role="document">
            <div class="modal-header">
                <div>
                    <h2 class="panel-title" style="margin:0; font-size:1.1rem;">{{ $product->name ?? 'Product' }}</h2>
                    <div class="panel-meta" style="color:#6b7280; font-size:0.9rem;">{{ $product->event_type ?? '-' }} • {{ $product->product_type ?? '-' }}</div>
                </div>
                <div>
                    <button id="close-panel" class="btn-close" aria-label="Close product details">✕</button>
                </div>
            </div>

            <div class="modal-body">
                <div class="modal-image">
                    <img id="panel-main-image" src="{{ $mainImage }}" alt="{{ $product->name ?? 'Product image' }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/no-image.png') }}';">
                    <div class="thumbnails">
                        @foreach($gallery as $i => $thumb)
                            <button class="thumb {{ $i === 0 ? 'selected' : '' }}" type="button" data-src="{{ $thumb }}" aria-label="View image {{ $i + 1 }}">
                                <img src="{{ $thumb }}" alt="{{ $product->name ?? 'Thumbnail' }} {{ $i + 1 }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/no-image.png') }}';">
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="modal-info">
                    <div class="meta-row" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div class="meta-left">
                            <div class="product-sku" style="color:#6b7280;">SKU: <strong>{{ $product->sku ?? '-' }}</strong></div>
                            <div class="product-category" style="color:#6b7280; margin-top:6px;">{{ $product->event_type ?? '-' }} · {{ $product->product_type ?? '-' }}</div>
                        </div>
                        <div class="meta-right">
                            <div class="status-badge {{ $product && $product->status ? 'status-' . \Illuminate\Support\Str::slug($product->status) : '' }}">{{ ucfirst($product->status ?? 'unknown') }}</div>
                        </div>
                    </div>

                    <div class="price-row" style="display:flex;gap:12px;margin-top:12px;align-items:center;">
                        <div class="price-item large"><strong>₱{{ number_format($product->selling_price ?? 0, 2) }}</strong><div class="price-sub">Selling</div></div>
                        <div class="price-item"><strong>{{ $product->quantity_ordered ?? 0 }}</strong><div class="price-sub">Qty</div></div>
                        <div class="price-item"><strong>{{ ($product->materials ? $product->materials->count() : 0) + ($product->inks ? $product->inks->count() : 0) }}</strong><div class="price-sub">Components</div></div>
                    </div>

                    <div class="description" style="margin-top:12px; color:#111827;">{!! $product->description ?? '<em>No description</em>' !!}</div>

                    <div class="lists" style="margin-top:14px;">
                        <section aria-labelledby="materials-heading">
                            <h3 id="materials-heading">Materials</h3>
                            @if($product && $product->materials && $product->materials->count())
                                <table class="panel-table" style="width:100%;border-collapse:collapse;margin-top:8px;">
                                    <thead><tr><th style="text-align:left;padding:6px;border-bottom:1px solid #eef2f6">Item</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Qty</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Unit</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Cost</th></tr></thead>
                                    <tbody>
                                    @foreach($product->materials as $mat)
                                        <tr>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9">{{ $mat->item }}</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">{{ $mat->qty ?? 0 }}</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">₱{{ number_format($mat->unit_price ?? 0,2) }}</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">₱{{ number_format($mat->cost ?? 0,2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="muted">No materials listed.</p>
                            @endif
                        </section>

                        <section aria-labelledby="inks-heading" style="margin-top:12px;">
                            <h3 id="inks-heading">Inks</h3>
                            @if($product && $product->inks && $product->inks->count())
                                <table class="panel-table" style="width:100%;border-collapse:collapse;margin-top:8px;">
                                    <thead><tr><th style="text-align:left;padding:6px;border-bottom:1px solid #eef2f6">Item</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Usage</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Cost/ml</th><th style="text-align:right;padding:6px;border-bottom:1px solid #eef2f6">Total</th></tr></thead>
                                    <tbody>
                                    @foreach($product->inks as $ink)
                                        <tr>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9">{{ $ink->item }}</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">{{ $ink->usage ?? 0 }} ml</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">₱{{ number_format($ink->cost_per_ml ?? 0,2) }}</td>
                                            <td style="padding:6px;border-bottom:1px solid #f6f7f9;text-align:right">₱{{ number_format($ink->total_cost ?? 0,2) }}</td>
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

            <div class="modal-footer">
                @if($product)
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary">Edit</a>
                @endif
                <button type="button" class="btn btn-secondary" id="panel-close-secondary">Close</button>
            </div>
>>>>>>> origin/dashboard17
        </div>
    </div>

    {{-- Inline scripts so the partial works immediately after injection --}}
    <script>
        (function () {
            // Provide a reusable handler attach function so index can call it if desired.
            function initPanel(root) {
                if (!root) return;
                var backdrop = root.querySelector('#panel-backdrop');
                var closeBtns = root.querySelectorAll('#close-panel, #panel-close-secondary');
                var thumbs = root.querySelectorAll('.thumbnails .thumb');
                var mainImg = root.querySelector('#panel-main-image');

                // Close/remove panel
                function removePanel() {
                    try {
                        root.remove();
                    } catch(e) {
                        if (root.parentNode) root.parentNode.removeChild(root);
                    }
                    cleanup();
                }

                closeBtns.forEach(function(b){ b.addEventListener('click', removePanel); });

                if (backdrop) backdrop.addEventListener('click', removePanel);

                // ESC to close
                function onKey(e) {
                    if (e.key === 'Escape') removePanel();
                }
                document.addEventListener('keydown', onKey);

                // Thumbnail switch
                thumbs.forEach(function(t){
                    t.addEventListener('click', function(){
                        var src = t.getAttribute('data-src');
                        if (!src) return;
                        // update selected
                        thumbs.forEach(function(x){ x.classList.remove('selected'); });
                        t.classList.add('selected');
                        if (mainImg) mainImg.src = src;
                    });
                });

                // Simple focus trap: keep focus within modal while open
                var focusableSelector = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
                var focusables = Array.prototype.slice.call(root.querySelectorAll(focusableSelector));
                if (focusables.length) focusables[0].focus();

                function trapFocus(e) {
                    if (!root.contains(document.activeElement)) return;
                    if (e.key !== 'Tab') return;
                    var first = focusables[0];
                    var last = focusables[focusables.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
                document.addEventListener('keydown', trapFocus);

                function cleanup() {
                    document.removeEventListener('keydown', onKey);
                    document.removeEventListener('keydown', trapFocus);
                    closeBtns.forEach(function(b){ try{ b.removeEventListener('click', removePanel); }catch(e){} });
                    if (backdrop) try{ backdrop.removeEventListener('click', removePanel); }catch(e){}
                    thumbs.forEach(function(t){ try{ t.removeEventListener('click', function(){}); }catch(e){} });
                }

                // expose cleanup for external callers if needed
                root._cleanupPanel = cleanup;
            }

            // expose attach function globally if not already present
            if (!window.attachProductPanelHandlers) {
                window.attachProductPanelHandlers = function(rootEl) {
                    initPanel(rootEl || document.getElementById('product-slide-panel'));
                };
            }

            // If panel already in DOM (common on injection), initialize it immediately
            var panel = document.getElementById('product-slide-panel');
            if (panel) {
                try { window.attachProductPanelHandlers(panel); } catch(e){ /* fail silently */ }
            }
        })();
    </script>
@endif
