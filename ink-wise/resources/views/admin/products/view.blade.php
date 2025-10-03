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

    @php
        $basePriceSummaryValue = '—';
        $basePrice = $product->base_price ?? $product->unit_price;
        if (!is_null($basePrice)) {
            $basePriceSummaryValue = '₱' . number_format($basePrice, 2);
        }

        $dateAvailableRaw = $product->getAttribute('date_available') ?? $product->getAttribute('date_Available');
        $dateAvailableDisplay = '—';
        if (!empty($dateAvailableRaw)) {
            try {
                $dateAvailableDisplay = \Illuminate\Support\Carbon::parse($dateAvailableRaw)->format('M d, Y');
            } catch (\Throwable $e) {
                $dateAvailableDisplay = is_string($dateAvailableRaw) ? $dateAvailableRaw : '—';
            }
        }

        $bulkOrdersSummary = collect($product->product_bulk_orders ?? $product->bulk_orders ?? []);
        $bulkOrdersSummaryValue = '—';
        if ($bulkOrdersSummary->isNotEmpty()) {
            $minQty = $bulkOrdersSummary->whereNotNull('min_qty')->min('min_qty');
            $maxQty = $bulkOrdersSummary->whereNotNull('max_qty')->max('max_qty');
            if (!is_null($minQty) && !is_null($maxQty)) {
                $bulkOrdersSummaryValue = number_format($minQty) . ' – ' . number_format($maxQty) . ' pcs';
            } elseif (!is_null($minQty)) {
                $bulkOrdersSummaryValue = 'Min ' . number_format($minQty) . ' pcs';
            } elseif (!is_null($maxQty)) {
                $bulkOrdersSummaryValue = 'Up to ' . number_format($maxQty) . ' pcs';
            } else {
                $tierCount = $bulkOrdersSummary->count();
                $bulkOrdersSummaryValue = $tierCount . ' tier' . ($tierCount === 1 ? '' : 's');
            }
        }
    @endphp

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
            <span class="summary-label">Base Price</span>
            <span class="summary-value">{{ $basePriceSummaryValue }}</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Bulk Orders</span>
            <span class="summary-value">{{ $bulkOrdersSummaryValue }}</span>
        </article>
    </section>

    <div class="product-content-grid">
        <aside class="product-side-panel">
            <div class="product-card media-card">
                <div class="media-card__image">
                    @php
                        $sideFront = '';
                        $sideBack = '';
                        $imgRecord = $product->images ?? $product->product_images ?? null;
                        if ($imgRecord) {
                            $sideFront = \App\Support\ImageResolver::url($imgRecord->front ?? null);
                            $sideBack = \App\Support\ImageResolver::url($imgRecord->back ?? null);
                        }
                        $templateRef = $product->template ?? null;
                        if ((!$sideFront || !$sideBack) && $templateRef) {
                            $tFront = $templateRef->front_image ?? $templateRef->preview_front ?? null;
                            $tBack = $templateRef->back_image ?? $templateRef->preview_back ?? null;
                            if (!$sideFront && $tFront) {
                                $sideFront = preg_match('/^(https?:)?\/\//i', $tFront) || strpos($tFront, '/') === 0
                                    ? $tFront
                                    : \Illuminate\Support\Facades\Storage::url($tFront);
                            }
                            if (!$sideBack && $tBack) {
                                $sideBack = preg_match('/^(https?:)?\/\//i', $tBack) || strpos($tBack, '/') === 0
                                    ? $tBack
                                    : \Illuminate\Support\Facades\Storage::url($tBack);
                            }
                        }
                    @endphp
                    <div style="display:flex; gap:8px; align-items:center;">
                        @if($sideFront)
                            <img src="{{ $sideFront }}" alt="{{ $product->name }} front preview" style="max-width:48%; max-height:140px; height:auto; object-fit:contain;">
                        @endif
                        @if($sideBack)
                            <img src="{{ $sideBack }}" alt="{{ $product->name }} back preview" style="max-width:48%; max-height:140px; height:auto; object-fit:contain;">
                        @endif
                        @if(!$sideFront && !$sideBack)
                            <img src="{{ \App\Support\ImageResolver::url($product->image) }}" alt="{{ $product->name }} preview" style="max-width:100%; max-height:140px; height:auto; object-fit:contain;">
                        @endif
                    </div>
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
                        <li><span>Event Type</span><strong>{{ $product->template->event_type ?? '—' }}</strong></li>
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
                    <div><dt>Base Price</dt><dd>{{ $product->base_price !== null ? '₱' . number_format($product->base_price, 2) : ($product->unit_price !== null ? '₱' . number_format($product->unit_price, 2) : '—') }}</dd></div>
                    <div><dt>Date Available</dt><dd>{{ $dateAvailableDisplay }}</dd></div>
                </dl>
            </div>

            <div class="product-card">
                <h2>Description</h2>
                <div class="product-description">
                    {!! $product->description ? nl2br(e($product->description)) : '<p>No description provided.</p>' !!}
                </div>
            </div>


            

            {{-- Paper Stocks --}}
            <div class="product-card">
                <h2>Paper Stocks</h2>
                @php $paperStocks = $product->paper_stocks ?? $product->paperStocks ?? collect(); @endphp
                @if($paperStocks && $paperStocks->count())
                    <ul class="meta-list">
                        @foreach($paperStocks as $ps)
                            <li>
                                <span>{{ $ps->name ?? 'Paper Stock' }}</span>
                                <strong>
                                    {{ isset($ps->price) ? '₱' . number_format($ps->price, 2) : '—' }}
                                    @if(!empty($ps->image_path) || !empty($ps->image))
                                        <img src="{{ \App\Support\ImageResolver::url($ps->image_path ?? $ps->image) }}" alt="{{ $ps->name ?? 'paper' }}" style="width:48px; height:48px; object-fit:contain; margin-left:8px; vertical-align:middle; border:1px solid #eee; background:#fff; padding:4px;">
                                    @endif
                                </strong>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No paper stocks defined.</p>
                @endif
            </div>

            {{-- Addons --}}
            <div class="product-card">
                <h2>Addons</h2>
                @php $addons = $product->addons ?? $product->product_addons ?? $product->addOns ?? collect(); @endphp
                @if($addons && $addons->count())
                    <ul class="meta-list">
                        @foreach($addons as $ad)
                                <li style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        @if(!empty($ad->image_path) || !empty($ad->image))
                                            <img src="{{ \App\Support\ImageResolver::url($ad->image_path ?? $ad->image) }}" alt="{{ $ad->name ?? 'addon' }}" style="width:48px; height:48px; object-fit:contain; border:1px solid #eee; background:#fff; padding:4px;">
                                        @endif
                                        <span>{{ $ad->name ?? $ad->addon_type ?? 'Addon' }}</span>
                                    </div>
                                    <strong>{{ isset($ad->price) ? '₱' . number_format($ad->price, 2) : '—' }}</strong>
                                </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No addons defined.</p>
                @endif
            </div>

            {{-- Colors --}}
            <div class="two-column-row">
            <div class="product-card">
                <h2>Colors</h2>
                @php $colors = $product->colors ?? $product->product_colors ?? collect(); @endphp
                @if($colors && $colors->count())
                    <ul class="meta-list">
                        @foreach($colors as $c)
                            <li style="display:flex; align-items:center; gap:8px;">
                                <span>{{ $c->name ?? 'Color' }}</span>
                                <strong style="display:flex; align-items:center; gap:8px;">
                                    @if(!empty($c->color_code))
                                        <span style="width:20px; height:20px; display:inline-block; background:{{ $c->color_code }}; border:1px solid #ccc;"></span>
                                        <span>{{ $c->color_code }}</span>
                                    @else
                                        —
                                    @endif
                                </strong>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No colors defined.</p>
                @endif
            </div>

            {{-- Bulk Orders --}}
            <div class="product-card">
                <h2>Bulk Orders</h2>
                @php $bulkOrders = $product->bulk_orders ?? $product->product_bulk_orders ?? collect(); @endphp
                @if($bulkOrders && $bulkOrders->count())
                    <ul class="meta-list">
                        @foreach($bulkOrders as $b)
                            <li>
                                <div><strong>Min Qty:</strong> {{ $b->min_qty ?? '—' }}</div>
                                <div><strong>Max Qty:</strong> {{ $b->max_qty ?? '—' }}</div>
                                <div><strong>Price per Unit:</strong> {{ $b->price_per_unit ?? '—' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No bulk order tiers defined.</p>
                @endif
            </div>
            </div>
        </section>
    </div>
</main>
@endsection