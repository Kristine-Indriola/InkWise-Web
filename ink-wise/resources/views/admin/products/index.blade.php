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

    <div class="floating-panel" id="productFloatingPanel" style="display: none;">


    </div>

    {{-- Product slide panel removed. Slidebar/slide-panel markup and gallery logic were removed to simplify UI. --}}

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

                // close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!popup.contains(e.target) && e.target !== filterBtn) {
                        closePopup();
                    }
                });
            });

            // AJAX slide-panel removed: viewing product details via slide panel is disabled.
            // Use the product view route or implement a new modal if desired.

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

