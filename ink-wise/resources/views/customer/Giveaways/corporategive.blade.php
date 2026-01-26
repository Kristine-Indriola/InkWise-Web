@php
    $invitationType = 'Corporate';
    $products = $products ?? collect();
@endphp
@extends('customer.Invitations.invitations')

@section('title', 'Corporate Giveaways')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --give-accent: #f9cf9d;
            --give-accent-dark: #f2a65a;
            --give-surface: #ffffff;
            --give-muted: #4b5563;
            --give-shadow: 0 24px 48px rgba(242, 166, 90, 0.2);
        }

        .giveaway-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .giveaway-hero {
            position: relative;
            padding: clamp(1.75rem, 5vw, 3.25rem);
            color: #0f172a;
            text-align: center;
        }

        .giveaway-hero__content {
            position: relative;
            max-width: 680px;
            margin-inline: auto;
            text-align: center;
            z-index: 1;
        }

        .giveaway-hero__title {
            margin-top: 0.5rem;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-family: 'ITC New Baskerville', 'Baskerville', 'Times New Roman', serif;
            font-weight: 700;
            line-height: 1.05;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        .giveaway-hero__subtitle {
            margin-top: 0.75rem;
            font-size: clamp(0.8rem, 2vw, 1.1rem);
            font-family: 'ITC New Baskerville', 'Baskerville', 'Times New Roman', serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #b45309;
        }

        .giveaway-gallery {
            position: relative;
            padding-bottom: 2rem;
        }

        .giveaway-gallery::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top, rgba(242, 166, 90, 0.2), transparent 55%);
            pointer-events: none;
        }

        .giveaway-gallery::after {
            content: "";
            position: absolute;
            inset: auto 8% -10% 8%;
            height: 220px;
            background: rgba(255, 255, 255, 0.5);
            filter: blur(80px);
            pointer-events: none;
        }

        .giveaway-gallery .layout-container {
            position: relative;
            z-index: 1;
        }

        .giveaway-grid {
            display: grid;
            gap: clamp(1.25rem, 3vw, 2rem);
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            justify-items: center;
        }

        .giveaway-card {
            position: relative;
            width: min(220px, 100%);
            aspect-ratio: 2 / 3.4;
            border-radius: 26px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(249, 207, 157, 0.35), rgba(255, 255, 255, 0.7));
            box-shadow: 0 18px 40px rgba(242, 166, 90, 0.18);
            transition: transform 0.35s ease, box-shadow 0.35s ease, filter 0.35s ease;
            isolation: isolate;
            opacity: 0;
            transform: translateY(24px) scale(0.98);
        }

        .giveaway-card.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .giveaway-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(0, 0, 0, 0.25));
            opacity: 0;
            transition: opacity 0.35s ease;
            pointer-events: none;
        }

        .giveaway-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 32px 60px rgba(242, 166, 90, 0.35);
            filter: drop-shadow(0 12px 25px rgba(120, 53, 15, 0.2));
        }

        .giveaway-card:hover::after {
            opacity: 1;
        }

        .giveaway-card__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scale(1.05);
            transition: transform 0.35s ease, filter 0.35s ease;
            cursor: pointer;
            border-radius: inherit;
        }

        .giveaway-card:hover .giveaway-card__image {
            transform: scale(1.08);
            filter: saturate(1.05) contrast(1.05);
        }

        .giveaway-card__info {
            position: absolute;
            inset: auto 0 0;
            padding: 1rem 1.1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, 0.9));
            color: #fff;
            z-index: 1;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform: translateY(40%);
            opacity: 0;
            cursor: pointer;
        }

        .giveaway-card:hover .giveaway-card__info,
        .giveaway-card:focus-within .giveaway-card__info {
            transform: translateY(0);
            opacity: 1;
        }

        .giveaway-card__info::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.5));
            border-radius: 0 0 26px 26px;
            z-index: -1;
        }

        .giveaway-card__name {
            font-family: 'The Seasons', 'Playfair Display', serif;
            font-size: 1rem;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .giveaway-card__price-tag {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .giveaway-card__ratings {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.88);
            letter-spacing: 0.03em;
        }

        .giveaway-card__rating-stars {
            display: flex;
            gap: 0.15rem;
        }

        .giveaway-card__rating-star {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .giveaway-card__rating-star.filled {
            color: #fcd34d;
            text-shadow: 0 0 6px rgba(252, 211, 77, 0.6);
        }

        .giveaway-card__rating-detail {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .giveaway-card__rating-empty {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .favorite-toggle {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.3rem;
            height: 2.3rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            color: #f2a65a;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 12px 26px rgba(242, 166, 90, 0.25);
            z-index: 2;
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .favorite-toggle svg {
            width: 1.1rem;
            height: 1.1rem;
            fill: currentColor;
            stroke: currentColor;
        }

        .favorite-toggle:hover,
        .favorite-toggle:focus-visible {
            transform: translateY(-3px) scale(1.04);
            box-shadow: 0 18px 32px rgba(242, 166, 90, 0.35);
        }

        .favorite-toggle.is-active {
            background: linear-gradient(135deg, #fcd29f, #f2a65a);
            color: #ffffff;
            box-shadow: 0 20px 36px rgba(242, 166, 90, 0.45);
        }

        .giveaway-empty {
            text-align: center;
            border-radius: 24px;
            padding: clamp(2.5rem, 6vw, 4rem);
            background: rgba(249, 207, 157, 0.2);
            border: 1px dashed rgba(242, 166, 90, 0.4);
            color: #b45309;
        }

        .giveaway-empty p {
            margin-top: 0.75rem;
            color: var(--give-muted);
        }

        @media (max-width: 640px) {
            .giveaway-hero {
                border-radius: 24px;
            }

            .giveaway-card {
                width: min(180px, 100%);
                border-radius: 20px;
            }

            .favorite-toggle {
                width: 2rem;
                height: 2rem;
            }

            .giveaway-card__info {
                transform: none;
                opacity: 1;
            }
        }

        /* Rating Modal Styles */
        .rating-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .rating-modal-overlay.is-visible {
            opacity: 1;
            visibility: visible;
        }

        .rating-modal {
            background: #ffffff;
            border-radius: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 28px 55px rgba(79, 70, 229, 0.25);
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
        }

        .rating-modal-overlay.is-visible .rating-modal {
            transform: scale(1) translateY(0);
        }

        .rating-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 24px 0;
        }

        .rating-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .rating-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .rating-modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .rating-modal-body {
            padding: 24px;
        }

        .rating-modal-summary {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .rating-modal-stars {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 8px;
        }

        .rating-modal-star {
            font-size: 1.5rem;
            color: #d1d5db;
        }

        .rating-modal-star.filled {
            color: #fcd34d;
        }

        .rating-modal-average {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .rating-modal-count {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        .rating-modal-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rating-modal-item {
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .rating-modal-item:last-child {
            border-bottom: none;
        }

        .rating-modal-item-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .rating-modal-item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .rating-modal-item-customer {
            font-weight: 600;
            color: #111827;
        }

        .rating-modal-item-stars {
            display: flex;
            gap: 2px;
        }

        .rating-modal-item-star {
            font-size: 0.875rem;
            color: #d1d5db;
        }

        .rating-modal-item-star.filled {
            color: #fcd34d;
        }

        .rating-modal-item-date {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .rating-modal-item-review {
            color: #374151;
            margin: 8px 0 0 0;
            line-height: 1.5;
        }

        .rating-modal-item-photos {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .rating-modal-item-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .rating-modal-item-photo:hover {
            transform: scale(1.05);
        }

        .rating-modal-empty {
            text-align: center;
            color: #6b7280;
            padding: 40px 20px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const FAVORITES_KEY = 'inkwise:corporate:giveaways:favorites';
            let favorites;

            try {
                const stored = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
                favorites = new Set(stored);
            } catch (error) {
                console.warn('Unable to parse corporate giveaway favorites from storage.', error);
                favorites = new Set();
            }

            const updateStorage = () => {
                if (!window.localStorage) return;
                window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(favorites)));
            };

            const setFavoriteState = (button, active) => {
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                const baseLabel = button.getAttribute('data-label') || 'Save giveaway';
                button.setAttribute('title', active ? 'Remove from saved giveaways' : baseLabel);
            };

            document.querySelectorAll('.favorite-toggle').forEach((button) => {
                const productId = button.getAttribute('data-product-id');
                if (!productId) return;

                button.dataset.label = button.dataset.label || button.getAttribute('aria-label') || 'Save giveaway';

                if (favorites.has(productId)) {
                    setFavoriteState(button, true);
                }

                button.addEventListener('click', () => {
                    const isActive = favorites.has(productId);
                    if (isActive) {
                        favorites.delete(productId);
                    } else {
                        favorites.add(productId);
                    }
                    setFavoriteState(button, !isActive);
                    updateStorage();
                });
            });

            const animateCards = () => {
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries, obs) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                obs.unobserve(entry.target);
                            }
                        });
                    }, {
                        rootMargin: '0px 0px -5%',
                        threshold: 0.15,
                    });

                    document.querySelectorAll('.giveaway-card').forEach((card) => observer.observe(card));
                } else {
                    document.querySelectorAll('.giveaway-card').forEach((card) => card.classList.add('is-visible'));
                }
            };

            animateCards();

            // Rating Modal Functionality
            const ratingModal = document.getElementById('ratingModal');
            const ratingModalClose = document.getElementById('ratingModalClose');
            const ratingModalTitle = document.getElementById('ratingModalTitle');
            const ratingModalBody = document.getElementById('ratingModalBody');

            // Store ratings data globally (populated from PHP)
            window.productRatings = @json($ratingsData);

            function showRatingModal(productId) {
                const productData = window.productRatings[productId];
                if (!productData) return;

                ratingModalTitle.textContent = `Reviews for ${productData.name}`;

                let modalContent = '';

                if (productData.rating_count > 0) {
                    // Summary
                    modalContent += `
                        <div class="rating-modal-summary">
                            <div class="rating-modal-stars">
                                ${Array.from({length: 5}, (_, i) =>
                                    `<span class="rating-modal-star ${i < Math.round(productData.average_rating) ? 'filled' : ''}">★</span>`
                                ).join('')}
                            </div>
                            <p class="rating-modal-average">${productData.average_rating.toFixed(1)} out of 5</p>
                            <p class="rating-modal-count">Based on ${productData.rating_count} review${productData.rating_count > 1 ? 's' : ''}</p>
                        </div>
                    `;

                    // Individual reviews
                    modalContent += '<ul class="rating-modal-list">';
                    productData.ratings.forEach(rating => {
                        modalContent += `
                            <li class="rating-modal-item">
                                <div class="rating-modal-item-header">
                                    <div class="rating-modal-item-info">
                                        <strong class="rating-modal-item-customer">${rating.customer_name}</strong>
                                        <div class="rating-modal-item-stars">
                                            ${Array.from({length: 5}, (_, i) =>
                                                `<span class="rating-modal-item-star ${i < rating.rating ? 'filled' : ''}">★</span>`
                                            ).join('')}
                                        </div>
                                    </div>
                                    <span class="rating-modal-item-date">${rating.submitted_at || 'Recent'}</span>
                                </div>
                                ${rating.review ? `<p class="rating-modal-item-review">${rating.review}</p>` : ''}
                                ${rating.photos && rating.photos.length > 0 ? `
                                    <div class="rating-modal-item-photos">
                                        ${rating.photos.map(photo => {
                                            const photoUrl = photo.startsWith('http') ? photo : '/storage/' + photo;
                                            return `<img src="${photoUrl}" alt="Rating photo" class="rating-modal-item-photo" onclick="window.open('${photoUrl}', '_blank')">`;
                                        }).join('')}
                                    </div>
                                ` : ''}
                            </li>
                        `;
                    });
                    modalContent += '</ul>';
                } else {
                    modalContent = '<div class="rating-modal-empty">No reviews yet for this product.</div>';
                }

                ratingModalBody.innerHTML = modalContent;
                ratingModal.classList.add('is-visible');
                ratingModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function hideRatingModal() {
                ratingModal.classList.remove('is-visible');
                ratingModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            // Event listeners
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('rating-trigger') || e.target.closest('.rating-trigger')) {
                    const trigger = e.target.classList.contains('rating-trigger') ? e.target : e.target.closest('.rating-trigger');
                    const productId = trigger.dataset.productId;
                    if (productId) {
                        showRatingModal(productId);
                    }
                }
            });

            ratingModalClose.addEventListener('click', hideRatingModal);
            ratingModal.addEventListener('click', (e) => {
                if (e.target === ratingModal) {
                    hideRatingModal();
                }
            });

            // Keyboard support
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && ratingModal.classList.contains('is-visible')) {
                    hideRatingModal();
                }
            });
        });
    </script>
@endpush

@section('content')
@php
    $hasProducts = $products->count();
@endphp
<main class="giveaway-page">
    <section class="giveaway-hero">
        <div class="giveaway-hero__content">
            <h1 class="giveaway-hero__title">CORPORATE GIVEAWAYS</h1>
            <p class="giveaway-hero__subtitle">INSPIRE PROFESSIONAL THANK-YOUS</p>
        </div>
    </section>

    <section class="giveaway-gallery">
        <div class="layout-container">
            @if(!$hasProducts)
                <div class="giveaway-empty">
                    <h2>No giveaways available yet</h2>
                    <p>We’re curating polished favors. Check back soon or message us for a bespoke concept.</p>
                </div>
            @else
                <div class="giveaway-grid" role="list">
                    @foreach($products as $product)
                        @php
                            $uploads = $product->uploads ?? collect();
                            if (!($uploads instanceof \Illuminate\Support\Collection)) {
                                $uploads = collect($uploads);
                            }

                            $firstUpload = $uploads->first();
                            $images = $product->images ?? $product->product_images ?? null;
                            $templateRef = $product->template ?? null;

                            $previewSrc = null;
                            if ($firstUpload && str_starts_with($firstUpload->mime_type ?? '', 'image/')) {
                                $previewSrc = \Illuminate\Support\Facades\Storage::disk('public')->url('uploads/products/' . $product->id . '/' . $firstUpload->filename);
                            } elseif ($images && ($images->front || $images->preview)) {
                                $candidate = $images->front ?: $images->preview;
                                $previewSrc = \App\Support\ImageResolver::url($candidate);
                            } elseif (!empty($product->image)) {
                                $previewSrc = \App\Support\ImageResolver::url($product->image);
                            } elseif ($templateRef) {
                                $templatePreview = $templateRef->preview_front ?? $templateRef->front_image ?? $templateRef->preview ?? $templateRef->image ?? null;
                                if ($templatePreview) {
                                    $previewSrc = preg_match('/^(https?:)?\/\//i', $templatePreview) || str_starts_with($templatePreview, '/')
                                        ? $templatePreview
                                        : \Illuminate\Support\Facades\Storage::url($templatePreview);
                                }
                            }

                            if (!$previewSrc) {
                                $previewSrc = asset('images/no-image.png');
                            }

                            $priceValue = $product->base_price ?? $product->unit_price ?? optional($templateRef)->base_price ?? optional($templateRef)->unit_price;
                            $previewUrl = route('product.preview', $product->id);
                            $ratingSummary = $ratingsData[$product->id] ?? [];
                            $ratingCount = $ratingSummary['rating_count'] ?? 0;
                            $averageRating = $ratingSummary['average_rating'] ?? null;
                        @endphp
                        <article class="giveaway-card" role="listitem" data-product-id="{{ $product->id }}">
                            <button type="button"
                                    class="favorite-toggle"
                                    data-product-id="{{ $product->id }}"
                                    aria-label="Save {{ $product->name }} to favorites"
                                    aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9-8.5C1.33 9.5 2.15 6 5 4.8 7.38 3.77 9.55 4.89 12 7.4c2.45-2.51 4.62-3.63 7-2.6 2.85 1.2 3.68 4.7 2 7.7-2.5 4.15-9 8.5-9 8.5Z" />
                                </svg>
                            </button>
                            <img src="{{ $previewSrc }}"
                                 alt="{{ $product->name }} giveaway preview"
                                 class="giveaway-card__image preview-trigger"
                                 loading="lazy"
                                 data-product-id="{{ $product->id }}"
                                 data-preview-url="{{ $previewUrl }}"
                                 data-template="{{ $product->name }}">
                            <div class="giveaway-card__info preview-trigger"
                                 data-product-id="{{ $product->id }}"
                                 data-preview-url="{{ $previewUrl }}"
                                 data-template="{{ $product->name }}"
                                 role="button"
                                 tabindex="0">
                                <h3 class="giveaway-card__name">{{ \Illuminate\Support\Str::limit($product->name, 28) }}</h3>
                                <span class="giveaway-card__price-tag">
                                    @if(!is_null($priceValue))
                                        ₱{{ number_format($priceValue, 2) }}
                                    @else
                                        Custom quote
                                    @endif
                                </span>
                                <div class="giveaway-card__ratings rating-trigger"
                                     role="button"
                                     tabindex="0"
                                     data-product-id="{{ $product->id }}"
                                     aria-label="{{ $ratingCount > 0 ? 'View reviews for '.$product->name : 'Be the first to review '.$product->name }}">
                                    @if($ratingCount > 0)
                                        @php
                                            $roundedAverage = min(5, max(0, (int) round($averageRating ?? 0)));
                                        @endphp
                                        <div class="giveaway-card__rating-stars" aria-hidden="true">
                                            @for($star = 1; $star <= 5; $star++)
                                                <span class="giveaway-card__rating-star {{ $star <= $roundedAverage ? 'filled' : '' }}">★</span>
                                            @endfor
                                        </div>
                                        <span class="giveaway-card__rating-detail">
                                            {{ number_format($averageRating ?? 0, 1) }} · {{ $ratingCount }} review{{ $ratingCount === 1 ? '' : 's' }}
                                        </span>
                                    @else
                                        <span class="giveaway-card__rating-empty">New · Be the first to review</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
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
