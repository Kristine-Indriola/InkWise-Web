@extends('layouts.staffapp')

@php
    $templateType = request('type', 'invitation');
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Invitation';

    // Check if editing a preview
    $editPreviewId = request('edit_preview');
    $previewData = null;
    if ($editPreviewId) {
        $previews = session('preview_templates', []);
        foreach ($previews as $preview) {
            if (isset($preview['id']) && $preview['id'] === $editPreviewId) {
                $previewData = $preview;
                break;
            }
        }
    }
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
    <style>
        /* Make the create container bigger */
        .create-container{max-width:1400px;margin:0 auto;padding:20px}
        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
        /* Make giveaway preview smaller */
        .giveaway-preview{max-height:300px;aspect-ratio:4/3}
    </style>
@endpush

@push('scripts')
    <script>
        // Debug form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            const submitBtn = document.querySelector('.btn-submit');

            console.log('Form found:', form);
            console.log('Submit button found:', submitBtn);

            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    console.log('========== SUBMIT BUTTON CLICKED ==========');
                    console.log('Form found:', !!form);
                    console.log('Form action:', form ? form.action : 'No form');
                    console.log('Button type:', this.type);
                    console.log('Button disabled:', this.disabled);
                    console.log('Form method:', form ? form.method : 'N/A');

                    // Check form fields
                    if (form) {
                        const formData = new FormData(form);
                        console.log('Template name:', formData.get('name'));
                        console.log('Product type:', formData.get('product_type'));
                    }

                    // Check for form validation
                    if (form && !form.checkValidity()) {
                        console.log('❌ Form validation failed - showing validation messages');
                        form.reportValidity();
                        e.preventDefault();
                        return false;
                    }

                    console.log('✅ Form validation passed - allowing submission');
                });
            }
        });
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Preview Template Design</h2>
            <p class="create-subtitle">Upload your design files to preview how the template will look before creating it</p>
        </div>

    <form action="{{ route('staff.templates.store') }}" method="POST" class="create-form" enctype="multipart/form-data">
            @csrf

            <!-- Template Information -->
            <div class="create-section">
                <h3 class="section-title">Template Information</h3>

                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="name">Template Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter template name" value="{{ $previewData['name'] ?? '' }}" required>
                    </div>
                    <div class="create-group flex-1">
                        <label for="event_type">Event Type</label>
                        <select id="event_type" name="event_type">
                            <option value="">Select event type</option>
                            <option value="Wedding" {{ ($previewData['event_type'] ?? '') === 'Wedding' ? 'selected' : '' }}>Wedding</option>
                            <option value="Birthday" {{ ($previewData['event_type'] ?? '') === 'Birthday' ? 'selected' : '' }}>Birthday</option>
                            <option value="Baptism" {{ ($previewData['event_type'] ?? '') === 'Baptism' ? 'selected' : '' }}>Baptism</option>
                            <option value="Corporate" {{ ($previewData['event_type'] ?? '') === 'Corporate' ? 'selected' : '' }}>Corporate</option>
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
                        <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="{{ $previewData['theme_style'] ?? '' }}">
                    </div>
                </div>

                <div class="create-group">
                    <label for="description">Design Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe the template design, style, and intended use...">{{ $previewData['description'] ?? '' }}</textarea>
                </div>
            </div>

            <div class="create-actions">
                <a href="{{ route('staff.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create Preview</button>
            </div>
        </form>
    </section>
</main>
@endsection
