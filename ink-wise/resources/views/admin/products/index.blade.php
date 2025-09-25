{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')

@section('content')
<div class="dashboard-container">
    <h1 class="page-title">Products</h1>

    <!-- Summary Cards -->
    <div class="summary-cards compact">
        <div class="card">
            <div class="card-body">
                <div class="card-title">All Session</div>
                <div class="card-subtitle">No Shop · 56.15%</div>
                <div class="card-footer">
                    <div class="card-number">245.15k</div>
                    <div class="card-percentage positive">+7.11%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="card-title">Product Views</div>
                <div class="card-subtitle">No Shop · 26.22%</div>
                <div class="card-footer">
                    <div class="card-number">154.12k</div>
                    <div class="card-percentage positive">+2.11%</div>
                </div>
            </div>
        </div>

        <div class="card">
    
            <div class="card-body">
                <div class="card-title">In the Cart</div>
                <div class="card-subtitle">Wishlist · 50.15%</div>
                <div class="card-footer">
                    <div class="card-number">101.05k</div>
                    <div class="card-percentage positive">+1.11%</div>
                </div>
            </div>
        </div>

        <div class="card">

            <div class="card-body">
                <div class="card-title">Ordered</div>
                <div class="card-subtitle">Cancelled · 15.05%</div>
                <div class="card-footer">
                    <div class="card-number">95.34k</div>
                    <div class="card-percentage negative">-0.11%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search + Add Buttons -->
    <div class="search-and-add">
        <div class="add-buttons">
            <button class="btn-add-new" aria-label="Add new product"><i class="fi fi-rr-pen-nib"></i> Add New Product</button>
            <div class="floating-buttons">
                <a href="{{ route('admin.products.create.invitation') }}" class="btn-floating btn-invitation"><i class="fa-solid fa-envelope"></i> Add Invitation</a>
                <button class="btn-floating btn-giveaway"><i class="fa-solid fa-gift"></i> Add Giveaway</button>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="table-controls">
        <div class="controls-left">
            <div class="search-bar-wrapper">
                <form id="productSearchForm" method="GET" action="{{ route('admin.products.index') }}" role="search">
                    <label class="search-input-wrapper" for="productSearch">
                        <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input class="search-input" id="productSearch" name="q" type="search" value="{{ request('q') }}" placeholder="Search product..." aria-describedby="search-help">
                        <span id="search-help" class="sr-only">Type to search products by name or category.</span>
                        <button type="button" class="filter-icon" title="Filters" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-solid fa-filter"></i>
                        </button>

                    <!-- Small floating filter popup -->
                    <div id="filterPopup" class="filter-popup" aria-hidden="true" style="display:none;position:absolute;top:110%;right:0;z-index:50;background:#fff;border:1px solid #e5e7eb;padding:8px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                        <button type="button" id="filterAll" class="btn-filter-option" style="display:inline-block;margin:4px;padding:6px 10px;border-radius:4px;border:1px solid #d1d5db;background:#f9fafb;">All</button>
                        <button type="button" id="filterInks" class="btn-filter-option" style="display:inline-block;margin:4px;padding:6px 10px;border-radius:4px;border:1px solid #d1d5db;background:#f9fafb;">Inks</button>
                        <button type="button" id="filterMaterials" class="btn-filter-option" style="display:inline-block;margin:4px;padding:6px 10px;border-radius:4px;border:1px solid #d1d5db;background:#f9fafb;">Materials</button>
                    </div>
                </label>
            </div>
        </div>
        <div class="controls-right">
            <button class="btn-download-all" title="Download CSV"><i class="fa-solid fa-file-arrow-down"></i></button>
            <div class="sort-group">
                <button class="btn-sort-up" title="Sort ascending"><i class="fa-solid fa-arrow-up"></i></button>
                <button class="btn-sort-down" title="Sort descending"><i class="fa-solid fa-arrow-down"></i></button>
            </div>
            <div class="filter-group">
                <select class="filter-select">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-container" id="products-table-container">
        <h2>Products List</h2>
        <table class="products-table" aria-describedby="products-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Event Type</th>
                    <th>Product Type</th>
                    <th>Selling Price</th>
                    <th>Quantity Ordered</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                        <td>
                            <div class="product-meta">
                                <img src="@imageUrl($product->image)" alt="{{ $product->name }}" class="product-thumb">
                                <div class="product-meta-text">
                                    <strong class="product-name">{{ $product->name }}</strong>
                                    @if($product->description)
                                        <small class="product-desc">{{ Str::limit($product->description, 60) }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->event_type ?? '-' }}</td>
                        <td>{{ $product->product_type ?? '-' }}</td>
                        <td>₱{{ number_format($product->selling_price ?? 0, 2) }}</td>
                        <td>{{ $product->quantity_ordered ?? 0 }}</td>
                        <td>₱{{ number_format(($product->selling_price ?? 0) * ($product->quantity_ordered ?? 0), 2) }}</td>
                        <td>
                            <span class="status status-{{ \Illuminate\Support\Str::slug($product->status ?? 'unknown') }}">{{ ucfirst($product->status ?? 'unknown') }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn-view btn-view-ajax" data-id="{{ $product->id }}" title="View {{ $product->name }}" aria-label="View {{ $product->name }}"><i class="fi fi-sr-eye"></i></button>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-update" title="Edit {{ $product->name }}" aria-label="Edit {{ $product->name }}"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;" class="ajax-delete-form" data-id="{{ $product->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn-delete ajax-delete" data-id="{{ $product->id }}" title="Delete {{ $product->name }}" aria-label="Delete {{ $product->name }}"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
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

    <!-- Inks Table (hidden by default) -->
    <div class="table-container" id="inks-table-container" style="display:none;">
        <h2>All Product Inks</h2>
        @php
            $inksList = collect();
            foreach($products as $product) {
                if ($product->inks && $product->inks->count()) {
                    foreach ($product->inks as $ink) {
                        $inksList->push(['product' => $product, 'ink' => $ink]);
                    }
                }
            }
        @endphp

        <table class="inks-table" aria-describedby="inks-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Ink Item</th>
                    <th>Type</th>
                    <th>Usage</th>
                    <th>Cost Per ML</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inksList as $idx => $entry)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $entry['product']->name ?? '-' }}</td>
                        <td>{{ $entry['ink']->item ?? '-' }}</td>
                        <td>{{ $entry['ink']->type ?? '-' }}</td>
                        <td>{{ $entry['ink']->usage ?? '-' }}</td>
                        <td>₱{{ number_format($entry['ink']->cost_per_ml ?? 0, 2) }}</td>
                        <td>₱{{ number_format($entry['ink']->total_cost ?? 0, 2) }}</td>
                        <td>
                            <a href="#" class="btn-view" title="View Ink"><i class="fi fi-sr-eye"></i></a>
                            <button type="button" class="btn-delete ajax-delete" data-id="{{ $entry['ink']->id ?? '' }}" aria-label="Delete Ink"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No inks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Materials Table (hidden by default) -->
    <div class="table-container" id="materials-table-container" style="display:none;">
        <h2>All Product Materials</h2>
        @php
            $materialsList = collect();
            foreach($products as $product) {
                if ($product->materials && $product->materials->count()) {
                    foreach ($product->materials as $mat) {
                        $materialsList->push(['product' => $product, 'material' => $mat]);
                    }
                }
            }
        @endphp

        <table class="materials-table" aria-describedby="materials-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Material Item</th>
                    <th>Type</th>
                    <th>Color</th>
                    <th>Weight</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materialsList as $idx => $entry)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $entry['product']->name ?? '-' }}</td>
                        <td>{{ $entry['material']->item ?? '-' }}</td>
                        <td>{{ $entry['material']->type ?? '-' }}</td>
                        <td>{{ $entry['material']->color ?? '-' }}</td>
                        <td>{{ $entry['material']->weight ?? '-' }}</td>
                        <td>₱{{ number_format($entry['material']->unit_price ?? 0, 2) }}</td>
                        <td>{{ $entry['material']->qty ?? 0 }}</td>
                        <td>₱{{ number_format($entry['material']->cost ?? 0, 2) }}</td>
                        <td>
                            <a href="#" class="btn-view" title="View Material"><i class="fi fi-sr-eye"></i></a>
                            <button type="button" class="btn-delete ajax-delete" data-id="{{ $entry['material']->id ?? '' }}" aria-label="Delete Material"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No materials found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>


<!-- CSS + JS -->
<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
<script src="{{ asset('js/admin-js/product.js') }}" defer></script>
<!-- Slidebar assets are lazy-loaded by the admin layout when needed -->
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

        // close when clicking outside
        document.addEventListener('click', function (e) {
            if (!popup.contains(e.target) && e.target !== filterBtn) {
                closePopup();
            }
        });
    });

    // Toggle tables: All / Inks / Materials
    document.addEventListener('DOMContentLoaded', function() {
        var btnAll = document.getElementById('filterAll');
        var btnInks = document.getElementById('filterInks');
        var btnMaterials = document.getElementById('filterMaterials');

    var productsContainer = document.getElementById('products-table-container');
        var inksContainer = document.getElementById('inks-table-container');
        var materialsContainer = document.getElementById('materials-table-container');

        function showProducts() {
            if (productsContainer) productsContainer.style.display = '';
            if (inksContainer) inksContainer.style.display = 'none';
            if (materialsContainer) materialsContainer.style.display = 'none';
        }

        function showInks() {
            if (productsContainer) productsContainer.style.display = 'none';
            if (inksContainer) inksContainer.style.display = '';
            if (materialsContainer) materialsContainer.style.display = 'none';
        }

        function showMaterials() {
            if (productsContainer) productsContainer.style.display = 'none';
            if (inksContainer) inksContainer.style.display = 'none';
            if (materialsContainer) materialsContainer.style.display = '';
        }

        var popup = document.getElementById('filterPopup');
        var filterBtn = document.querySelector('.filter-icon');

        function closePopupSelection() {
            if (popup) {
                popup.style.display = 'none';
                popup.setAttribute('aria-hidden', 'true');
            }
            if (filterBtn) {
                filterBtn.setAttribute('aria-expanded', 'false');
            }
        }

        if (btnAll) btnAll.addEventListener('click', function(e) { e.stopPropagation(); showProducts(); closePopupSelection(); });
        if (btnInks) btnInks.addEventListener('click', function(e) { e.stopPropagation(); showInks(); closePopupSelection(); });
        if (btnMaterials) btnMaterials.addEventListener('click', function(e) { e.stopPropagation(); showMaterials(); closePopupSelection(); });
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
            return fetch('{{ route('admin.products.show', ['id' => 'REPLACE_ID']) }}'.replace('REPLACE_ID', id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html, application/xhtml+xml' }
            })
            .then(function(resp){ return resp.text(); })
            .then(function(html){
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                // append panel to body
                document.body.appendChild(wrapper);
                // Attach the panel handlers (defined in product-slide.js)
                if (window.attachProductPanelHandlers) {
                    try { window.attachProductPanelHandlers(document.getElementById('product-slide-panel')); } catch(e){ console.error(e); }
                }
            });
        }

        // Ensure assets loaded via layout helper if available
        if (typeof window.__loadProductSlideAssets === 'function') {
            window.__loadProductSlideAssets().then(function(){
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
</script>
    <script>
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

                // remove the row from the table
                var row = form.closest('tr');
                if (row) row.parentNode.removeChild(row);

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
@endsection
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

