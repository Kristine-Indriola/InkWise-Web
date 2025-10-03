{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')
<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
<script src="{{ asset('js/admin/product.js') }}"></script>
@section('content')
<main class="dashboard-container admin-page-shell" role="main">
    <h1 class="page-title">Products</h1>
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $filterQuery = request()->except(['type', 'page']);
        $filterBaseRoute = route('admin.products.index', $filterQuery);
        $invitationRoute = route('admin.products.index', array_merge($filterQuery, ['type' => 'Invitation']));
        $giveawayRoute = route('admin.products.index', array_merge($filterQuery, ['type' => 'Giveaway']));
        $activeFilter = $currentFilter ?? 'All';
    @endphp

    <!-- Summary Cards -->
    <section class="summary-cards" aria-label="Product summary">
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Invitations</span>
                <span class="summary-card-chip">Products</span>
            </div>
            <span class="summary-card-value">{{ number_format($invitationCount ?? 0) }}</span>
        </article>
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Giveaways</span>
                <span class="summary-card-chip">Products</span>
            </div>
            <span class="summary-card-value">{{ number_format($giveawayCount ?? 0) }}</span>
        </article>
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Uploads</span>
                <span class="summary-card-chip">Assets</span>
            </div>
            <span class="summary-card-value">{{ number_format($totalUploads ?? 0) }}</span>
        </article>
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Products</span>
                <span class="summary-card-chip">Catalog</span>
            </div>
            <span class="summary-card-value">{{ number_format($totalProducts ?? 0) }}</span>
        </article>
    </section>

    <!-- Search + Add Buttons -->
    <div class="search-and-add">
        <div class="filter-buttons" role="group" aria-label="Filter products by type">
            <a href="{{ $filterBaseRoute }}" class="filter-btn {{ $activeFilter === 'All' ? 'active' : '' }}" @if($activeFilter === 'All') aria-current="page" @endif>All</a>
            <a href="{{ $invitationRoute }}" class="filter-btn {{ $activeFilter === 'Invitation' ? 'active' : '' }}" @if($activeFilter === 'Invitation') aria-current="page" @endif>Invitations</a>
            <a href="{{ $giveawayRoute }}" class="filter-btn {{ $activeFilter === 'Giveaway' ? 'active' : '' }}" @if($activeFilter === 'Giveaway') aria-current="page" @endif>Giveaways</a>
        </div>
        <div class="add-buttons">
            <a href="{{ route('admin.products.create.invitation') }}" class="btn-add-new" aria-label="Upload new product"><i class="fi fi-rr-cloud-upload"></i> Create New Product</a>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="table-container" id="products-table-container">
        <h2>Products List</h2>

        <div class="products-grid" role="list">
            @forelse($products as $product)
                @include('admin.products.partials.card', ['product' => $product])
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

    @if(isset($recentGiveaways) && $recentGiveaways->count())
        <section class="giveaways-container" aria-labelledby="giveaways-heading">
            <div class="giveaways-header">
                <h2 id="giveaways-heading">Giveaways</h2>
                <p class="giveaways-subtitle">Latest giveaway concepts ready for review.</p>
            </div>
            <div class="products-grid giveaways-grid" role="list">
                @foreach($recentGiveaways as $product)
                    @include('admin.products.partials.card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

        {{-- CSS + JS assets pushed to stacks so layout can place them appropriately --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">

{{-- Upload modal styles --}}
<style>
    .upload-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }
    .upload-modal.show {
        display: flex;
    }
    .upload-modal-content {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .upload-modal h3 {
        margin: 0 0 15px 0;
        font-size: 1.2rem;
        color: #333;
    }
    .upload-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .upload-form input[type="file"] {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .upload-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .btn-upload-submit {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }
    .btn-upload-cancel {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }
</style>
    @endpush

    @push('scripts')
        <script src="{{ asset('js/admin/product.js') }}" defer></script>

        <script>
            // Make entire product card clickable and keyboard accessible.
            document.addEventListener('DOMContentLoaded', function () {
                function isInteractiveElement(el) {
                    if (!el) return false;
                    return el.closest('a, button, form, input, select, textarea, .ajax-delete-form, .card-actions');
                }

                document.querySelectorAll('.product-card').forEach(function (card) {
                    // click to open view unless clicking an action
                    card.addEventListener('click', function (e) {
                        if (isInteractiveElement(e.target)) return; // let the button/link handle it
                        var url = card.getAttribute('data-view-url');
                        if (!url) return;

                        // If user used Ctrl/Cmd or middle-click, open in new tab
                        var openInNewTab = e.ctrlKey || e.metaKey || (e.button === 1);
                        if (openInNewTab) {
                            window.open(url, '_blank');
                            return;
                        }

                        // otherwise navigate in same tab
                        window.location = url;
                    });

                    // keyboard activation: Enter or Space
                    card.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            // prevent scrolling when Space is used
                            e.preventDefault();
                            // ignore if focus is on an interactive child
                            if (isInteractiveElement(document.activeElement)) return;
                            var url = card.getAttribute('data-view-url');
                            if (url) window.location = url;
                        }
                    });
                });
            });

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
            // Use the product view route or implement a new modal if desired.            // AJAX delete handler for delete buttons inside forms with class 'ajax-delete-form'
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ajax-delete')) return;

                // prevent normal form submission when JS is enabled
                e.preventDefault();

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

            // Upload button handler: open the final invitation template (customer view) for the product
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.btn-upload')) return;

                e.preventDefault();

                const btn = e.target.closest('.btn-upload');
                const productId = btn.getAttribute('data-id');
                if (!productId) return;

                // Open the wedding invite view in a new tab so admin can preview the final template
                // Route: /admin/products/{id}/weddinginvite
                const url = `/admin/products/${productId}/weddinginvite`;
                window.open(url, '_blank');
            });

            // Note: the upload modal remains in the markup for backward compatibility, but
            // the admin upload button now opens the final invitation preview in a new tab.

                        // AJAX slide-panel removed: viewing product details via slide panel is disabled.
            // Use the product view route or implement a new modal if desired.
        </script>
    @endpush

    {{-- Upload modal --}}
    <div id="upload-modal" class="upload-modal">
        <div class="upload-modal-content">
            <h3>Upload Template</h3>
            <form id="upload-form" class="upload-form" enctype="multipart/form-data">
                <input type="file" id="upload-file" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                <div class="upload-buttons">
                    <button type="submit" class="btn-upload-submit">Upload</button>
                    <button type="button" id="cancel-upload" class="btn-upload-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

@endsection

