@extends('layouts.admin')

@php
    $templateType = request('type', 'invitation');
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Invitation';
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

        document.querySelector('.create-form').addEventListener('submit', function(e) {
            // Use default submit only if JS disabled; otherwise handle via fetch
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Basic validation
            if (!formData.get('name') || !formData.get('front_image') || !formData.get('back_image')) {
                alert('Please provide a name and both front/back images.');
                return;
            }

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
                    let message = 'Upload failed';
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
                    alert('Template uploaded successfully. Redirecting to templates list.');
                    window.location = json.redirect || '{{ route('admin.templates.index') }}';
                } else {
                    alert('Upload succeeded but server response unexpected.');
                }
            }).catch(err => {
                console.error(err);
                alert('Upload failed: ' + (err.message || 'Unknown'));
            });
        });

        // SVG preview handling for front and back
        function setupPreview(fileInputId, previewId) {
            const fileInput = document.getElementById(fileInputId);
            const previewContainer = document.getElementById(previewId);
            if (!fileInput || !previewContainer) return;

            function clearPreview() {
                previewContainer.innerHTML = '<span class="muted">No SVG selected</span>';
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
                    showError('SVG is too large (max 5MB).');
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

        setupPreview('custom_front_image', 'front-preview');
        setupPreview('custom_back_image', 'back-preview');
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New {{ ucfirst($templateType) }} Template</h2>
            <p class="create-subtitle">Fill in the details to craft a new {{ $templateType }} template</p>
        </div>

        <form action="{{ route('admin.templates.store') }}" method="POST" class="create-form" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="design" id="design" value="{}">

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" required>
                </div>
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday">Birthday</option>
                        <option value="Baptism">Baptism</option>
                        <option value="Corporate">Corporate</option>
                    </select>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $selectedProductType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $selectedProductType }}" required>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme/Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use..."></textarea>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="custom_front_image">Front Image *</label>
                    <input type="file" id="custom_front_image" name="front_image" accept="image/*" required>
                </div>
                <div class="create-group flex-1">
                    <label for="custom_back_image">Back Image *</label>
                    <input type="file" id="custom_back_image" name="back_image" accept="image/*" required>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <div class="preview-box" aria-live="polite">
                        <div class="preview-label">Front SVG Preview</div>
                        <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                            <span class="muted">No SVG selected</span>
                        </div>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <div class="preview-box" aria-live="polite">
                        <div class="preview-label">Back SVG Preview</div>
                        <div id="back-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                            <span class="muted">No SVG selected</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="create-actions">
                <a href="{{ route('admin.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create &amp; Edit Template</button>
            </div>
        </form>
    </section>
</main>
@endsection

