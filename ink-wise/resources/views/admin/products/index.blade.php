{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')
<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
<script src="{{ asset('js/admin/product.js') }}"></script>
@section('content')
<main class="dashboard-container admin-page-shell" role="main" style="padding-left:140px;">
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
        $envelopeRoute = route('admin.products.index', array_merge($filterQuery, ['type' => 'Envelope']));
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
                <span class="summary-card-label">Envelopes</span>
                <span class="summary-card-chip">Products</span>
            </div>
            <span class="summary-card-value">{{ number_format($envelopeCount ?? 0) }}</span>
        </article>
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Templates</span>
                <span class="summary-card-chip">Templates</span>
            </div>
            <span class="summary-card-value">{{ number_format($totalUploads ?? 0) }}</span>
        </article>
        <article class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Uploaded Templates</span>
                <span class="summary-card-chip">Published</span>
            </div>
            <span class="summary-card-value">{{ number_format($uploadedTemplatesCount ?? 0) }}</span>
        </article>
    </section>

    <!-- Search + Add Buttons -->
    <div class="search-and-add">
        <div class="filter-buttons" role="group" aria-label="Filter products by type">
            <a href="{{ $filterBaseRoute }}" class="filter-btn {{ $activeFilter === 'All' ? 'active' : '' }}" @if($activeFilter === 'All') aria-current="page" @endif>All</a>
            <a href="{{ $invitationRoute }}" class="filter-btn {{ $activeFilter === 'Invitation' ? 'active' : '' }}" @if($activeFilter === 'Invitation') aria-current="page" @endif>Invitations</a>
            <a href="{{ $giveawayRoute }}" class="filter-btn {{ $activeFilter === 'Giveaway' ? 'active' : '' }}" @if($activeFilter === 'Giveaway') aria-current="page" @endif>Giveaways</a>
            <a href="{{ $envelopeRoute }}" class="filter-btn {{ $activeFilter === 'Envelope' ? 'active' : '' }}" @if($activeFilter === 'Envelope') aria-current="page" @endif>Envelopes</a>
        </div>
        <div class="add-buttons">
            <button type="button" id="bulk-delete-btn" class="btn-bulk-delete" style="display: none; background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin-right: 10px;" disabled>
                <i class="fa-solid fa-trash"></i> Delete Selected (<span id="selected-count">0</span>)
            </button>
            <a href="{{ route('admin.products.create.invitation') }}" class="btn-add-new" aria-label="Upload new product"><i class="fi fi-rr-cloud-upload"></i> Create New Product</a>
            <a href="{{ route('admin.products.create.giveaway') }}" class="btn-add-new" aria-label="Create new giveaway"><i class="fi fi-rr-gift"></i> Create Giveaway</a>
            <a href="{{ route('admin.products.create.envelope') }}" class="btn-add-new" aria-label="Create new envelope"><i class="fi-sr-envelope"></i> Create Envelope</a>
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

    {{-- CSS + JS assets pushed to stacks so layout can place them appropriately --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">

{{-- Upload modal styles --}}
<style>
    /* Further increased right nudge for summary cards and products grid */
    .dashboard-container { padding-left: 140px !important; }
    .summary-cards { margin-left: 32px; }
    .products-grid { margin-left: 32px; }

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
    .upload-modal-content h3 {
        margin: 0 0 15px 0;
        font-size: 1.2rem;
        color: #333;
    }
    .confirm-message {
        text-align: left;
        margin: 20px 0;
        line-height: 1.6;
        color: #555;
    }
    .confirm-message strong {
        color: #007bff;
        display: block;
        margin-bottom: 10px;
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
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .btn-upload-submit:hover {
        background: #0056b3;
    }
    .btn-upload-cancel {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .btn-upload-cancel:hover {
        background: #545b62;
    }    /* Enhanced delete button styles */
    .btn-delete.deleting {
        background-color: #dc3545 !important;
        opacity: 0.7;
        pointer-events: none;
    }
    .btn-delete.deleting i {
        animation: spin 1s linear infinite;
    }

    /* Product card deleting state */
    .product-card.deleting {
        pointer-events: none;
        position: relative;
    }
    .product-card.deleting::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    .product-card.deleting::before {
        content: 'Deleting...';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(220, 53, 69, 0.9);
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        z-index: 11;
    }

    /* Screen reader only text */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Enhanced notice styles */
    .ajax-notice {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(4px);
        border-left: 4px solid;
    }
    .ajax-notice.ajax-success {
        border-left-color: #16a34a;
    }
    .ajax-notice.ajax-error {
        border-left-color: #dc2626;
    }

    /* Smooth animations */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Bulk selection styles */
    .product-select {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 2;
    }

    .product-checkbox {
        display: none;
    }

    .checkbox-label {
        display: inline-block;
        width: 20px;
        height: 20px;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .checkbox-label:hover {
        border-color: #007bff;
        background: rgba(255, 255, 255, 1);
    }

    .product-checkbox:checked + .checkbox-label {
        background: #007bff;
        border-color: #007bff;
    }

    .product-checkbox:checked + .checkbox-label::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    .btn-bulk-delete {
        background: #dc3545 !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 14px !important;
        display: none;
    }

    .btn-bulk-delete:hover:not(:disabled) {
        background: #c82333 !important;
        transform: translateY(-1px);
    }

    .btn-bulk-delete:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Published button styles */
    .btn-upload.published {
        background: #28a745 !important;
        color: white !important;
        cursor: default !important;
        opacity: 0.8;
    }
    .btn-upload.published:hover {
        background: #28a745 !important;
        transform: none !important;
    }

    .btn-unupload {
        background: #f97316;
        color: #fff;
        padding: 8px 16px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .btn-unupload:hover {
        background: #ea580c;
        transform: translateY(-1px);
    }
    .btn-upload.disabled, .btn-unupload.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
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
            // Use the product view route or implement a new modal if desired.            // Enhanced AJAX delete handler for delete buttons inside forms with class 'ajax-delete-form'
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ajax-delete')) return;

                // prevent normal form submission when JS is enabled
                e.preventDefault();

                var btn = e.target.closest('.ajax-delete');
                var form = btn.closest('form');
                if (!form) return;

                // Get product details for better confirmation
                var card = form.closest('.product-card');
                var productName = btn.getAttribute('data-name') || 'this product';
                var productType = card ? card.getAttribute('data-product-type') || 'Product' : 'Product';
                var eventType = card ? card.getAttribute('data-event-type') || '' : '';
                var themeStyle = card ? card.getAttribute('data-theme-style') || '' : '';

                // Create detailed confirmation message
                var confirmMessage = 'Are you sure you want to delete this ' + productType.toLowerCase() + '?\n\n';
                confirmMessage += '📄 Name: ' + productName + '\n';
                if (eventType && eventType !== '—') confirmMessage += '🎉 Event: ' + eventType + '\n';
                if (themeStyle && themeStyle !== '—') confirmMessage += '🎨 Theme: ' + themeStyle + '\n';
                confirmMessage += '\n⚠️  This action cannot be undone!';

                if (!confirm(confirmMessage)) {
                    return;
                }

                // Show loading state with better visual feedback
                var originalDisabled = btn.disabled;
                var originalHtml = btn.innerHTML;
                var originalClass = btn.className;

                btn.disabled = true;
                btn.className = originalClass + ' deleting';
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i><span class="sr-only">Deleting...</span>';

                // Add visual feedback to the card
                if (card) {
                    card.classList.add('deleting');
                }

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
                        return { success: true, message: productType + ' "' + productName + '" deleted successfully.' };
                    }

                    return resp.json().catch(function () {
                        if (resp.ok) {
                            return { success: true, message: productType + ' "' + productName + '" deleted successfully.' };
                        }
                        return { success: false, message: 'Delete failed.' };
                    });
                }).then(function (data) {
                    if (!data || data === 'redirect') return;
                    if (data.success === false) {
                        throw new Error(data.message || 'Delete failed');
                    }

                    // Smooth removal animation
                    if (card) {
                        card.style.transition = 'all 0.4s ease';
                        card.style.transform = 'scale(0.95)';
                        card.style.opacity = '0';

                        setTimeout(function() {
                            if (card.parentNode) {
                                card.parentNode.removeChild(card);

                                // Update product count if visible
                                updateProductCounts();
                            }
                        }, 400);
                    } else {
                        // Fallback for table rows
                        var row = form.closest('tr');
                        if (row) {
                            row.style.transition = 'all 0.4s ease';
                            row.style.transform = 'scale(0.95)';
                            row.style.opacity = '0';
                            setTimeout(function() {
                                if (row.parentNode) {
                                    row.parentNode.removeChild(row);
                                }
                            }, 400);
                        }
                    }

                    // Show enhanced success notice
                    showNotice(data.message || productType + ' deleted successfully.', 'success');
                }).catch(function (err) {
                    if (err === 'redirect') return;

                    // Remove loading states
                    if (card) {
                        card.classList.remove('deleting');
                    }

                    var msg = (err && err.message) ? err.message : 'Failed to delete ' + productType.toLowerCase() + '. Please refresh and try again.';
                    showNotice(msg, 'error');
                }).finally(function () {
                    // Restore button state
                    btn.disabled = originalDisabled;
                    btn.className = originalClass;
                    btn.innerHTML = originalHtml;
                });
            });

            // Function to update product counts after deletion
            function updateProductCounts() {
                // Update total count in pagination info
                var entriesInfo = document.querySelector('.entries-info');
                if (entriesInfo) {
                    var currentText = entriesInfo.textContent;
                    var match = currentText.match(/Showing (\d+) to (\d+) of (\d+) entries/);
                    if (match) {
                        var firstItem = parseInt(match[1]);
                        var lastItem = parseInt(match[2]);
                        var total = parseInt(match[3]) - 1;

                        if (total > 0) {
                            var newLastItem = Math.min(lastItem - 1, total);
                            entriesInfo.textContent = 'Showing ' + firstItem + ' to ' + newLastItem + ' of ' + total + ' entries';
                        } else {
                            entriesInfo.textContent = 'No entries';
                        }
                    }
                }
            }

            const uploadConfirmModal = document.getElementById('upload-confirm-modal');
            const proceedUpload = document.getElementById('proceed-upload');
            const cancelConfirm = document.getElementById('cancel-confirm');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let currentUploadUrl = null;
            let currentProductId = null;
            let currentCard = null;
            let submitBtnOriginalLabel = '';

            const unpublishConfirmModal = document.getElementById('unpublish-confirm-modal');
            const proceedUnpublish = document.getElementById('proceed-unpublish');
            const cancelUnpublish = document.getElementById('cancel-unpublish');
            const unpublishReason = document.getElementById('unpublish-reason');
            let currentUnpublishUrl = null;
            let currentUnpublishBtn = null;

            function closeConfirmModal() {
                if (!uploadConfirmModal) return;
                uploadConfirmModal.classList.remove('show');
                uploadConfirmModal.setAttribute('aria-hidden', 'true');
                uploadConfirmModal.style.display = 'none';
            }

            function showConfirmModal() {
                if (!uploadConfirmModal) return;
                uploadConfirmModal.classList.add('show');
                uploadConfirmModal.setAttribute('aria-hidden', 'false');
                uploadConfirmModal.style.display = 'flex';
            }

            function closeUnpublishModal() {
                if (!unpublishConfirmModal) return;
                unpublishConfirmModal.classList.remove('show');
                unpublishConfirmModal.setAttribute('aria-hidden', 'true');
                unpublishConfirmModal.style.display = 'none';
                if (unpublishReason) unpublishReason.value = '';
            }

            function showUnpublishModal() {
                if (!unpublishConfirmModal) return;
                unpublishConfirmModal.classList.add('show');
                unpublishConfirmModal.setAttribute('aria-hidden', 'false');
                unpublishConfirmModal.style.display = 'flex';
            }

            function showNotice(message, type = 'success', options = {}) {
                const notice = document.createElement('div');
                notice.className = `ajax-notice ajax-${type}`;
                notice.style.position = 'fixed';
                notice.style.right = '20px';
                notice.style.top = '20px';
                notice.style.zIndex = '9999';
                notice.style.background = type === 'success' ? '#16a34a' : '#dc2626';
                notice.style.color = '#fff';
                notice.style.padding = '8px 12px';
                notice.style.borderRadius = '6px';
                notice.textContent = '';
                notice.appendChild(document.createTextNode(message));
                if (options?.link?.href) {
                    const spacer = document.createTextNode(' ');
                    const link = document.createElement('a');
                    link.href = options.link.href;
                    link.target = options.link.target || '_blank';
                    link.rel = options.link.rel || 'noopener';
                    link.textContent = options.link.text || 'View';
                    link.style.color = '#fff';
                    link.style.textDecoration = 'underline';
                    notice.appendChild(spacer);
                    notice.appendChild(link);
                }
                document.body.appendChild(notice);
                setTimeout(() => { try { notice.remove(); } catch (e) {} }, 3500);
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-upload');
                if (!btn) return;

                e.preventDefault();
                if (!uploadConfirmModal) return;

                currentUploadUrl = btn.getAttribute('data-upload-url');
                currentProductId = btn.getAttribute('data-id');
                currentCard = btn.closest('.product-card');
                const catalogUrl = btn.getAttribute('data-customer-url')
                    || currentCard?.getAttribute('data-customer-url')
                    || `${window.location.origin}/templates/wedding/invitations`;

                if (!currentUploadUrl || !csrfToken) {
                    showNotice('Upload configuration missing.', 'error');
                    return;
                }

                // Get product details for confirmation message
                const productType = currentCard ? currentCard.getAttribute('data-product-type') : '';
                const productName = currentCard ? currentCard.getAttribute('data-name') : 'this product';
                const catalogLink = `<a href="${catalogUrl}" target="_blank" rel="noopener">${catalogUrl}</a>`;

                // Update confirmation message
                const confirmMessage = uploadConfirmModal.querySelector('.confirm-message');
                if (confirmMessage) {
                    confirmMessage.innerHTML = `
                        <strong>Publish "${productName}" to customer catalog</strong><br><br>
                        Once published, customers will be able to:<br>
                        • Browse this ${productType.toLowerCase()} in the public catalog<br>
                        • Preview and personalize the design<br>
                        • Place orders directly from the listing<br><br>
                        <em>Publish now?</em>
                    `;
                }

                uploadConfirmModal.classList.add('show');
                uploadConfirmModal.setAttribute('aria-hidden', 'false');
                uploadConfirmModal.style.display = 'flex';
            });

            if (proceedUpload) {
                proceedUpload.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Show loading state
                    const originalText = proceedUpload.innerHTML;
                    proceedUpload.disabled = true;
                    proceedUpload.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

                    // Make AJAX request to publish the template
                    fetch(currentUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }).then(function (response) {
                        return response.json();
                    }).then(function (data) {
                        if (data.success) {
                            const customerUrl = currentCard?.getAttribute('data-customer-url')
                                || currentCard?.querySelector('.btn-upload')?.getAttribute('data-customer-url')
                                || `${window.location.origin}/templates/wedding/invitations`;
                            const linkOptions = customerUrl ? { link: { href: customerUrl, text: 'View customer page', target: '_blank', rel: 'noopener' } } : {};
                            showNotice(data.message || 'Template published to customer catalog successfully.', 'success', linkOptions);

                            if (currentCard) {
                                const uploadBtn = currentCard.querySelector('.btn-upload');
                                const unuploadBtn = currentCard.querySelector('.btn-unupload');
                                if (uploadBtn) {
                                    uploadBtn.classList.add('disabled');
                                    uploadBtn.setAttribute('disabled', 'disabled');
                                }
                                if (unuploadBtn) {
                                    unuploadBtn.classList.remove('disabled');
                                    unuploadBtn.removeAttribute('disabled');
                                }
                                currentCard.setAttribute('data-published', '1');
                            }

                            closeConfirmModal();
                        } else {
                            throw new Error(data.message || 'Failed to publish template.');
                        }
                    }).catch(function (error) {
                        console.error('Publish error:', error);
                        showNotice(error.message || 'Failed to publish template. Please try again.', 'error');
                    }).finally(function () {
                        // Restore button state
                        proceedUpload.disabled = false;
                        proceedUpload.innerHTML = originalText;
                    });
                });
            }

            if (cancelConfirm) {
                cancelConfirm.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeConfirmModal();
                });
            }

            if (proceedUnpublish) {
                proceedUnpublish.addEventListener('click', function (e) {
                    e.preventDefault();

                    const reason = unpublishReason ? unpublishReason.value.trim() : '';
                    const payload = new URLSearchParams();
                    payload.append('_token', csrfToken || '');
                    if (reason) {
                        payload.append('reason', reason);
                    }

                    const btn = currentUnpublishBtn;
                    if (!btn || !currentUnpublishUrl) return;

                    // Show loading state
                    const originalText = proceedUnpublish.innerHTML;
                    proceedUnpublish.disabled = true;
                    proceedUnpublish.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Unpublishing...';

                    fetch(currentUnpublishUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: payload.toString(),
                    }).then(res => {
                        if (!res.ok) {
                            throw new Error('Request failed');
                        }
                        return res.json().catch(() => ({ success: true }));
                    }).then(data => {
                        if (!data || data.success !== true) {
                            throw new Error(data?.message || 'Unable to unpublish product.');
                        }

                        closeUnpublishModal();

                        btn.classList.add('disabled');
                        btn.disabled = true;

                        selectedProducts.delete(btn.getAttribute('data-id'));
                        updateBulkDeleteButton();

                        const card = btn.closest('.product-card');
                        if (card) {
                            const uploadBtn = card.querySelector('.btn-upload');
                            const unuploadBtn = card.querySelector('.btn-unupload');
                            if (uploadBtn) {
                                uploadBtn.classList.remove('disabled');
                                uploadBtn.removeAttribute('disabled');
                            }
                            if (unuploadBtn) {
                                unuploadBtn.classList.add('disabled');
                                unuploadBtn.setAttribute('disabled', 'disabled');
                            }
                            card.setAttribute('data-published', '0');
                        }

                        showNotice('Product unpublished successfully.', 'success');
                    }).catch(err => {
                        console.error(err);
                        showNotice(err?.message || 'Failed to unpublish product.', 'error');
                    }).finally(() => {
                        // Restore button state
                        proceedUnpublish.disabled = false;
                        proceedUnpublish.innerHTML = originalText;
                    });
                });
            }

            if (cancelUnpublish) {
                cancelUnpublish.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeUnpublishModal();
                });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-unupload');
                if (!btn) return;

                e.preventDefault();

                const card = btn.closest('.product-card');
                const productName = card ? card.getAttribute('data-name') : 'this product';

                // Update confirmation message
                const confirmMessage = unpublishConfirmModal.querySelector('.confirm-message');
                if (confirmMessage) {
                    confirmMessage.innerHTML = `<strong>Unpublish "${productName}"?</strong><br><br>This will remove it from the customer catalog.`;
                }

                currentUnpublishUrl = btn.getAttribute('data-unupload-url');
                currentUnpublishBtn = btn;

                showUnpublishModal();
            });

            if (uploadConfirmModal) {
                uploadConfirmModal.addEventListener('click', function (e) {
                    if (e.target === uploadConfirmModal) {
                        closeConfirmModal();
                    }
                });
            }

            if (unpublishConfirmModal) {
                unpublishConfirmModal.addEventListener('click', function (e) {
                    if (e.target === unpublishConfirmModal) {
                        closeUnpublishModal();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    if (uploadConfirmModal?.classList.contains('show')) {
                        closeConfirmModal();
                    }
                    if (unpublishConfirmModal?.classList.contains('show')) {
                        closeUnpublishModal();
                    }
                }
            });



            // AJAX slide-panel removed: viewing product details via slide panel is disabled.
            // Use the product view route or implement a new modal if desired.

            // Bulk selection functionality
            let selectedProducts = new Set();

            // Handle individual checkbox changes
            document.addEventListener('change', function(e) {
                if (!e.target.classList.contains('product-checkbox')) return;

                const checkbox = e.target;
                const productId = checkbox.value;
                const isChecked = checkbox.checked;

                if (isChecked) {
                    selectedProducts.add(productId);
                } else {
                    selectedProducts.delete(productId);
                }

                updateBulkDeleteButton();
            });

            // Update bulk delete button state
            function updateBulkDeleteButton() {
                const count = selectedProducts.size;
                const btn = document.getElementById('bulk-delete-btn');
                const countSpan = document.getElementById('selected-count');

                if (!btn || !countSpan) return;

                countSpan.textContent = count;

                if (count > 0) {
                    btn.style.display = 'inline-block';
                    btn.disabled = false;
                } else {
                    btn.style.display = 'none';
                    btn.disabled = true;
                }
            }

            // Bulk delete handler
            document.addEventListener('click', function(e) {
                if (e.target.id !== 'bulk-delete-btn' && !e.target.closest('#bulk-delete-btn')) return;

                const count = selectedProducts.size;
                const productIds = Array.from(selectedProducts);

                if (count === 0) return;

                const confirmMessage = `Are you sure you want to delete ${count} selected product${count > 1 ? 's' : ''}? This action cannot be undone.`;

                if (!confirm(confirmMessage)) return;

                const btn = document.getElementById('bulk-delete-btn');
                const originalText = btn.innerHTML;

                // Disable button and show loading
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

                // Delete products one by one
                const deletePromises = productIds.map(id => {
                    return fetch(`/admin/products/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                });

                Promise.allSettled(deletePromises).then(results => {
                    let successCount = 0;
                    let failCount = 0;

                    results.forEach((result, index) => {
                        if (result.status === 'fulfilled' && result.value.ok) {
                            successCount++;
                            // Remove the card from DOM
                            const card = document.querySelector(`.product-card[data-id="${productIds[index]}"]`);
                            if (card) {
                                card.style.transition = 'all 0.4s ease';
                                card.style.transform = 'scale(0.95)';
                                card.style.opacity = '0';
                                setTimeout(() => {
                                    if (card.parentNode) {
                                        card.parentNode.removeChild(card);
                                    }
                                }, 400);
                            }
                        } else {
                            failCount++;
                            console.error(`Failed to delete product ${productIds[index]}:`, result.reason);
                        }
                    });

                    // Clear selection
                    selectedProducts.clear();
                    document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    updateBulkDeleteButton();

                    // Show result message
                    let message = '';
                    if (successCount > 0) {
                        message += `${successCount} product${successCount > 1 ? 's' : ''} deleted successfully.`;
                    }
                    if (failCount > 0) {
                        message += ` ${failCount} product${failCount > 1 ? 's' : ''} failed to delete.`;
                    }

                    showNotice(message, failCount > 0 ? 'error' : 'success');

                    // Reset button
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });
        </script>
    @endpush

    {{-- Upload confirmation modal --}}
    <div id="upload-confirm-modal" class="upload-modal">
        <div class="upload-modal-content">
            <h3><i class="fas fa-cloud-upload-alt"></i> Confirm Template Publish</h3>
            <div class="confirm-message" style="text-align: left; margin: 20px 0; line-height: 1.6;">
                <!-- Message will be populated by JavaScript -->
            </div>
            <div class="upload-buttons">
                <button type="button" id="proceed-upload" class="btn-upload-submit">
                    <i class="fas fa-upload"></i> Publish to Customer Pages
                </button>
                <button type="button" id="cancel-confirm" class="btn-upload-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- Unpublish confirmation modal --}}
    <div id="unpublish-confirm-modal" class="upload-modal">
        <div class="upload-modal-content">
            <h3><i class="fas fa-cloud-download-alt"></i> Confirm Template Unpublish</h3>
            <div class="confirm-message" style="text-align: left; margin: 20px 0; line-height: 1.6;">
                <!-- Message will be populated by JavaScript -->
            </div>
            <div class="reason-input" style="margin: 15px 0;">
                <label for="unpublish-reason" style="display: block; margin-bottom: 5px; font-weight: 500;">Add a note for staff (optional):</label>
                <textarea id="unpublish-reason" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="Reason for unpublishing..."></textarea>
            </div>
            <div class="upload-buttons">
                <button type="button" id="proceed-unpublish" class="btn-upload-submit" style="background: #dc3545;">
                    <i class="fas fa-times"></i> Unpublish
                </button>
                <button type="button" id="cancel-unpublish" class="btn-upload-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>



@endsection

