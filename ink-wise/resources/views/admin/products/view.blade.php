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
        </div>
    </header>

    @php
        $basePriceSummaryValue = '—';
        $basePrice = $product->base_price
            ?? $product->unit_price
            ?? ($product->product_type === 'Envelope' ? optional($product->envelope)->price_per_unit : null);
        if (!is_null($basePrice)) {
            $basePriceSummaryValue = '₱' . number_format($basePrice, 2);
        }

        $dateAvailableRaw = $product->getAttribute('date_available')
            ?? $product->getAttribute('date_Available')
            ?? $product->created_at;
        $dateAvailableDisplay = '—';
        if (!empty($dateAvailableRaw)) {
            try {
                $dateAvailableDisplay = \Illuminate\Support\Carbon::parse($dateAvailableRaw)->format('M d, Y');
            } catch (\Throwable $e) {
                $dateAvailableDisplay = is_string($dateAvailableRaw) ? $dateAvailableRaw : '—';
            }
        }

    @endphp

    <section class="product-summary" aria-label="Key product metrics">
        <article class="summary-card">
            <span class="summary-label">Product Type</span>
            <span class="summary-value">{{ $product->product_type ?? '—' }}</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Base Price</span>
            <span class="summary-value">{{ $basePriceSummaryValue }}</span>
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
                            $tPreview = $templateRef->preview ?? $templateRef->image ?? null;

                            if ($tFront) {
                                $displayImages['front'] = [
                                    'url' => \App\Support\ImageResolver::url($tFront),
                                    'alt' => $product->name . ' template front'
                                ];
                            }
                            if ($tBack) {
                                $displayImages['back'] = [
                                    'url' => \App\Support\ImageResolver::url($tBack),
                                    'alt' => $product->name . ' template back'
                                ];
                            }
                            if (empty($displayImages) && $tPreview) {
                                $displayImages['preview'] = [
                                    'url' => \App\Support\ImageResolver::url($tPreview),
                                    'alt' => $product->name . ' template preview'
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
                        @elseif($product->product_type === 'Envelope' && $product->envelope && $product->envelope->material)
                            {{ $product->envelope->material->material_name }}
                        @else
                            {{ $product->name }}
                        @endif
                    </dd></div>
                    <div><dt>Event Type</dt><dd>{{ $product->event_type ?? '—' }}</dd></div>
                    <div><dt>Product Type</dt><dd>{{ $product->product_type ?? '—' }}</dd></div>
                    @if($product->product_type === 'Invitation')
                    <div><dt>Theme / Style</dt><dd>{{ $product->theme_style ?? '—' }}</dd></div>
                    @php
                        $sizeDisplay = '—';
                        // Prefer template explicit inch columns
                        if ($product->template) {
                            $t = $product->template;
                            if (!empty($t->width_inch) || !empty($t->height_inch)) {
                                $fmt = function ($v) {
                                    if ($v === null || $v === '') return null;
                                    $f = floatval($v);
                                    if (floor($f) == $f) return (string) intval($f);
                                    return rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
                                };
                                $w = $fmt($t->width_inch);
                                $h = $fmt($t->height_inch);
                                if ($w !== null && $h !== null) {
                                    $sizeDisplay = $w . 'x' . $h . ' in';
                                } elseif ($w !== null) {
                                    $sizeDisplay = $w . 'x in';
                                } elseif ($h !== null) {
                                    $sizeDisplay = 'x' . $h . ' in';
                                }
                            } elseif (!empty($product->size)) {
                                $sizeDisplay = $product->size;
                            } elseif (!empty($product->invitation_size)) {
                                $sizeDisplay = $product->invitation_size;
                            } elseif (!empty($t->size)) {
                                $sizeDisplay = $t->size;
                            }
                        } else {
                            if (!empty($product->size)) $sizeDisplay = $product->size;
                            elseif (!empty($product->invitation_size)) $sizeDisplay = $product->invitation_size;
                        }
                    @endphp
                    <div><dt>Size</dt><dd>{{ $sizeDisplay }}</dd></div>
                    @endif
                    <div><dt>Base Price</dt><dd>
                        @if($product->base_price !== null)
                            ₱{{ number_format($product->base_price, 2) }}
                        @elseif($product->unit_price !== null)
                            ₱{{ number_format($product->unit_price, 2) }}
                        @elseif($product->product_type === 'Envelope' && optional($product->envelope)->price_per_unit !== null)
                            ₱{{ number_format($product->envelope->price_per_unit, 2) }}
                        @else
                            —
                        @endif
                    </dd></div>
                    <div><dt>Date Available</dt><dd>{{ $dateAvailableDisplay }}</dd></div>
                </dl>
            </div>

            <div class="product-card">
                <h2>Description</h2>
                <div class="product-description">
                    {!! $product->description ? nl2br(e($product->description)) : '<p>No description provided.</p>' !!}
                </div>
            </div>

            @if($product->product_type === 'Invitation')
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
            @endif

            @if($product->product_type === 'Envelope')
            <div class="product-card">
                <h2>Envelope Material</h2>
                @php $envelope = $product->envelope; @endphp
                @if($envelope && ($envelope->material || $envelope->envelope_material_name))
                    <ul class="meta-list">
                        <li>
                            <span>Material</span>
                            <strong>{{ $envelope->material->material_name ?? $envelope->envelope_material_name ?? '—' }}</strong>
                        </li>
                        <li>
                            <span>Type</span>
                            <strong>{{ strtoupper($envelope->material->material_type ?? $envelope->envelope_material_name ?? 'ENVELOPE') }}</strong>
                        </li>
                    </ul>
                @else
                    <p class="muted">No envelope material defined.</p>
                @endif
            </div>
            @endif

            @if($product->product_type === 'Giveaway')
            <div class="product-card">
                <h2>Giveaway Material</h2>
                @php $materials = $product->materials ?? collect(); @endphp
                @if($materials && $materials->count())
                    <ul class="meta-list">
                        @foreach($materials as $mat)
                            @php
                                $matName = $mat->material->material_name ?? $mat->material_name ?? '—';
                                $matType = strtoupper($mat->material->material_type ?? $mat->material_type ?? 'GIVEAWAY');
                            @endphp
                            <li>
                                <span>{{ $matName }}</span>
                                <strong>{{ $matType }}</strong>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No giveaway material defined.</p>
                @endif
            </div>
            @endif

            {{-- Size --}}
            @if($product->product_type !== 'Envelope' && $product->product_type !== 'Giveaway')
            <div class="product-card">
                <h2>Size</h2>
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
                    <p class="muted">No size defined.</p>
                @endif
            </div>
            @endif

            {{-- Ink Usage --}}
            <div class="product-card">
                <h2>Ink Usage</h2>
                @php $inkUsage = $product->inkUsage ?? $product->colors ?? collect(); @endphp
                @if($inkUsage && $inkUsage->count())
                    <ul class="meta-list">
                        @foreach($inkUsage as $usage)
                            <li>
                                <div><strong>Average Usage:</strong> {{ $usage->average_usage_ml ?? '—' }} ml</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">No ink usage defined.</p>
                @endif
            </div>
    </section>
    </div>
</main>
@endsection
