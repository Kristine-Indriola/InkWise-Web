{{-- resources/views/admin/products/view.blade.php --}}
@extends('layouts.admin')

@section('title', 'View Product: ' . ($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material ? $product->materials->first()->material->material_name : $product->name))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/product-view.css') }}">
    <style>
        .rating-summary {
            margin-bottom: 1rem;
        }
        .stars-display {
            display: flex;
            gap: 2px;
            margin: 0.5rem 0;
        }
        .stars-display .star {
            font-size: 1.2rem;
            color: #ddd;
        }
        .stars-display .star.filled {
            color: #f59e0b;
        }
        .ratings-list {
            list-style: none;
            padding: 0;
        }
        .rating-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .rating-item:last-child {
            border-bottom: none;
        }
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            gap: 1rem;
        }
        .rating-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }
        .rating-customer {
            font-weight: 600;
            color: #111827;
            font-size: 0.9rem;
        }
        .rating-date {
            font-size: 0.9rem;
            color: #666;
        }
        .rating-review {
            margin: 0.5rem 0;
            font-style: italic;
        }
        .rating-photos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .rating-photo {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .rating-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
@endpush

@section('content')
<main class="product-view-wrapper" role="main">
    <header class="product-view-header">
        <div class="product-view-header__text">
            <a href="{{ route('admin.products.index') }}" class="back-link" aria-label="Back to product list">
                <i class="fi fi-rr-angle-left"></i>
                Back to Products
            </a>
            <h1>
                @if($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material)
                    {{ $product->materials->first()->material->material_name }}
                @else
                    {{ $product->name }}
                @endif
            </h1>
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
                        $displayImages = [];
                        $imgRecord = $product->images ?? $product->product_images ?? null;

                        // First priority: Product images (front/back/preview)
                        if ($imgRecord) {
                            if (!empty($imgRecord->front)) {
                                $displayImages['front'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->front),
                                    'alt' => $product->name . ' front preview'
                                ];
                            }
                            if (!empty($imgRecord->back)) {
                                $displayImages['back'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->back),
                                    'alt' => $product->name . ' back preview'
                                ];
                            }
                            if (!empty($imgRecord->preview) && empty($displayImages)) {
                                $displayImages['preview'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->preview),
                                    'alt' => $product->name . ' preview'
                                ];
                            }
                        }

                        // Second priority: Template images (for envelope products)
                        $templateRef = $product->template ?? null;
                        if (empty($displayImages) && $templateRef) {
                            $tFront = $templateRef->front_image ?? $templateRef->preview_front ?? null;
                            $tBack = $templateRef->back_image ?? $templateRef->preview_back ?? null;

                            if ($tFront) {
                                $displayImages['front'] = [
                                    'url' => preg_match('/^(https?:)?\/\//i', $tFront) || strpos($tFront, '/') === 0
                                        ? $tFront
                                        : \Illuminate\Support\Facades\Storage::url($tFront),
                                    'alt' => $product->name . ' template front'
                                ];
                            }
                            if ($tBack) {
                                $displayImages['back'] = [
                                    'url' => preg_match('/^(https?:)?\/\//i', $tBack) || strpos($tBack, '/') === 0
                                        ? $tBack
                                        : \Illuminate\Support\Facades\Storage::url($tBack),
                                    'alt' => $product->name . ' template back'
                                ];
                            }
                        }

                        // Fallback: Main product image
                        if (empty($displayImages) && !empty($product->image)) {
                            $displayImages['main'] = [
                                'url' => \App\Support\ImageResolver::url($product->image),
                                'alt' => $product->name . ' preview'
                            ];
                        }

                        $imageCount = count($displayImages);
                    @endphp

                    @if($imageCount > 0)
                        <div style="display: grid; grid-template-columns: repeat({{ $imageCount }}, 1fr); gap: 8px; align-items: center;">
                            @foreach($displayImages as $key => $image)
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" style="width: 100%; max-height: 140px; height: auto; object-fit: contain; {{ $imageCount === 1 ? 'max-width: 100%;' : 'max-width: 48%;' }}">
                            @endforeach
                        </div>
                    @else
                        <div style="width: 100%; height: 140px; background: #f3f4f6; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            <span>No images available</span>
                        </div>
                    @endif
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

            {{-- Ratings --}}
            <div class="product-card">
                <h2>Ratings</h2>
                @php
                    $ratings = $product->ratings ?? collect();
                    $averageRating = $ratings->avg('rating');
                @endphp
                @if($ratings->isNotEmpty())
                    <div class="rating-summary">
                        <p><strong>Average Rating:</strong> {{ number_format($averageRating, 1) }} / 5 ({{ $ratings->count() }} review{{ $ratings->count() > 1 ? 's' : '' }})</p>
                        <div class="stars-display">
                            @foreach(range(1, 5) as $i)
                                <span class="star {{ $i <= round($averageRating) ? 'filled' : '' }}">&#9733;</span>
                            @endforeach
                        </div>
                    </div>
                    <ul class="ratings-list">
                        @foreach($ratings as $rating)
                            <li class="rating-item">
                                <div class="rating-header">
                                    <div class="rating-info">
                                        <strong class="rating-customer">{{ $rating->customer->name ?? 'Customer' }}</strong>
                                        <div class="stars-display">
                                            @foreach(range(1, 5) as $i)
                                                <span class="star {{ $i <= $rating->rating ? 'filled' : '' }}">&#9733;</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <span class="rating-date">{{ optional($rating->submitted_at)->format('M d, Y') }}</span>
                                </div>
                                @if($rating->review)
                                    <p class="rating-review">{{ $rating->review }}</p>
                                @endif
                                @if($rating->photos && count($rating->photos))
                                    <div class="rating-photos">
                                        @foreach($rating->photos as $photo)
                                            @php
                                                $photoUrl = \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
                                                    ? $photo
                                                    : \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
                                            @endphp
                                            <img src="{{ $photoUrl }}" alt="Rating photo" class="rating-photo" onclick="window.open('{{ $photoUrl }}', '_blank')">
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No ratings yet.</p>
                @endif
            </div>

            @if($product->uploads && $product->uploads->count() && !in_array($product->product_type, ['Envelope', 'Giveaway']))
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
                <h2>{{ $product->product_type ?? 'Product' }} Information</h2>
                <dl class="info-grid">
                    <div><dt>{{ $product->product_type ?? 'Product' }} Name</dt><dd>
                        @if($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material)
                            {{ $product->materials->first()->material->material_name }}
                        @else
                            {{ $product->name }}
                        @endif
                    </dd></div>
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
            @if($product->product_type !== 'Envelope' && $product->product_type !== 'Giveaway')
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
            @endif

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
