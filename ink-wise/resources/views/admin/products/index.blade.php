{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')

@section('content')
<div class="dashboard-container">
    <h1 class="page-title">Products</h1>
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Search + Add Buttons -->
    <div class="search-and-add">
        <div class="add-buttons">
            <button class="btn-add-new" aria-label="Add new product"><i class="fi fi-rr-pen-nib"></i> Create New Product</button>
            <div class="floating-buttons">
                <a href="{{ route('admin.products.create.invitation') }}" class="btn-floating btn-invitation"><i class="fa-solid fa-envelope"></i> Add Invitation</a>
                <button class="btn-floating btn-giveaway"><i class="fa-solid fa-gift"></i> Add Giveaway</button>
            </div>
        </div>
    </div>

<<<<<<< HEAD
=======

>>>>>>> origin/dashboard17
    <!-- Products Grid -->
    <div class="table-container" id="products-table-container">
        <h2>Products List</h2>

        <div class="products-grid" role="list">
            @forelse($products as $product)
                <div class="product-card" role="listitem" data-id="{{ $product->id }}">
                    <div class="product-card-media">
                        <img src="{{ \App\Support\ImageResolver::url($product->image) }}" alt="{{ $product->name }}" class="product-card-thumb">
                    </div>
                    <div class="product-card-body">
                        <h3 class="product-card-title">{{ $product->name }}</h3>
                        @if($product->description)
                            <p class="product-card-desc">{{ Str::limit($product->description, 80) }}</p>
                        @endif
                        <div class="product-card-meta">
                            <span class="meta-item">{{ $product->event_type ?? '-' }}</span>
                            <span class="meta-sep">•</span>
                            <span class="meta-item">{{ $product->product_type ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="product-card-footer">
                        <div class="price">₱{{ number_format($product->selling_price ?? 0, 2) }}</div>
                        <div class="qty">Qty: {{ $product->quantity_ordered ?? 0 }}</div>
                        <div class="status-wrap"><span class="status status-{{ \Illuminate\Support\Str::slug($product->status ?? 'unknown') }}">{{ ucfirst($product->status ?? 'unknown') }}</span></div>
                        <div class="card-actions">
                            <button type="button" class="btn-view btn-view-ajax" data-id="{{ $product->id }}" data-url="{{ route('admin.products.show', $product->id) }}" title="View {{ $product->name }}" aria-label="View {{ $product->name }}"><i class="fi fi-sr-eye"></i></button>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-update" title="Edit {{ $product->name }}" aria-label="Edit {{ $product->name }}"><i class="fa-solid fa-pen-to-square"></i></a>
                            <button type="button" class="btn-upload" data-id="{{ $product->id }}" title="Upload" aria-label="Upload assets for {{ $product->name }}"><i class="fa-solid fa-upload"></i></button>
                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;" class="ajax-delete-form" data-id="{{ $product->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn-delete ajax-delete" data-id="{{ $product->id }}" title="Delete {{ $product->name }}" aria-label="Delete {{ $product->name }}"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="no-products">No products found.</div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="table-pagination" id="products-list">
            <div class="entries-info">
                @if($products->total())
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} entries
                @else
                    No entries
                @endif
            </div>
            <div class="pagination-links">
                {{ $products->links() }}
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <div class="floating-panel" id="productFloatingPanel" style="display: none;">
=======
    {{-- Inks and Materials tables removed — only product grid is shown per request --}}
>>>>>>> origin/dashboard17


    </div>

<<<<<<< HEAD
    {{-- Product slide panel removed. Slidebar/slide-panel markup and gallery logic were removed to simplify UI. --}}
=======
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

    {{-- This file is a pure modal partial intended for AJAX injection. Keep it self-contained. --}}
    @if($isAjax)
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
            </div>
        </div>
    @endif
>>>>>>> origin/dashboard17

    {{-- CSS + JS assets pushed to stacks so layout can place them appropriately --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('js/admin/product.js') }}" defer></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var filterBtn = document.querySelector('.filter-icon');
                var popup = document.getElementById('filterPopup');

                if (!filterBtn || !popup) return;

                function openPopup() {
                    popup.style.display = 'block';
                    filterBtn.setAttribute('aria-expanded', 'true');
                    popup.setAttribute('aria-hidden', 'false');
                }

                function closePopup() {
                    popup.style.display = 'none';
                    filterBtn.setAttribute('aria-expanded', 'false');
                    popup.setAttribute('aria-hidden', 'true');
                }

                filterBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (popup.style.display === 'block') {
                        closePopup();
                    } else {
                        openPopup();
                    }
                });
<<<<<<< HEAD

                // close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!popup.contains(e.target) && e.target !== filterBtn) {
                        closePopup();
                    }
                });
            });

            // AJAX slide-panel removed: viewing product details via slide panel is disabled.
            // Use the product view route or implement a new modal if desired.

=======

                // close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!popup.contains(e.target) && e.target !== filterBtn) {
                        closePopup();
                    }
                });
            });

            // AJAX view slide panel (ensure slide assets are loaded via layout lazy-loader)
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-view-ajax')) return;
                var btn = e.target.closest('.btn-view-ajax');
                var id = btn.getAttribute('data-id');
                if (!id) return;

                // If panel already present remove it
                var existing = document.getElementById('product-slide-panel');
                if (existing) existing.remove();

                function doFetch() {
                    // Prefer button-provided data-url (exact route for this product). Fallback to the REPLACE_ID route pattern.
                    var url = btn.getAttribute('data-url') || ('{{ route('admin.products.show', ['id' => 'REPLACE_ID']) }}'.replace('REPLACE_ID', id));
                    return fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html, application/xhtml+xml' }
                    })
                    .then(function(resp){ return resp.text(); })
                    .then(function(html){
                        // create a temporary container to parse HTML
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = html;

                        // Extract and execute any inline/external scripts from the fetched HTML
                        var scripts = Array.from(wrapper.querySelectorAll('script'));
                        scripts.forEach(function(s){
                            var newScript = document.createElement('script');
                            // copy attributes like type/module if present
                            if (s.type) newScript.type = s.type;
                            if (s.src) {
                                // external script: preserve src, load synchronously by appending to head
                                newScript.src = s.src;
                                // ensure execution order by making async = false (default false for dynamically added scripts without async attribute)
                                newScript.async = false;
                                document.head.appendChild(newScript);
                            } else {
                                // inline script: copy text
                                newScript.textContent = s.textContent || s.innerText || '';
                                document.head.appendChild(newScript);
                            }
                            // remove original script node from wrapper to prevent duplication when appending content
                            s.parentNode && s.parentNode.removeChild(s);
                        });

                        // append remaining HTML (the modal markup) to the body
                        while (wrapper.firstChild) {
                            document.body.appendChild(wrapper.firstChild);
                        }

                        // After injection and script execution, attach handlers exposed by the partial
                        if (window.attachProductPanelHandlers) {
                            try { window.attachProductPanelHandlers(document.getElementById('product-slide-panel')); } catch(e){ console.error(e); }
                        }
                    });
                }

                // Ensure assets loaded via layout helper if available
                if (typeof window.__loadProductModalAssets === 'function') {
                    window.__loadProductModalAssets().then(function(){
                        doFetch().catch(function(err){ console.error('Failed to load product view after assets:', err); });
                    }).catch(function(){
                        // fallback to fetching anyway
                        doFetch().catch(function(err){ console.error('Failed to load product view:', err); });
                    });
                } else {
                    // no loader available, just fetch
                    doFetch().catch(function(err){ console.error('Failed to load product view:', err); });
                }
            });

>>>>>>> origin/dashboard17
            // AJAX delete handler for delete buttons inside forms with class 'ajax-delete-form'
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ajax-delete')) return;

                var btn = e.target.closest('.ajax-delete');
                var form = btn.closest('form');
                if (!form) return;

                // confirmation
                var name = btn.getAttribute('data-name') || btn.getAttribute('title') || 'this item';
                if (!confirm('Are you sure you want to delete ' + name + '? This action cannot be undone.')) {
                    return;
                }

                // disable button to prevent duplicate clicks
                var originalDisabled = btn.disabled;
                var originalHtml = btn.innerHTML;
                btn.disabled = true;
                try { btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; } catch (err) {}

                var action = form.getAttribute('action');
                var formData = new FormData(form);

                fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(function (resp) {
                    if (resp.redirected) {
                        // If server redirected (non-AJAX fallback), follow it
                        window.location = resp.url;
                        return Promise.reject('redirect');
                    }

                    if (resp.status === 204) {
                        return { success: true, message: 'Deleted.' };
                    }

                    return resp.json().catch(function () { return { success: resp.ok }; });
                }).then(function (data) {
                    if (!data || data === 'redirect') return;
                    if (data.success === false) {
                        throw new Error(data.message || 'Delete failed');
                    }

                    // remove the containing element: prefer product-card (grid view), fall back to table row
                    var card = form.closest('.product-card');
                    if (card) {
                        card.parentNode.removeChild(card);
                    } else {
                        var row = form.closest('tr');
                        if (row) row.parentNode.removeChild(row);
                    }

                    // optional small success notice element
                    var notice = document.createElement('div');
                    notice.className = 'ajax-notice ajax-success';
                    notice.style.position = 'fixed';
                    notice.style.right = '20px';
                    notice.style.top = '20px';
                    notice.style.zIndex = '9999';
                    notice.style.background = '#16a34a';
                    notice.style.color = '#fff';
                    notice.style.padding = '8px 12px';
                    notice.style.borderRadius = '6px';
                    notice.textContent = data.message || 'Deleted successfully.';
                    document.body.appendChild(notice);
                    setTimeout(function () { try { notice.remove(); } catch(e){} }, 3500);
                }).catch(function (err) {
                    if (err === 'redirect') return;
                    var msg = (err && err.message) ? err.message : 'Failed to delete. Refresh and try again.';
                    alert(msg);
                }).finally(function () {
                    btn.disabled = originalDisabled;
                    try { btn.innerHTML = originalHtml; } catch (e) {}
                });
            });
        </script>
    @endpush

@endsection

