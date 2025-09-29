{{-- resources/views/admin/products/view.blade.php --}}
@extends('layouts.admin')

@section('title', 'View Product: ' . $product->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/product-view.css') }}">
@endpush

@section('content')
<main class="product-view-wrapper" role="main">
    <header class="product-view-header">
        <div class="product-view-header__text">
            <a href="{{ route('admin.products.index') }}" class="back-link" aria-label="Back to product list">
                <i class="fi fi-rr-angle-left"></i>
                Back to Products
            </a>
            <h1>{{ $product->name }}</h1>
            @php $taglineParts = collect([$product->theme_style, $product->event_type])->filter(); @endphp
            @if($taglineParts->isNotEmpty())
                <p class="product-tagline">{{ $taglineParts->implode(' • ') }}</p>
            @endif
        </div>
        <div class="product-view-actions">
            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-action btn-edit">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Product
            </a>
            @if($product->template_id)
                <a href="{{ route('admin.products.create.invitation', ['template_id' => $product->template_id]) }}" class="btn-action btn-duplicate">
                    <i class="fi fi-rr-copy-alt"></i>
                    Duplicate from Template
                </a>
            @else
                <span class="btn-action btn-duplicate is-disabled" role="button" aria-disabled="true">
                    <i class="fi fi-rr-copy-alt"></i>
                    Duplicate from Template
                </span>
            @endif
        </div>
    </header>

    <section class="product-summary" aria-label="Key product metrics">
        <article class="summary-card">
            <span class="summary-label">Event Type</span>
            <span class="summary-value">{{ $product->event_type ?? '—' }}</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Product Type</span>
            <span class="summary-value">{{ $product->product_type ?? '—' }}</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Unit Price</span>
            <span class="summary-value">{{ $product->unit_price !== null ? '₱' . number_format($product->unit_price, 2) : '—' }}</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Min Order</span>
            <span class="summary-value">{{ $product->min_order_qty ?? '—' }}</span>
        </article>
    </section>

    <div class="product-content-grid">
        <aside class="product-side-panel">
            <div class="product-card media-card">
                <div class="media-card__image">
                    <img src="{{ \App\Support\ImageResolver::url($product->image) }}" alt="{{ $product->name }} preview">
                </div>
                <div class="media-card__meta">
                    <span class="meta-pill">Stock: {{ $product->stock_availability ?? '—' }}</span>
                    <span class="meta-pill">Lead Time: {{ $product->lead_time ?? '—' }}</span>
                </div>
            </div>

            @if($product->template)
                <div class="product-card template-card">
                    <div class="card-heading">
                        <h2>Template Reference</h2>
                        <a href="{{ route('admin.templates.editor', $product->template->id) }}" class="text-link" aria-label="Open template editor">
                            Open editor
                            <i class="fi fi-rr-arrow-right"></i>
                        </a>
                    </div>
                    <p class="template-name">{{ $product->template->name }}</p>
                    <ul class="meta-list">
                        <li><span>Category</span><strong>{{ $product->template->category ?? '—' }}</strong></li>
                        <li><span>Theme</span><strong>{{ $product->template->theme_style ?? '—' }}</strong></li>
                        <li><span>Updated</span><strong>{{ optional($product->template->updated_at)->format('M d, Y') ?? '—' }}</strong></li>
                    </ul>
                </div>
            @endif

            @if($product->uploads && $product->uploads->count())
                <div class="product-card uploads-card">
                    <div class="card-heading">
                        <h2>Uploaded Assets</h2>
                        <a href="{{ route('admin.products.edit', $product->id) }}#uploads" class="text-link">Manage files</a>
                    </div>
                    <ul class="uploads-list">
                        @foreach($product->uploads as $upload)
                            @php
                                $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('uploads/products/' . $product->id . '/' . $upload->filename);
                                $sizeKb = $upload->size ? round($upload->size / 1024, 1) : null;
                            @endphp
                            <li>
                                <div class="upload-name">
                                    <i class="fi fi-rr-file"></i>
                                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ $upload->original_name }}</a>
                                </div>
                                <span class="upload-meta">{{ strtoupper($upload->mime_type ?? 'file') }} @if($sizeKb) · {{ $sizeKb }} KB @endif</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>

        <section class="product-details">
            <div class="product-card">
                <h2>Basic Information</h2>
                <dl class="info-grid">
                    <div><dt>Name</dt><dd>{{ $product->name }}</dd></div>
                    <div><dt>Theme / Style</dt><dd>{{ $product->theme_style ?? '—' }}</dd></div>
                    <div><dt>Event Type</dt><dd>{{ $product->event_type ?? '—' }}</dd></div>
                    <div><dt>Product Type</dt><dd>{{ $product->product_type ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="product-card">
                <h2>Description</h2>
                <div class="product-description">
                    {!! $product->description ? nl2br(e($product->description)) : '<p>No description provided.</p>' !!}
                </div>
            </div>

            <div class="product-card">
                <h2>Materials</h2>
                @php $materialRecords = $product->materials ?? collect(); @endphp
                @if($materialRecords->count())
                    <ul class="meta-list">
                        @foreach($materialRecords as $material)
                            <li>
                                <span>{{ $material->item ?? 'Material' }}</span>
                                <strong>
                                    {{ collect([$material->type, $material->color, $material->size])->filter()->implode(' • ') }}
                                    @if($material->weight) · {{ $material->weight }} GSM @endif
                                </strong>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <dl class="info-grid">
                        <div><dt>Item</dt><dd>{{ $product->item ?? '—' }}</dd></div>
                        <div><dt>Type</dt><dd>{{ $product->type ?? '—' }}</dd></div>
                        <div><dt>Color</dt><dd>{{ $product->color ?? '—' }}</dd></div>
                        <div><dt>Size</dt><dd>{{ $product->size ?? '—' }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ $product->weight ? $product->weight . ' GSM' : '—' }}</dd></div>
                    </dl>
                @endif
            </div>

            <div class="product-card">
                <h2>Production & Pricing</h2>
                <dl class="info-grid">
                    <div><dt>Minimum Order Quantity</dt><dd>{{ $product->min_order_qty ?? '—' }}</dd></div>
                    <div><dt>Lead Time</dt><dd>{{ $product->lead_time ?? '—' }}</dd></div>
                    <div><dt>Stock Availability</dt><dd>{{ $product->stock_availability ?? '—' }}</dd></div>
                    <div><dt>Unit Price</dt><dd>{{ $product->unit_price !== null ? '₱' . number_format($product->unit_price, 2) : '—' }}</dd></div>
                </dl>
            </div>
        </section>
    </div>
</main>
@endsection