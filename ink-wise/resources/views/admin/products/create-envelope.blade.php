{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\create-envelope.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Envelope Product')

<link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
<script src="{{ asset('js/admin/create_invite.js') }}"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@section('content')
@php
    $product = $product ?? null;
    $selectedTemplate = $selectedTemplate ?? ($product->template ?? null);
    $envelope = $envelope ?? ($product->envelope ?? null);

    $defaults = [
        'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
        'product_type' => 'Envelope',
    ];
@endphp

{{-- Breadcrumb Navigation --}}
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ route('admin.products.index') }}" class="breadcrumb-link" aria-label="Go to Admin Dashboard" itemprop="item">
                <span itemprop="name"><i class="fas fa-home"></i> Dashboard</span>
            </a>
            <meta itemprop="position" content="1" />
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step1" class="breadcrumb-step active" aria-live="polite" aria-current="page" onclick="Navigation.showPage(0)">
                Templates
            </button>
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step2" class="breadcrumb-step" aria-live="polite" onclick="Navigation.showPage(1)">
                Envelope Details
            </button>
        </li>
    </ol>
</nav>

{{-- Page Title --}}
<h1 id="page-title">Templates</h1>

<form method="POST" action="{{ route('admin.products.store') }}" id="envelope-form" enctype="multipart/form-data">
    @csrf
    @if(isset($product) && $product->id)
        <input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">
    @endif
    <input type="hidden" id="template_id" name="template_id" value="">
    <input type="hidden" name="productType" value="Envelope">

    <div class="invitation-container">
        {{-- Progress Bar --}}
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        {{-- Page 1: Templates --}}
        @include('admin.products.templates')

        {{-- Page 2: Envelope Details --}}
        <div class="page page2" data-page="1" style="display: none;">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page2"></ul>
            </div>

            {{-- Envelope Details --}}
            <div class="form-section">
                <h2><i class="fas fa-envelope"></i> Envelope Details</h2>
        <div class="responsive-grid grid-2-cols">
            <div class="field">
                <label for="invitationName">Envelope Name</label>
                <input type="text" id="invitationName" name="invitationName" placeholder="Envelope Name (e.g. Elegant Pearl Wedding Envelope)" required aria-required="true" aria-describedby="invitationName-error" value="{{ $defaults['name'] }}">
                <span id="invitationName-error" class="error-message" role="alert" aria-live="polite"></span>
                @error('invitationName') <span class="error-message">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="material_type">Product Type</label>
                <input type="hidden" name="material_type" value="envelope">
                <input type="text" class="styled-select" value="ENVELOPE" readonly>
            </div>
        </div>

        <div class="responsive-grid grid-1-cols">
            <div class="field">
                <label for="envelope_material_id">Envelope Material Name</label>
                <select name="envelope_material_id" id="envelope_material_id" required aria-required="true">
                    <option value="">Select Envelope Material</option>
                    @foreach($envelopeMaterials as $material)
                        <option value="{{ $material->material_id }}" {{ (old('envelope_material_id', $envelope->material_id ?? null) == $material->material_id) ? 'selected' : '' }}>
                            {{ strtoupper($material->material_type ?? 'ENVELOPE') }} • {{ $material->material_name }}
                        </option>
                    @endforeach
                </select>
                @error('envelope_material_id') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </div>

                <div class="field">
                    <label for="price_per_unit">Price per Unit</label>
                    <input type="number" id="price_per_unit" name="price_per_unit" placeholder="0.00" step="0.01" min="0" value="{{ old('price_per_unit', $envelope->price_per_unit ?? '') }}" required aria-required="true">
                    @error('price_per_unit') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="average_usage_ml">Average usage (ml)</label>
                    <input type="number" id="average_usage_ml" name="average_usage_ml" step="0.01" min="0" placeholder="0.00" value="{{ old('average_usage_ml', $envelope->average_usage_ml ?? '') }}">
                    <small class="field-help">Used for printing and cost calculations.</small>
                </div>

                {{-- Template Preview Section --}}
                <div class="field">
                    <label>Selected Template Preview</label>
                    <div id="template-preview" class="template-preview-container">
                        <div class="template-preview-placeholder">
                            <i class="fas fa-image"></i>
                            <p>Select a template to see preview</p>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-save" id="submit-btn">
                        <span class="btn-text">Create Envelope</span>
                        <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Function to update template preview images
    function updatePreviewImages() {
        const templateId = document.getElementById('template_id').value;
        const previewContainer = document.getElementById('template-preview');

        if (!templateId) {
            previewContainer.innerHTML = `
                <div class="template-preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Select a template to see preview</p>
                </div>
            `;
            return;
        }

        // Find the selected template button to get its data
        const templateBtn = document.querySelector(`.continue-btn[data-template-id="${templateId}"]`);
        if (!templateBtn) {
            previewContainer.innerHTML = `
                <div class="template-preview-placeholder">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Template data not found</p>
                </div>
            `;
            return;
        }

        const templateName = templateBtn.dataset.templateName;
        const frontImageUrl = templateBtn.dataset.frontUrl || templateBtn.dataset.templatePreview;

        let previewHtml = `<div class="template-preview-content">`;

        if (frontImageUrl) {
            previewHtml += `
                <div class="template-preview-image">
                    <img src="${frontImageUrl}" alt="${templateName}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                </div>
            `;
        } else {
            previewHtml += `
                <div class="template-preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>No preview available</p>
                </div>
            `;
        }

        previewHtml += `
            <div class="template-preview-info">
                <h4>${templateName}</h4>
            </div>
        </div>`;

        previewContainer.innerHTML = previewHtml;
    }

    // Initialize preview on page load
    document.addEventListener('DOMContentLoaded', function() {
        updatePreviewImages();
    });
</script>

<style>
.template-preview-container {
    min-height: 120px;
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.template-preview-placeholder {
    text-align: center;
    color: #64748b;
}

.template-preview-placeholder i {
    font-size: 2rem;
    margin-bottom: 8px;
    display: block;
}

.template-preview-placeholder p {
    margin: 0;
    font-size: 0.9rem;
}

.template-preview-content {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
}

.template-preview-image {
    flex-shrink: 0;
}

.template-preview-info {
    flex: 1;
}

.template-preview-info h4 {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
}
</style>

@endsection
