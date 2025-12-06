@php $invitationType = 'Baptism'; @endphp
@extends('customer.Invitations.invitations')

@section('title', 'Baptism Invitations')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --invite-accent: #3b82f6;
            --invite-accent-dark: #2563eb;
            --invite-surface: #ffffff;
            --invite-muted: #6b7280;
            --invite-shadow: 0 24px 48px rgba(59, 130, 246, 0.22);
        }

        .baptism-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .baptism-hero {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: clamp(1.75rem, 5vw, 3.25rem);
            background:
                linear-gradient(135deg, #eff6ff, #dbeafe 55%, #f0f9ff);
            box-shadow: 0 28px 55px rgba(59, 130, 246, 0.22);
            color: #111827;
            isolation: isolate;
        }

        .baptism-hero::before,
        .baptism-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            opacity: 0.55;
            transform: translate3d(0, 0, 0);
        }

        .baptism-hero::before {
            width: clamp(180px, 28vw, 320px);
            height: clamp(180px, 28vw, 320px);
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9), rgba(59, 130, 246, 0));
            top: -12%;
            right: 10%;
        }

        .baptism-hero::after {
            width: clamp(220px, 32vw, 380px);
            height: clamp(220px, 32vw, 380px);
            background: radial-gradient(circle, rgba(147, 197, 253, 0.35), rgba(255, 255, 255, 0));
            bottom: -18%;
            left: 8%;
        }

        .baptism-hero__content {
            position: relative;
            max-width: 680px;
            margin-inline: auto;
            text-align: center;
            z-index: 1;
        }

        .baptism-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.2);
            color: #1d4ed8;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .baptism-hero__title {
            margin-top: 1rem;
            font-size: clamp(2rem, 5vw, 2.8rem);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.1;
        }

        .baptism-hero__title span {
            display: inline-block;
        }

        .baptism-hero__title .highlight-primary {
            color: var(--invite-accent-dark);
        }

        .baptism-hero__title .highlight-secondary {
            color: #0ea5e9;
        }

        .baptism-hero__subtitle {
            margin-top: 0.85rem;
            font-size: clamp(0.95rem, 2vw, 1.1rem);
            color: var(--invite-muted);
        }

        .invitation-gallery {
            position: relative;
        }

        .invitation-gallery::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.08), transparent 35%, transparent 65%, rgba(14, 165, 233, 0.08));
            pointer-events: none;
        }

        .invitation-gallery .layout-container {
            position: relative;
            z-index: 1;
        }

        .invitation-grid {
            display: grid;
            gap: clamp(1.75rem, 3.5vw, 2.5rem);
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .invitation-card {
            position: relative;
            display: flex;
            flex-direction: column;
            background: var(--invite-surface);
            border-radius: 24px;
            padding: 1.25rem;
            box-shadow: 0 18px 40px rgba(59, 130, 246, 0.14);
            border: 1px solid rgba(59, 130, 246, 0.28);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            opacity: 0;
            transform: translateY(28px) scale(0.98);
        }

        .invitation-card.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .invitation-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: var(--invite-shadow);
        }

        .invitation-card__preview {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 3 / 4;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(14, 165, 233, 0.1));
        }

        .invitation-card__image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .invitation-card:hover .invitation-card__image {
            transform: scale(1.04);
        }

        .favorite-toggle {
            position: absolute;
            top: 0.9rem;
            right: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 14px 26px rgba(59, 130, 246, 0.28);
            color: var(--invite-accent);
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .favorite-toggle:hover {
            transform: translateY(-2px) scale(1.03);
            background: var(--invite-accent);
            color: #ffffff;
        }

        .favorite-toggle svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .favorite-toggle.is-active {
            background: var(--invite-accent);
            color: #ffffff;
        }

        .invitation-card__body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .invitation-card__title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            line-height: 1.4;
        }

        .invitation-card__subtitle {
            font-size: 0.875rem;
            color: var(--invite-muted);
        }

        .invitation-card__badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--invite-accent-dark);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .invitation-card__price {
            font-size: 1rem;
            font-weight: 600;
            color: var(--invite-accent-dark);
        }

        .invitation-card__muted {
            font-size: 0.875rem;
            color: var(--invite-muted);
        }

        .invitation-card__rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .invitation-card__rating:hover {
            opacity: 0.8;
        }

        .invitation-card__stars {
            display: flex;
            gap: 0.125rem;
        }

        .invitation-card__star {
            font-size: 0.875rem;
            color: #d1d5db;
        }

        .invitation-card__star.filled {
            color: #fbbf24;
        }

        .invitation-card__rating-text {
            font-size: 0.875rem;
            color: var(--invite-muted);
        }

        .invitation-card__review {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.75rem;
            border-radius: 8px;
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .invitation-card__review-text {
            font-size: 0.875rem;
            color: #374151;
            font-style: italic;
            line-height: 1.4;
        }

        .invitation-card__materials {
            margin-top: auto;
        }

        .invitation-card__materials-text {
            font-size: 0.75rem;
            color: var(--invite-muted);
        }

        .invitation-card__low-stock {
            font-size: 0.75rem;
            color: #dc2626;
            font-weight: 500;
        }

        .invitation-card__actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .invitation-card__action {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background: var(--invite-accent);
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .invitation-card__action:hover {
            transform: translateY(-1px);
            background: var(--invite-accent-dark);
        }

        .invitation-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 1rem;
        }

        .invitation-empty h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .invitation-empty p {
            color: var(--invite-muted);
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 640px) {
            .invitation-card {
                padding: 1rem;
            }

            .invitation-card__title {
                font-size: 1rem;
            }

            .invitation-card__actions {
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            handleSwatches();
        });
    </script>
@endpush

@section('content')
@php
    $baptismTemplates = [
        // Add baptism templates here if needed
    ];
@endphp
<main class="baptism-page">
    <section class="baptism-hero">
        <div class="baptism-hero__content">
            <div class="baptism-hero__eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
                Baptism Invitations
            </div>
            <h1 class="baptism-hero__title">
                <span class="highlight-primary">Elegant</span> 
                <span class="highlight-secondary">Baptism</span> Invitations
            </h1>
            <p class="baptism-hero__subtitle">
                Celebrate this sacred milestone with beautifully crafted invitations that reflect the purity and joy of baptism.
            </p>
        </div>
    </section>

    <section class="invitation-gallery">
        <div class="layout-container">
            <div class="invitation-grid">
                @forelse($products as $product)
                    @php
                        $materials = $product->materials ?? collect();
                        $hasLowStockMaterials = $materials->some(function($material) {
                            return ($material->stock ?? 0) < 10; // Assuming 10 is low stock threshold
                        });
                        $priceValue = $product->base_price ?? $product->unit_price ?? optional($product->template)->base_price ?? optional($product->template)->unit_price;
                        $averageRating = $product->ratings->avg('rating') ?? 0;
                        $ratingCount = $product->ratings->count() ?? 0;
                        $previewUrl = route('product.preview', ['product' => $product->id]);
                    @endphp
                    <article class="invitation-card">
                        <div class="invitation-card__preview">
                            @if($product->product_images && !empty($product->product_images->preview))
                                <img src="{{ \App\Support\ImageResolver::url($product->product_images->preview) }}" 
                                     alt="{{ $product->name }}" 
                                     class="invitation-card__image">
                            @elseif($product->uploads && $product->uploads->isNotEmpty())
                                @php $primaryUpload = $product->uploads->first(); @endphp
                                @if(str_starts_with($primaryUpload->mime_type ?? '', 'image/'))
                                    <img src="{{ asset('storage/uploads/products/' . $product->id . '/' . $primaryUpload->filename) }}" 
                                         alt="{{ $product->name }}" 
                                         class="invitation-card__image">
                                @endif
                            @else
                                <img src="{{ asset('images/placeholder.png') }}" 
                                     alt="{{ $product->name }}" 
                                     class="invitation-card__image">
                            @endif
                            <button type="button" class="favorite-toggle" aria-label="Add to favorites">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="invitation-card__body">
                            <h3 class="invitation-card__title">{{ $product->name }}</h3>
                            <p class="invitation-card__subtitle">Baptism Invitation</p>
                            @if($priceValue)
                                <p class="invitation-card__price">Starting at ₱{{ number_format($priceValue, 2) }}</p>
                            @else
                                <p class="invitation-card__muted">Pricing available on request</p>
                            @endif
                            @if($materials->count() > 0)
                                <div class="invitation-card__materials">
                                    <p class="invitation-card__materials-text">Materials: {{ $materials->pluck('name')->join(', ') }}</p>
                                </div>
                            @endif
                            @if($hasLowStockMaterials)
                                <p class="invitation-card__low-stock">Low stock - limited availability</p>
                            @endif
                            @if($ratingCount > 0)
                                <div class="invitation-card__rating rating-trigger"
                                     data-product-id="{{ $product->id }}"
                                     data-product-name="{{ $product->name }}"
                                     role="button"
                                     tabindex="0"
                                     aria-label="View {{ $ratingCount }} review{{ $ratingCount > 1 ? 's' : '' }} for {{ $product->name }}">
                                    <div class="invitation-card__stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="invitation-card__star {{ $i <= round($averageRating) ? 'filled' : '' }}">★</span>
                                        @endfor
                                    </div>
                                    <span class="invitation-card__rating-text">{{ number_format($averageRating, 1) }} ({{ $ratingCount }})</span>
                                </div>
                                @php $latestReview = $product->ratings->sortByDesc('submitted_at')->first(); @endphp
                                @if($latestReview && $latestReview->review)
                                    <div class="invitation-card__review">
                                        @if($latestReview->photos && count($latestReview->photos) > 0)
                                            <div class="flex flex-wrap gap-1 mr-2">
                                                @foreach(array_slice($latestReview->photos, 0, 3) as $photo)
                                                    @php
                                                        $photoUrl = str_starts_with($photo, 'http') ? $photo : \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
                                                    @endphp
                                                    <img src="{{ $photoUrl }}" alt="Rating photo" class="w-8 h-8 object-cover rounded border border-gray-200">
                                                @endforeach
                                                @if(count($latestReview->photos) > 3)
                                                    <div class="w-8 h-8 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-xs text-gray-500">+{{ count($latestReview->photos) - 3 }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        <p class="invitation-card__review-text">"{{ Str::limit($latestReview->review, 80) }}" - {{ $latestReview->customer->name ?? 'Customer' }}</p>
                                    </div>
                                @endif
                            @else
                                <div class="invitation-card__rating">
                                    <div class="invitation-card__stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="invitation-card__star">★</span>
                                        @endfor
                                    </div>
                                    <span class="invitation-card__rating-text">No reviews yet</span>
                                </div>
                            @endif
                            <div class="invitation-card__actions">
                                <button type="button"
                                        class="invitation-card__action preview-trigger"
                                        data-preview-url="{{ $previewUrl }}">
                                    Quick preview
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="invitation-empty">
                        <h3>No baptism invitations yet</h3>
                        <p>We’re curating new designs for you. Please check back soon or contact us for a bespoke concept.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</main>

<div id="productPreviewOverlay" class="preview-overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="preview-frame-wrapper">
        <div class="preview-frame-header">
            <button type="button" class="preview-close-btn" id="productPreviewClose" aria-label="Close preview">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Close
            </button>
        </div>
        <div class="preview-frame-body">
            <iframe id="productPreviewFrame" title="Product preview"></iframe>
        </div>
    </div>
</div>

<!-- Rating Modal -->
<div id="ratingModal" class="rating-modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="rating-modal">
        <div class="rating-modal-header">
            <h3 class="rating-modal-title" id="ratingModalTitle">Customer Reviews</h3>
            <button type="button" class="rating-modal-close" id="ratingModalClose" aria-label="Close reviews">
                ×
            </button>
        </div>
        <div class="rating-modal-body" id="ratingModalBody">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>
@endsection
