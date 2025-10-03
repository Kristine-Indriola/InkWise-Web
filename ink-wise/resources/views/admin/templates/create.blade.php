@extends('layouts.admin')

@section('title', 'Create New Template')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Template</h2>
            <p class="create-subtitle">Fill in the details to craft a new invitation template</p>
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
                    <label for="product_type">Product Type</label>
                    <select id="product_type" name="product_type" required>
                        <option value="">Select product type</option>
                        <option value="Invitation">Invitation</option>
                        <option value="Giveaway">Giveaway</option>
                    </select>
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

            <div class="create-actions">
                <a href="{{ route('admin.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create &amp; Edit Template</button>
            </div>
        </form>
    </section>
</main>
@endsection

        @push('scripts')
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
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }).then(async res => {
                    if (!res.ok) {
                        const txt = await res.text();
                        throw new Error(txt || 'Upload failed');
                    }
                    return res.json();
                }).then(json => {
                    if (json && json.success) {
                        alert('Template uploaded successfully. Redirecting to templates list.');
                        window.location = '{{ route('admin.templates.index') }}';
                    } else {
                        alert('Upload succeeded but server response unexpected.');
                    }
                }).catch(err => {
                    console.error(err);
                    alert('Upload failed: ' + (err.message || 'Unknown'));
                });
            });
        </script>
        @endpush

