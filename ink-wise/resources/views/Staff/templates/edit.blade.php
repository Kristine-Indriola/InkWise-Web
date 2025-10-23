@extends('layouts.staffapp')

@php
    $templateType = $template->product_type ?? 'Invitation';
    $productTypeMap = [
        'Invitation' => 'Invitation',
        'Giveaway' => 'Giveaway',
        'Envelope' => 'Envelope'
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
    <style>
        /* Make the create container bigger */
        .create-container{max-width:1400px;margin:0 auto;padding:20px}
        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
    <script>
        // Helper to read CSRF token from meta or hidden input
        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) {
                const v = meta.getAttribute('content');
                if (v) return v;
            }
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        document.querySelector('.edit-form').addEventListener('submit', function(e) {
            // Use default submit only if JS disabled; otherwise handle via fetch
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Basic validation
            if (!formData.get('name')) {
                alert('Please provide a name.');
                return;
            }

            // For invitations, require both front and back images if uploading new ones
            @if($templateType === 'Invitation')
                if (formData.get('front_image') && !formData.get('back_image')) {
                    alert('Please provide both front and back images for invitations.');
                    return;
                }
                if (formData.get('back_image') && !formData.get('front_image')) {
                    alert('Please provide both front and back images for invitations.');
                    return;
                }
            @endif

            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            }).then(async res => {
                if (!res.ok) {
                    let message = 'Update failed';
                    try {
                        const data = await res.json();
                        message = data?.message || JSON.stringify(data);
                    } catch (err) {
                        const txt = await res.text();
                        if (txt) message = txt;
                    }
                    throw new Error(message);
                }
                return res.json();
            }).then(json => {
                if (json && json.success) {
                    alert('Updated successfully');
                    window.location = json.redirect || '{{ route("staff.templates.index") }}';
                } else {
                    alert('Update succeeded but server response unexpected.');
                }
            }).catch(err => {
                console.error(err);
                alert('Update failed: ' + (err.message || 'Unknown'));
            });
        });

        // SVG preview handling for front and back
        function setupPreview(fileInputId, previewId, currentImage) {
            const fileInput = document.getElementById(fileInputId);
            const previewContainer = document.getElementById(previewId);
            if (!fileInput || !previewContainer) return;

            function clearPreview() {
                if (currentImage) {
                    previewContainer.innerHTML = '<img src="' + currentImage + '" alt="Current image" style="max-width:100%;max-height:200px;">';
                } else {
                    previewContainer.innerHTML = '<span class="muted">No image selected</span>';
                }
            }

            function showError(msg) {
                previewContainer.innerHTML = '<div class="preview-error" style="color:#b91c1c;">' + msg + '</div>';
            }

            fileInput.addEventListener('change', function(ev) {
                const file = ev.target.files && ev.target.files[0];
                if (!file) {
                    clearPreview();
                    return;
                }

                // Only show preview for SVG files
                if (!/svg/i.test(file.type) && !file.name.toLowerCase().endsWith('.svg')) {
                    clearPreview();
                    return;
                }

                // limit ~5MB
                if (file.size > 5 * 1024 * 1024) {
                    showError('Image is too large (max 5MB).');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(r) {
                    try {
                        const text = r.target.result;
                        // naive sanitization: remove script tags
                        const cleaned = text.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');
                        previewContainer.innerHTML = cleaned;
                    } catch (err) {
                        console.error(err);
                        showError('Unable to render SVG preview');
                    }
                };
                reader.onerror = function() {
                    showError('Failed to read file');
                };
                reader.readAsText(file);
            });
        }

        // Setup previews with current images
        @if($template->front_image)
            setupPreview('custom_front_image', 'front-preview', '{{ asset('storage/' . $template->front_image) }}');
        @else
            setupPreview('custom_front_image', 'front-preview', null);
        @endif

        @if($template->back_image)
            setupPreview('custom_back_image', 'back-preview', '{{ asset('storage/' . $template->back_image) }}');
        @else
            setupPreview('custom_back_image', 'back-preview', null);
        @endif
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="edit-template-heading">
        <div>
            <h2 id="edit-template-heading">Edit {{ $templateType }} Template</h2>
            <p class="edit-subtitle">Update the details for this {{ strtolower($templateType) }} template</p>
        </div>

        <form action="{{ route('staff.templates.update', $template->id) }}" method="POST" class="edit-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="hidden" name="design" id="design" value="{{ $template->design ?? '{}' }}">

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" value="{{ $template->name }}" required>
                </div>
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding" {{ $template->event_type === 'Wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="Birthday" {{ $template->event_type === 'Birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="Baptism" {{ $template->event_type === 'Baptism' ? 'selected' : '' }}>Baptism</option>
                        <option value="Corporate" {{ $template->event_type === 'Corporate' ? 'selected' : '' }}>Corporate</option>
                        <option value="Other" {{ $template->event_type === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $templateType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $templateType }}" required>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme/Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="{{ $template->theme_style }}">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use...">{{ $template->description }}</textarea>
            </div>

            @if($templateType === 'Invitation')
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="custom_front_image">Front Image</label>
                        <input type="file" id="custom_front_image" name="front_image" accept="image/*">
                        @if($template->front_image)
                            <small class="file-hint">Leave empty to keep current image</small>
                        @endif
                    </div>
                    <div class="create-group flex-1">
                        <label for="custom_back_image">Back Image</label>
                        <input type="file" id="custom_back_image" name="back_image" accept="image/*">
                        @if($template->back_image)
                            <small class="file-hint">Leave empty to keep current image</small>
                        @endif
                    </div>
                </div>

                <div class="create-row">
                    <div class="create-group flex-1">
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">Front Image Preview</div>
                            <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                @if($template->front_image)
                                    <img src="{{ asset('storage/' . $template->front_image) }}" alt="Current front image" style="max-width:100%;max-height:200px;">
                                @else
                                    <span class="muted">No front image</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="create-group flex-1">
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">Back Image Preview</div>
                            <div id="back-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                @if($template->back_image)
                                    <img src="{{ asset('storage/' . $template->back_image) }}" alt="Current back image" style="max-width:100%;max-height:200px;">
                                @else
                                    <span class="muted">No back image</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="custom_front_image">Image</label>
                        <input type="file" id="custom_front_image" name="front_image" accept="image/svg+xml">
                        @if($template->front_image)
                            <small class="file-hint">Leave empty to keep current image</small>
                        @endif
                    </div>
                    <div class="create-group flex-1">
                        <!-- SVG Preview -->
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">Image Preview</div>
                            <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                @if($template->front_image)
                                    <img src="{{ asset('storage/' . $template->front_image) }}" alt="Current image" style="max-width:100%;max-height:200px;">
                                @else
                                    <span class="muted">No image</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="create-actions">
                <a href="{{ route('staff.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Update Template</button>
            </div>
        </form>
    </section>
</main>
@endsection