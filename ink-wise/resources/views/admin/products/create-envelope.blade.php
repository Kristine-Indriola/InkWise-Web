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
        <li class="breadcrumb-item" aria-current="page">
            <span>Create Envelope</span>
        </li>
    </ol>
</nav>

{{-- Page Title --}}
<h1>Create Envelope Product</h1>

<form method="POST" action="{{ route('admin.products.store') }}" id="envelope-form" enctype="multipart/form-data">
    @csrf
    @if(isset($product) && $product->id)
        <input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">
    @endif
    <input type="hidden" name="productType" value="Envelope">

    <div class="invitation-container">
        {{-- Envelope Details --}}
        <h2><i class="fas fa-envelope"></i> Envelope Details</h2>
        <div class="responsive-grid grid-2-cols">
            <div class="field">
                <label for="invitationName">Envelope Name *</label>
                <input type="text" id="invitationName" name="invitationName" placeholder="Envelope Name * (e.g. Elegant Pearl Wedding Envelope)" required aria-required="true" aria-describedby="invitationName-error" value="{{ $defaults['name'] }}">
                <span id="invitationName-error" class="error-message" role="alert" aria-live="polite"></span>
                @error('invitationName') <span class="error-message">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="material_type">Material *</label>
                <select name="material_type" id="material_type" class="material-select" required aria-required="true">
                    <option value="">Select Material Type</option>
                    @foreach($materialTypes as $type)
                        <option value="{{ $type }}" {{ (old('material_type', $envelope ? \App\Models\Material::find($envelope->material_id)?->material_type : null) == $type) ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
                @error('material_type') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="responsive-grid grid-3-cols">
            <div class="field">
                <label for="envelope_material_id">Envelope Material Name *</label>
                <select name="envelope_material_id" id="envelope_material_id" required aria-required="true">
                    <option value="">Select Envelope Material</option>
                    @foreach($envelopeMaterials as $material)
                        <option value="{{ $material->material_id }}" {{ (old('envelope_material_id', $envelope->material_id ?? null) == $material->material_id) ? 'selected' : '' }}>
                            {{ $material->material_name }}
                        </option>
                    @endforeach
                </select>
                @error('envelope_material_id') <span class="error-message">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="max_qty">Max Qty *</label>
                <input type="number" id="max_qty" name="max_qty" placeholder="Maximum Quantity" min="1" value="{{ old('max_qty', $envelope->max_qty ?? '') }}" required aria-required="true">
                @error('max_qty') <span class="error-message">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="max_quantity">Max Quantity *</label>
                <input type="number" id="max_quantity" name="max_quantity" placeholder="Maximum Quantity" min="1" value="{{ old('max_quantity', $envelope->max_quantity ?? '') }}" required aria-required="true">
                @error('max_quantity') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="responsive-grid grid-2-cols">
            <div class="field">
                <label for="price_per_unit">Price per Unit *</label>
                <input type="number" id="price_per_unit" name="price_per_unit" placeholder="0.00" step="0.01" min="0" value="{{ old('price_per_unit', $envelope->price_per_unit ?? '') }}" required aria-required="true">
                @error('price_per_unit') <span class="error-message">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="envelope_image">Envelope Image</label>
                <input type="file" id="envelope_image" name="envelope_image" accept="image/*" class="file-input">
                @if($envelope && $envelope->envelope_image)
                    <div class="image-preview">
                        <img src="{{ asset('storage/' . $envelope->envelope_image) }}" alt="Current Envelope Image" style="max-width: 100px; max-height: 100px;">
                    </div>
                @endif
                @error('envelope_image') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-save" id="submit-btn">
                <span class="btn-text">Create Envelope</span>
                <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
            </button>
        </div>
    </div>
</form>

<script>
    // Simple form validation can be added here if needed
</script>

@endsection
