{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Invitation Product')

<link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/product-edit.css') }}">
<script src="{{ asset('js/admin/create_invite.js') }}"></script>
<script src="{{ asset('js/admin/product-edit.js') }}" defer></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@section('content')
@php
    $product = $product ?? null;
    $selectedTemplate = $selectedTemplate ?? ($product->template ?? null);

    $defaults = [
        'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
        'event_type' => old('eventType', $product->event_type ?? $selectedTemplate->event_type ?? ''),
        'product_type' => old('productType', $product->product_type ?? $selectedTemplate->product_type ?? 'Invitation'),
        'theme_style' => old('themeStyle', $product->theme_style ?? $selectedTemplate->theme_style ?? ''),
        'description' => old('description', $product->description ?? $selectedTemplate->description ?? ''),
        'base_price' => old('base_price', $product->base_price ?? $product->unit_price ?? ''),
        'lead_time' => old('lead_time', $product->lead_time ?? ''),
        'date_available' => old('date_available', isset($product) && !empty($product->date_available)
            ? optional(\Illuminate\Support\Carbon::parse($product->date_available))->format('Y-m-d')
            : ''),
    ];
@endphp

<main class="product-edit-container" role="main">
    <h1>Edit Product</h1>

    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('admin.products.store') }}" id="product-edit-form" enctype="multipart/form-data" class="product-edit-form">
        @csrf
        @if(isset($product) && $product->id)
            <input type="hidden" name="product_id" value="{{ $product->id }}">
        @endif

        <div class="form-grid single-container">
            {{-- Removed template selector - direct to the form with pre-filled values --}}

            <div class="field">
                <label for="invitationName">Invitation Name *</label>
                <input type="text" id="invitationName" name="invitationName" required value="{{ $defaults['name'] }}">
            </div>

            <div class="field">
                <label for="eventType">Event Type *</label>
                <select id="eventType" name="eventType" required>
                    <option value="">Select event type</option>
                    @foreach(['Wedding','Birthday','Baptism','Corporate'] as $type)
                        <option value="{{ $type }}" {{ $defaults['event_type'] == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="productType">Product Type *</label>
                <select id="productType" name="productType" required>
                    <option value="Invitation" {{ $defaults['product_type'] == 'Invitation' ? 'selected' : '' }}>Invitation</option>
                    <option value="Giveaway" {{ $defaults['product_type'] == 'Giveaway' ? 'selected' : '' }}>Giveaway</option>
                </select>
            </div>

            <div class="field">
                <label for="themeStyle">Theme / Style *</label>
                <input type="text" id="themeStyle" name="themeStyle" required value="{{ $defaults['theme_style'] }}">
            </div>

            <div class="field">
                <label for="basePrice">Base Price</label>
                <input type="number" step="0.01" id="basePrice" name="base_price" value="{{ $defaults['base_price'] }}">
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4">{{ $defaults['description'] }}</textarea>
            </div>

            <div class="field">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <div class="image-preview" aria-live="polite">
                    @if(!empty($product->image))
                        <img src="{{ \App\Support\ImageResolver::url($product->image) }}" alt="Current image">
                    @endif
                </div>
                @if(!empty($product->image))
                    <div class="existing-file">Current: <a href="{{ \App\Support\ImageResolver::url($product->image) }}" target="_blank">View</a></div>
                @endif
            </div>

            {{-- Uploads: additional files stored in product_uploads table --}}
            <div class="field">
                <label for="productUploadFile">Upload Additional File (jpg, png, gif, pdf)</label>
                <input type="file" id="productUploadFile" name="productUploadFile" accept="image/*,.pdf">
                <div class="upload-actions mt-2">
                    @if(isset($product) && $product->id)
                        <button type="button" id="uploadBtn" class="btn-save">Upload</button>
                        <span id="uploadStatus" style="margin-left:8px;color:#333"></span>
                    @else
                        <button type="button" id="uploadBtn" class="btn-save" disabled>Upload (save product first)</button>
                    @endif
                </div>

                <div id="uploads-list" class="mt-3">
                    @if(isset($product) && $product->uploads && $product->uploads->count())
                        <div><strong>Existing uploads:</strong></div>
                        <ul>
                            @foreach($product->uploads as $up)
                                <li>
                                    <a href="{{ asset('storage/uploads/products/' . $product->id . '/' . $up->filename) }}" target="_blank">{{ $up->original_name ?? $up->filename }}</a>
                                    <small class="text-muted">({{ number_format($up->size/1024,2) }} KB)</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="field actions">
                <button type="submit" class="btn-save">Save Product</button>
                <a href="{{ route('admin.products.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </div>
    </form>
</main>

@endsection
                            
