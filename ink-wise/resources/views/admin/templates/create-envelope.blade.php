@extends('layouts.admin')

@php
    $templateType = 'envelope';
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Envelope';
@endphp

@push('styles')
    @vite('resources/css/admin/template/template.css')
    <style>
        /* Make the create container bigger */
        .create-container{max-width:1400px;margin:0 auto;padding:20px}
        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
    </style>
@endpush

@push('scripts')
    @vite('resources/js/admin/template/template.js')
    <script>
        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) return meta.getAttribute('content') || '';
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            if (!form) return;

            // SVG preview handling
            const frontFile = document.getElementById('front_image');
            const previewContainer = document.getElementById('svg-preview-side') || document.getElementById('svg-preview');
            function clearPreview() {
                if (!previewContainer) return;
                previewContainer.innerHTML = '<span class="muted">No SVG selected</span>';
            }

            function showError(msg) {
                if (!previewContainer) return;
                previewContainer.innerHTML = '<div class="preview-error" style="color:#b91c1c;">' + msg + '</div>';
            }

            if (frontFile && previewContainer) {
                frontFile.addEventListener('change', function(ev) {
                    const file = ev.target.files && ev.target.files[0];
                    if (!file) {
                        clearPreview();
                        return;
                    }

                    // Basic client-side checks
                    if (!/svg/i.test(file.type) && !file.name.toLowerCase().endsWith('.svg')) {
                        showError('Please select an SVG file.');
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
                            // inject SVG markup into preview (wrap in a container)
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

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);

                // For envelope we require name and front_image
                if (!formData.get('name') || !formData.get('front_image')) {
                    alert('Please provide a name and an SVG file.');
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
                        try { message = (await res.json()).message || message; } catch (err) {}
                        throw new Error(message);
                    }
                    return res.json();
                }).then(json => {
                    if (json && json.success) {
                        alert('Envelope template uploaded successfully');
                        window.location = json.redirect || '{{ route('admin.templates.index') }}';
                    }
                }).catch(err => {
                    console.error(err);
                    alert('Upload failed: ' + (err.message || 'Unknown'));
                });
            });
        });
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Envelope Template</h2>
            <p class="create-subtitle">Upload a single SVG to create an envelope template</p>
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
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $selectedProductType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $selectedProductType }}" required>
                    </div>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday">Birthday</option>
                        <option value="Baby Shower">Baby Shower</option>
                        <option value="Corporate">Corporate</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme / Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the design or special instructions (optional)"></textarea>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="front_image">Front SVG *</label>
                    <input type="file" id="front_image" name="front_image" accept="image/svg+xml" required>
                </div>
                <div class="create-group flex-1">
                    <!-- SVG Preview -->
                    <div class="preview-box" aria-live="polite">
                        <div class="preview-label">SVG Preview</div>
                        <div id="svg-preview-side" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                            <span class="muted">No SVG selected</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="create-actions">
                <a href="{{ route('admin.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create Envelope Template</button>
            </div>
        </form>
    </section>
</main>
@endsection
