@extends('layouts.staffapp')

@section('title', 'Templates')

@push('styles')
    @vite('resources/css/admin/template/template.css')
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

            .template-status {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 0.7rem;
                font-weight: 600;
                margin-left: auto;
            }

            .template-status--returned {
                background: rgba(220, 38, 38, 0.1);
                color: #b91c1c;
            }

            .template-note {
                margin-top: 8px;
                font-size: 0.85rem;
                color: #b91c1c;
                background: rgba(254, 226, 226, 0.6);
                border-radius: 6px;
                padding: 8px;
            }

        /* Multiple Previews */
        .multiple-previews {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
            height: 200px;
            overflow: hidden;
        }

        .preview-item {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #d1d5db;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item:hover {
            border-color: #9ca3af;
        }
    </style>
@endpush

@push('scripts')
    @vite('resources/js/admin/template/template.js')
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
                // Handle remove preview (AJAX)
                if (e.target && e.target.classList.contains('remove-preview')) {
                    e.preventDefault();
                    const btn = e.target;
                    const form = btn.closest('.remove-preview-form');
                    const previewId = form ? form.getAttribute('data-preview-id') : null;
                    if (!previewId) return;

                    btn.disabled = true;
                    btn.textContent = 'Removing...';

                    fetch(`{{ url('staff/templates/preview') }}/${previewId}/remove`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    }).then(r => r.json()).then(data => {
                        if (data && data.success) {
                            const card = btn.closest('.template-card');
                            if (card) card.remove();
                            showToast('Preview removed', 'info');
                        } else {
                            showToast('Failed to remove preview', 'error');
                            btn.disabled = false;
                            btn.textContent = 'Remove';
                        }
                    }).catch(err => {
                        console.error(err);
                        showToast('Failed to remove preview', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Remove';
                    });
                }
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
                        // Disable buttons and show uploading state
                        cancelBtn.disabled = true;
                        confirmBtn.disabled = true;
                        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                        
                        // Update dialog content to show progress
                        const dialogBody = confirmDialog.querySelector('.confirm-dialog-body');
                        dialogBody.innerHTML = `
                            <p>Uploading <strong>"${templateName}"</strong>...</p>
                            <p class="confirm-info">Please wait while we process your template.</p>
                        `;

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
                            // Update dialog for success
                            dialogBody.innerHTML = `
                                <p><strong>"${templateName}"</strong> uploaded successfully!</p>
                                <p class="confirm-info">The template has been published.</p>
                            `;
                            confirmDialog.querySelector('.confirm-dialog-header').style.background = 'linear-gradient(135deg, #d1fae5, #a7f3d0)';
                            confirmDialog.querySelector('.confirm-dialog-header').style.color = '#065f46';
                            confirmDialog.querySelector('.confirm-dialog-header i').className = 'fas fa-check-circle';
                            confirmDialog.querySelector('.confirm-dialog-header i').style.color = '#065f46';
                            
                            // Change button to close
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = 'Close';
                            confirmBtn.style.background = '#10b981';
                            confirmBtn.addEventListener('click', () => {
                                confirmDialog.classList.remove('show');
                                setTimeout(() => confirmDialog.remove(), 300);

                                // Redirect to provided route (uploaded page) or reload
                                setTimeout(() => {
                                    try {
                                        if (data && data.redirect) {
                                            window.location = data.redirect;
                                        } else {
                                            window.location.reload();
                                        }
                                    } catch (e) {
                                        window.location.reload();
                                    }
                                }, 500);
                            });

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
                        })
                        .catch(error => {
                            console.error('Upload error:', error);
                            
                            // Update dialog for error
                            dialogBody.innerHTML = `
                                <p>Failed to upload <strong>"${templateName}"</strong></p>
                                <p class="confirm-info">Please try again or contact support if the problem persists.</p>
                            `;
                            confirmDialog.querySelector('.confirm-dialog-header').style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
                            confirmDialog.querySelector('.confirm-dialog-header').style.color = '#dc2626';
                            confirmDialog.querySelector('.confirm-dialog-header i').className = 'fas fa-exclamation-triangle';
                            confirmDialog.querySelector('.confirm-dialog-header i').style.color = '#dc2626';
                            
                            // Reset buttons
                            cancelBtn.disabled = false;
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = 'Try Again';
                            confirmBtn.style.background = '#dc2626';
                            
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
            });
        });
    </script>
@endpush

@section('content')
    @php
        $normalizePreview = function ($value) {
            if (!is_string($value)) {
                return null;
            }
            return str_replace(["\r", "\n", "\t"], '', trim($value));
        };
    @endphp
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

        @if(session('template_import_warnings'))
            @php
                $warnings = (array) session('template_import_warnings');
                $warnings = array_filter($warnings, fn($message) => !empty($message));
            @endphp
            @if(count($warnings))
                <div class="alert alert-warning" role="status">
                    <strong>Heads up:</strong>
                    <ul class="mt-2" style="margin-bottom:0;padding-left:18px;">
                        @foreach($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

    @if((empty($previewTemplates) || count($previewTemplates) === 0) && $templates->isEmpty())
            <div class="empty-state mt-gap">
                <div class="empty-state-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3 class="empty-state-title">No drafts</h3>
                <p class="empty-state-description">Create a template to preview it here. Use Upload to persist the preview to the templates table.</p>
            </div>
        @else
            <div class="templates-grid mt-gap" role="list">
                {{-- Render any session previews first --}}
                @if(isset($previewTemplates) && count($previewTemplates))
                    @foreach($previewTemplates as $preview)
                        <article class="template-card" role="listitem">
                            <div class="template-preview">
                                @if(!empty($preview['front_image']))
                                    <img src="{{ \App\Support\ImageResolver::url($preview['front_image']) }}" alt="Preview of {{ $preview['name'] }}">
                                @elseif(!empty($preview['front_svg_content']))
                                    <div class="svg-preview-container" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;">
                                        {!! $preview['front_svg_content'] !!}
                                    </div>
                                @else
                                    <span>No preview</span>
                                @endif
                                @if(!empty($preview['back_image']))
                                    <img src="{{ \App\Support\ImageResolver::url($preview['back_image']) }}" alt="Back of {{ $preview['name'] }}" class="back-thumb">
                                @elseif(!empty($preview['back_svg_content']))
                                    <div class="svg-preview-container back-thumb" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;position:absolute;bottom:8px;right:8px;">
                                        {!! $preview['back_svg_content'] !!}
                                    </div>
                                @endif
                            </div>
                            <div class="template-info">
                                <div class="template-meta">
                                    <span class="template-category">{{ $preview['product_type'] ?? 'Uncategorized' }}</span>
                                    <span class="template-date">Preview</span>
                                </div>
                                <h3 class="template-title">{{ $preview['name'] }}</h3>
                                @if(!empty($preview['description']))
                                    <p class="template-description">{{ $preview['description'] }}</p>
                                @endif
                            </div>
                            <div class="template-actions">
                                <div class="action-buttons">
                                    <a href="{{ route('staff.templates.create', ['type' => strtolower($preview['product_type'] ?? 'invitation'), 'edit_preview' => $preview['id']]) }}" class="btn-action btn-edit" title="Edit Template Details">Edit</a>
                                    <button type="button" class="btn-action btn-upload upload-template-btn" 
                                            data-template-id="{{ $preview['id'] }}"
                                            data-template-name="{{ $preview['name'] }}"
                                            data-upload-url="{{ route('staff.templates.preview.save', ['preview' => $preview['id']]) }}"
                                            title="Upload Template">
                                        Upload
                                    </button>
                                </div>
                                <form action="#" method="POST" class="delete-form remove-preview-form" data-preview-id="{{ $preview['id'] }}">
                                    @csrf
                                    <button type="button" class="btn-action btn-delete remove-preview" title="Remove Preview">Remove</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                @endif

                @foreach($templates as $template)
                    <article class="template-card" role="listitem">
                        @php
                            $front = null;
                            $back = null;

                            $frontCandidates = [
                                $template->front_image,
                                $template->preview_front,
                                data_get($template->metadata, 'previews.front'),
                                $template->preview,
                            ];
                            foreach ($frontCandidates as $candidate) {
                                if (!empty($candidate)) {
                                    $front = $candidate;
                                    break;
                                }
                            }

                            $backCandidates = [
                                $template->back_image,
                                $template->preview_back,
                                data_get($template->metadata, 'previews.back'),
                            ];
                            foreach ($backCandidates as $candidate) {
                                if (!empty($candidate)) {
                                    $back = $candidate;
                                    break;
                                }
                            }

                            // Check if front image is an SVG file
                            $isSvgFront = $template->svg_path || ($front && str_ends_with(strtolower($front), '.svg'));
                            $isSvgBack = $template->back_svg_path || ($back && str_ends_with(strtolower($back), '.svg'));

                            // Check for multiple previews in metadata
                            $multiplePreviews = is_array($template->metadata['previews'] ?? null)
                                ? $template->metadata['previews']
                                : [];
                            $previewMeta = is_array($template->metadata['preview_images_meta'] ?? null)
                                ? $template->metadata['preview_images_meta']
                                : [];
                            $hasMultiplePreviews = count($multiplePreviews) > 0;

                            $templatePreviewClasses = 'template-preview' . ($hasMultiplePreviews ? ' template-preview--multi' : '');
                        @endphp
                        <div class="{{ $templatePreviewClasses }}">
                            @if($hasMultiplePreviews)
                                @php
                                    $orderedPreviews = collect($multiplePreviews)
                                        ->map(function ($path, $key) use ($previewMeta, $normalizePreview) {
                                            $normalizedPath = $normalizePreview($path);
                                            $meta = $previewMeta[$key] ?? [];
                                            $label = $meta['label'] ?? \Illuminate\Support\Str::title(str_replace(['-', '_'], ' ', $key));
                                            $order = array_key_exists('order', $meta) ? (int) $meta['order'] : null;

                                            return [
                                                'key' => $key,
                                                'path' => $normalizedPath,
                                                'label' => $label,
                                                'order' => $order,
                                            ];
                                        })
                                        ->sortBy(function ($item, $index) {
                                            return $item['order'] ?? $index;
                                        })
                                        ->values();
                                @endphp
                                <div class="multiple-previews" role="list">
                                    @foreach($orderedPreviews as $previewItem)
                                        @php
                                            $isPrimary = $previewItem['key'] === 'front' || $loop->first;
                                        @endphp
                                        <figure class="preview-item{{ $isPrimary ? ' preview-item--primary' : '' }}" role="listitem" aria-label="{{ $previewItem['label'] }}">
                                            <img src="{{ \App\Support\ImageResolver::url($previewItem['path']) }}" alt="{{ $previewItem['label'] }}" loading="lazy">
                                            <figcaption class="preview-item__label">{{ $previewItem['label'] }}</figcaption>
                                        </figure>
                                    @endforeach
                                </div>
                            @elseif($front)
                                @if($isSvgFront)
                                    @php
                                        // Read SVG content from file
                                        $svgPath = $template->svg_path ?? $front;
                                        $svgContent = '';
                                        try {
                                            $svgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($svgPath);
                                        } catch (\Exception $e) {
                                            $svgContent = '<div class="svg-error">SVG not found</div>';
                                        }
                                    @endphp
                                    @php
                                        if (is_string($svgContent)) {
                                            // Strip XML declarations that break inline rendering in HTML
                                            $trimmed = ltrim($svgContent);
                                            if (strpos($trimmed, '<?xml') === 0) {
                                                $svgContent = preg_replace('/^<\?xml[^>]+>\s*/', '', $trimmed);
                                            }
                                            if (strpos(ltrim($svgContent), '<!DOCTYPE') === 0) {
                                                $svgContent = preg_replace('/^<!DOCTYPE[^>]+>\s*/i', '', ltrim($svgContent));
                                            }
                                        }
                                    @endphp
                                    <div class="svg-preview-container" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;">
                                        {!! $svgContent !!}
                                    </div>
                                @else
                                    <img src="{{ \App\Support\ImageResolver::url($front) }}" alt="Preview of {{ $template->name }}" loading="lazy">
                                @endif
                            @else
                                <span>No preview</span>
                            @endif
                            @if($back)
                                @if($isSvgBack)
                                    @php
                                        // Read SVG content from file for back
                                        $backSvgPath = $template->back_svg_path ?? $back;
                                        $backSvgContent = '';
                                        try {
                                            $backSvgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($backSvgPath);
                                        } catch (\Exception $e) {
                                            $backSvgContent = '<div class="svg-error">SVG not found</div>';
                                        }
                                    @endphp
                                    @php
                                        if (is_string($backSvgContent)) {
                                            // Strip XML declarations that break inline rendering in HTML
                                            $trimmedBack = ltrim($backSvgContent);
                                            if (strpos($trimmedBack, '<?xml') === 0) {
                                                $backSvgContent = preg_replace('/^<\?xml[^>]+>\s*/', '', $trimmedBack);
                                            }
                                            if (strpos(ltrim($backSvgContent), '<!DOCTYPE') === 0) {
                                                $backSvgContent = preg_replace('/^<!DOCTYPE[^>]+>\s*/i', '', ltrim($backSvgContent));
                                            }
                                        }
                                    @endphp
                                    <div class="svg-preview-container back-thumb" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;position:absolute;bottom:8px;right:8px;">
                                        {!! $backSvgContent !!}
                                    </div>
                                @else
                                    <img src="{{ \App\Support\ImageResolver::url($back) }}" alt="Back of {{ $template->name }}" class="back-thumb" loading="lazy">
                                @endif
                            @endif
                        </div>
                        <div class="template-info">
                            <div class="template-meta">
                                <span class="template-category">{{ $template->product_type ?? 'Uncategorized' }}</span>
                                @if($template->updated_at)
                                    <span class="template-date">{{ $template->updated_at->format('M d, Y') }}</span>
                                @endif
                                @if($template->status === 'returned')
                                        <span class="template-status template-status--returned">Returned</span>
                                @endif
                            </div>
                            <h3 class="template-title">{{ $template->name }}</h3>
                            @if($template->description)
                                <p class="template-description">{{ $template->description }}</p>
                            @endif
                            @if($template->status === 'returned' && $template->status_note)
                                    <div class="template-note">
                                        <strong>Admin Note:</strong> {{ $template->status_note }}
                                    </div>
                            @endif
                        </div>
                        <div class="template-actions">
                            <div class="action-buttons">
                                <a href="{{ route('staff.templates.edit', $template->id) }}" class="btn-action btn-edit" title="Edit Template Details">
                                    Edit
                                </a>
                                <a href="{{ route('staff.templates.editor', $template->id) }}" class="btn-action btn-editor" title="Open in Editor">
                                    Editor
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
