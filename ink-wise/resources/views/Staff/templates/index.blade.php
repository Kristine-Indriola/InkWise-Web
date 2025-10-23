@extends('layouts.staffapp')

@section('title', 'Templates')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
    <style>
        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 0;
            min-width: 300px;
            max-width: 400px;
            z-index: 10000;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-notification.toast-success {
            border-left-color: #10b981;
        }

        .toast-notification.toast-error {
            border-left-color: #ef4444;
        }

        .toast-notification.toast-info {
            border-left-color: #3b82f6;
        }

        .toast-content {
            display: flex;
            align-items: center;
            padding: 16px;
            gap: 12px;
        }

        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .toast-notification.toast-success .toast-icon {
            color: #10b981;
        }

        .toast-notification.toast-error .toast-icon {
            color: #ef4444;
        }

        .toast-notification.toast-info .toast-icon {
            color: #3b82f6;
        }

        .toast-message {
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
            color: #374151;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0 16px 0 8px;
            transition: color 0.2s;
        }

        .toast-close:hover {
            color: #6b7280;
        }

        /* Button Loading States */
        .btn-action.btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-action.btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Template Card States */
        .template-card.uploading {
            opacity: 0.7;
            pointer-events: none;
        }

        .template-card.removing {
            animation: slideOut 0.3s ease forwards;
        }

        @keyframes slideOut {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        /* Empty State Animation */
        .empty-state.fade-in {
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Confirmation Dialog */
        .confirm-dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10001;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .confirm-dialog-overlay.show {
            opacity: 1;
        }

        .confirm-dialog {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            max-width: 400px;
            width: 90%;
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .confirm-dialog-overlay.show .confirm-dialog {
            transform: scale(1);
        }

        .confirm-dialog-header {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            padding: 20px;
            text-align: center;
            color: #dc2626;
        }

        .confirm-dialog-header i {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .confirm-dialog-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .confirm-dialog-body {
            padding: 24px;
            text-align: center;
        }

        .confirm-dialog-body p {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #374151;
        }

        .confirm-info {
            color: #6b7280 !important;
            font-size: 14px;
            margin-top: 8px !important;
        }

        .confirm-dialog-actions {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .confirm-dialog-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .btn-confirm {
            background: #dc2626;
            color: white;
        }

        .btn-confirm:hover:not(:disabled) {
            background: #b91c1c;
        }

        .btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Enhanced Button Styles */
        .btn-action {
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .btn-action:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-action:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Filter Button Enhancements */
        .filter-btn {
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
        }

        .filter-btn.active {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create toast notification system
            function showToast(message, type = 'success', duration = 3000) {
                // Remove existing toasts
                const existingToasts = document.querySelectorAll('.toast-notification');
                existingToasts.forEach(toast => toast.remove());

                // Create toast element
                const toast = document.createElement('div');
                toast.className = `toast-notification toast-${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <i class="toast-icon ${type === 'success' ? 'fas fa-check' : type === 'error' ? 'fas fa-times' : 'fas fa-info'}"></i>
                        <span class="toast-message">${message}</span>
                    </div>
                    <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
                `;

                // Add to page
                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => toast.classList.add('show'), 10);

                // Auto remove
                if (duration > 0) {
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 300);
                    }, duration);
                }

                return toast;
            }

            // Handle upload template button clicks using event delegation
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('upload-template-btn')) {
                    e.preventDefault(); // Prevent any default behavior
                    
                    const button = e.target;
                    const templateId = button.getAttribute('data-template-id');
                    const templateName = button.getAttribute('data-template-name');

                    // Create smooth confirmation dialog for upload
                    const confirmDialog = document.createElement('div');
                    confirmDialog.className = 'confirm-dialog-overlay';
                    confirmDialog.innerHTML = `
                        <div class="confirm-dialog">
                            <div class="confirm-dialog-header">
                                <i class="fas fa-upload"></i>
                                <h3>Upload Template</h3>
                            </div>
                            <div class="confirm-dialog-body">
                                <p>Upload <strong>"${templateName}"</strong> to published templates?</p>
                                <p class="confirm-warning">This action cannot be undone.</p>
                            </div>
                            <div class="confirm-dialog-actions">
                                <button class="btn-cancel">Cancel</button>
                                <button class="btn-confirm">Upload</button>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(confirmDialog);

                    // Animate in
                    setTimeout(() => confirmDialog.classList.add('show'), 10);

                    // Handle button clicks
                    const cancelBtn = confirmDialog.querySelector('.btn-cancel');
                    const confirmBtn = confirmDialog.querySelector('.btn-confirm');

                    cancelBtn.addEventListener('click', () => {
                        confirmDialog.classList.remove('show');
                        setTimeout(() => confirmDialog.remove(), 300);
                    });

                    confirmBtn.addEventListener('click', () => {
                        // Remove confirmation dialog
                        confirmDialog.classList.remove('show');
                        setTimeout(() => confirmDialog.remove(), 300);

                        const originalText = button.textContent;

                        // Add loading state with smooth animation
                        button.disabled = true;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading';
                        button.classList.add('btn-loading');

                        // Add visual feedback to the template card
                        const templateCard = button.closest('.template-card');
                        if (templateCard) {
                            templateCard.classList.add('uploading');
                        }

                        // Show initial loading toast
                        const loadingToast = showToast('Uploading', 'info', 0);

                        // Make AJAX request to upload template
                        const uploadUrl = button.getAttribute('data-upload-url');
                        fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Remove loading toast
                            if (loadingToast) {
                                loadingToast.remove();
                            }

                            if (data.success) {
                                // Show success toast with more detailed message
                                showToast('Uploaded successfully', 'success', 3000);

                                // Smoothly remove the template card with animation
                                if (templateCard) {
                                    templateCard.classList.add('removing');
                                    setTimeout(() => {
                                        templateCard.remove();

                                        // Check if there are any templates left
                                        const remainingTemplates = document.querySelectorAll('.template-card');
                                        if (remainingTemplates.length === 0) {
                                            // Show empty state with smooth animation
                                            const templatesGrid = document.querySelector('.templates-grid');
                                            if (templatesGrid) {
                                                const emptyState = document.createElement('div');
                                                emptyState.className = 'empty-state mt-gap fade-in';
                                                emptyState.innerHTML = `
                                                    <div class="empty-state-icon">
                                                        <i class="fas fa-paint-brush"></i>
                                                    </div>
                                                    <h3 class="empty-state-title">All uploaded</h3>
                                                    <p class="empty-state-description">Check uploaded templates page for your published designs.</p>
                                                `;
                                                templatesGrid.innerHTML = '';
                                                templatesGrid.appendChild(emptyState);
                                            }
                                        }
                                    }, 300);
                                }
                            } else {
                                // Show error toast with more helpful message
                                const errorMsg = data.message || 'Upload failed';
                                showToast(errorMsg, 'error', 4000);

                                // Reset button state
                                button.disabled = false;
                                button.innerHTML = originalText;
                                button.classList.remove('btn-loading');

                                // Remove uploading state from card
                                if (templateCard) {
                                    templateCard.classList.remove('uploading');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Upload error:', error);

                            // Remove loading toast
                            if (loadingToast) {
                                loadingToast.remove();
                            }

                            // Show error toast with network-specific message
                            showToast('Upload failed', 'error', 3000);

                            // Reset button state
                            button.disabled = false;
                            button.innerHTML = originalText;
                            button.classList.remove('btn-loading');

                            // Remove uploading state from card
                            if (templateCard) {
                                templateCard.classList.remove('uploading');
                            }
                        });
                    });
                }
            });            // Handle delete button clicks with smooth confirmation
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const form = this.closest('form');
                    const templateCard = this.closest('.template-card');
                    const templateName = templateCard ? templateCard.querySelector('.template-title').textContent : 'this template';

                    // Create custom confirmation dialog for delete
                    const confirmDialog = document.createElement('div');
                    confirmDialog.className = 'confirm-dialog-overlay';
                    confirmDialog.innerHTML = `
                        <div class="confirm-dialog">
                            <div class="confirm-dialog-header">
                                <i class="fas fa-trash-alt"></i>
                                <h3>Delete</h3>
                            </div>
                            <div class="confirm-dialog-body">
                                <p>Delete <strong>"${templateName}"</strong>?</p>
                                <p class="confirm-warning">This cannot be undone.</p>
                            </div>
                            <div class="confirm-dialog-actions">
                                <button class="btn-cancel">Cancel</button>
                                <button class="btn-confirm">Delete</button>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(confirmDialog);

                    // Animate in
                    setTimeout(() => confirmDialog.classList.add('show'), 10);

                    // Handle button clicks
                    const cancelBtn = confirmDialog.querySelector('.btn-cancel');
                    const confirmBtn = confirmDialog.querySelector('.btn-confirm');

                    cancelBtn.addEventListener('click', () => {
                        confirmDialog.classList.remove('show');
                        setTimeout(() => confirmDialog.remove(), 300);
                    });

                    confirmBtn.addEventListener('click', () => {
                        // Add loading state
                        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting';
                        confirmBtn.disabled = true;

                        // Show loading toast
                        const loadingToast = showToast('Deleting', 'info', 0);

                        // Submit form after a brief delay for visual feedback
                        setTimeout(() => {
                            form.submit();
                        }, 500);
                    });
                });
            });
        });
    </script>
@endpush

@section('content')
    <main class="dashboard-container templates-page" role="main">
        <section class="templates-container" aria-labelledby="templates-heading">
            <div class="templates-header">
                <div>
                    <h2 id="templates-heading">Templates</h2>
                    <p>Manage and create beautiful templates</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('staff.templates.uploaded') }}" class="btn-secondary">
                        <span>View Uploaded Templates</span>
                    </a>
                    <div class="create-dropdown-container">
                        <button class="btn-create dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                            <span aria-hidden="true">+</span>
                            <span>Create Template</span>
                            <span class="dropdown-arrow" aria-hidden="true">▼</span>
                        </button>
                        <div class="create-dropdown-menu" role="menu" aria-hidden="true">
                            <a href="{{ route('staff.templates.create', ['type' => 'invitation']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">■</span>
                                <span>Invitation</span>
                            </a>
                            <a href="{{ route('staff.templates.create', ['type' => 'giveaway']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">●</span>
                                <span>Giveaway</span>
                            </a>
                            <a href="{{ route('staff.templates.create', ['type' => 'envelope']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">▬</span>
                                <span>Envelope</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="templates-filters">
                <div class="filter-buttons" role="group" aria-label="Filter templates">
                    <a href="{{ route('staff.templates.index') }}" class="filter-btn {{ !$type ?? true ? 'active' : '' }}" data-filter="all">
                        All
                    </a>
                    <a href="{{ route('staff.templates.index', ['type' => 'invitation']) }}" class="filter-btn {{ $type === 'invitation' ? 'active' : '' }}" data-filter="invitation">
                        Invitations
                    </a>
                    <a href="{{ route('staff.templates.index', ['type' => 'giveaway']) }}" class="filter-btn {{ $type === 'giveaway' ? 'active' : '' }}" data-filter="giveaway">
                        Giveaways
                    </a>
                    <a href="{{ route('staff.templates.index', ['type' => 'envelope']) }}" class="filter-btn {{ $type === 'envelope' ? 'active' : '' }}" data-filter="envelope">
                        Envelopes
                    </a>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if($templates->isEmpty())
            <div class="empty-state mt-gap">
                <div class="empty-state-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3 class="empty-state-title">No drafts</h3>
                <p class="empty-state-description">All templates uploaded. Check uploaded templates page.</p>
            </div>
        @else
            <div class="templates-grid mt-gap" role="list">
                @foreach($templates as $template)
                    <article class="template-card" role="listitem">
                        <div class="template-preview">
                            @php
                                $front = $template->front_image ?? $template->preview;
                                $back = $template->back_image ?? null;
                            @endphp
                            @if($front)
                                <img src="{{ \App\Support\ImageResolver::url($front) }}" alt="Preview of {{ $template->name }}">
                            @else
                                <span>No preview</span>
                            @endif
                            @if($back)
                                <img src="{{ \App\Support\ImageResolver::url($back) }}" alt="Back of {{ $template->name }}" class="back-thumb">
                            @endif
                        </div>
                        <div class="template-info">
                            <div class="template-meta">
                                <span class="template-category">{{ $template->product_type ?? 'Uncategorized' }}</span>
                                @if($template->updated_at)
                                    <span class="template-date">{{ $template->updated_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <h3 class="template-title">{{ $template->name }}</h3>
                            @if($template->description)
                                <p class="template-description">{{ $template->description }}</p>
                            @endif
                        </div>
                        <div class="template-actions">
                            <div class="action-buttons">
                                <a href="{{ route('staff.templates.edit', $template->id) }}" class="btn-action btn-edit" title="Edit Template Details">
                                    Edit
                                </a>
                                <button type="button" class="btn-action btn-upload upload-template-btn"
                                        data-template-id="{{ $template->id }}"
                                        data-template-name="{{ $template->name }}"
                                        data-upload-url="{{ route('staff.templates.uploadToProductUploads', $template->id) }}"
                                        data-url="{{ route('staff.templates.uploadToProductUploads', $template->id) }}"
                                        title="Upload Template">
                                    Upload
                                </button>
                            </div>
                            <form action="{{ route('staff.templates.destroy', $template->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" title="Delete Template">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div id="previewModal">
            <span id="closePreview" aria-label="Close preview" role="button">&times;</span>
            <img id="modalImg" src="" alt="Template preview modal">
        </div>
    </main>
@endsection