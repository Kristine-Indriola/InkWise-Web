@php $invitationType = 'Wedding'; @endphp
@extends('customer.Invitations.invitations')

@section('title', 'Wedding Invitations')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --invite-accent: #a6b7ff;
            --invite-accent-dark: #8f9dff;
            --invite-surface: #ffffff;
            --invite-muted: #6b7280;
            --invite-shadow: 0 24px 48px rgba(70, 89, 182, 0.18);
        }

        .wedding-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .wedding-hero {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: clamp(1.75rem, 5vw, 3.25rem);
            background:
                radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.9), rgba(166, 183, 255, 0.65)),
                linear-gradient(135deg, #f5f0ff, #edf2ff 55%, #f9f7ff);
            box-shadow: 0 28px 55px rgba(79, 70, 229, 0.18);
            color: #111827;
            isolation: isolate;
        }

        .wedding-hero::before,
        .wedding-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(0);
            opacity: 0.55;
            transform: translate3d(0,0,0);
        }

        .wedding-hero::before {
            width: clamp(180px, 28vw, 320px);
            height: clamp(180px, 28vw, 320px);
            background: radial-gradient(circle, rgba(255, 255, 255, 0.85), rgba(166, 183, 255, 0));
            top: -12%;
            right: 12%;
        }

        .wedding-hero::after {
            width: clamp(220px, 32vw, 380px);
            height: clamp(220px, 32vw, 380px);
            background: radial-gradient(circle, rgba(140, 154, 255, 0.4), rgba(255, 255, 255, 0));
            bottom: -18%;
            left: 8%;
        }

        .wedding-hero__content {
            position: relative;
            max-width: 680px;
            margin-inline: auto;
            text-align: center;
            z-index: 1;
        }

        .wedding-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            background: rgba(166, 183, 255, 0.18);
            color: #4338ca;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .wedding-hero__title {
            margin-top: 1rem;
            font-size: clamp(2rem, 5vw, 2.8rem);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.1;
        }

        .wedding-hero__title span {
            display: inline-block;
        }

        .wedding-hero__title .highlight-primary {
            color: var(--invite-accent-dark);
        }

        .wedding-hero__title .highlight-secondary {
            color: #4338ca;
        }

        .wedding-hero__subtitle {
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
            background: linear-gradient(180deg, rgba(166, 183, 255, 0.08), transparent 35%, transparent 65%, rgba(166, 183, 255, 0.05));
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
            box-shadow: 0 18px 40px rgba(79, 70, 229, 0.12);
            border: 1px solid rgba(166, 183, 255, 0.18);
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
            background: linear-gradient(135deg, rgba(166, 183, 255, 0.15), rgba(166, 183, 255, 0.05));
        }

        .invitation-card__image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform 0.35s ease;
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
            box-shadow: 0 14px 26px rgba(166, 183, 255, 0.28);
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
            fill: currentColor;
            stroke: currentColor;
        }

        .favorite-toggle.is-active {
            background: var(--invite-accent);
            color: #ffffff;
            box-shadow: 0 18px 32px rgba(166, 183, 255, 0.4);
        }

        .invitation-card__body {
            margin-top: 1.15rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-align: left;
        }

        .invitation-card__title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #111827;
        }

        .invitation-card__subtitle {
            color: var(--invite-muted);
            font-size: 0.9rem;
        }

        .invitation-card__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(166, 183, 255, 0.18);
            color: #4338ca;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .invitation-card__price {
            font-size: 1rem;
            font-weight: 700;
            color: #059669;
        }

        .invitation-card__muted {
            color: var(--invite-muted);
            font-size: 0.85rem;
        }

        .invitation-card__rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            font-size: 0.85rem;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .invitation-card__rating:hover {
            opacity: 0.8;
        }

        .invitation-card__stars {
            display: flex;
            gap: 1px;
        }

        .invitation-card__star {
            font-size: 0.9rem;
            color: #ddd;
        }

        .invitation-card__star.filled {
            color: #f59e0b;
        }

        .invitation-card__rating-text {
            color: var(--invite-muted);
            font-weight: 500;
        }

        .invitation-card__review {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: rgba(166, 183, 255, 0.05);
            border-radius: 8px;
            border-left: 3px solid var(--invite-accent);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .invitation-card__review-text {
            font-size: 0.85rem;
            color: #374151;
            font-style: italic;
            line-height: 1.4;
            margin: 0;
            flex: 1;
        }

        .invitation-card__materials {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: rgba(166, 183, 255, 0.05);
            border-radius: 8px;
            border-left: 3px solid var(--invite-accent);
        }

        .invitation-card__materials-text {
            font-size: 0.85rem;
            color: #374151;
            line-height: 1.4;
            margin: 0;
        }

        .invitation-card__low-stock {
            font-size: 0.8rem;
            color: #dc2626;
            font-weight: 600;
            margin: 0.25rem 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(166, 183, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .rating-modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .rating-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--invite-muted);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .rating-modal-close:hover {
            background: rgba(166, 183, 255, 0.1);
            color: #4338ca;
        }

        .rating-modal-body {
            padding: 1.5rem 2rem;
        }

        .rating-modal-summary {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(166, 183, 255, 0.2);
        }

        .rating-modal-stars {
            display: flex;
            justify-content: center;
            gap: 2px;
            margin: 0.5rem 0;
        }

        .rating-modal-star {
            font-size: 1.5rem;
            color: #ddd;
        }

        .rating-modal-star.filled {
            color: #f59e0b;
        }

        .rating-modal-average {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            margin: 0.25rem 0;
        }

        .rating-modal-count {
            color: var(--invite-muted);
            font-size: 0.9rem;
        }

        .rating-modal-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rating-modal-item {
            border-bottom: 1px solid rgba(166, 183, 255, 0.1);
            padding: 1.5rem 0;
        }

        .rating-modal-item:last-child {
            border-bottom: none;
        }

        .rating-modal-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            gap: 1rem;
        }

        .rating-modal-item-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }

        .rating-modal-item-customer {
            font-weight: 600;
            color: #111827;
            font-size: 0.9rem;
        }

        .rating-modal-item-stars {
            display: flex;
            gap: 1px;
        }

        .rating-modal-item-star {
            font-size: 1rem;
            color: #ddd;
        }

        .rating-modal-item-star.filled {
            color: #f59e0b;
        }

        .rating-modal-item-date {
            font-size: 0.85rem;
            color: var(--invite-muted);
        }

        .rating-modal-item-review {
            margin: 0.75rem 0;
            line-height: 1.5;
            color: #374151;
        }

        .rating-modal-item-photos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .rating-modal-item-photo {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .rating-modal-item-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .rating-modal-empty {
            text-align: center;
            color: var(--invite-muted);
            padding: 2rem;
        }

        .invitation-card__actions {
            margin-top: auto;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .invitation-card__action {
            flex: 1 1 auto;
            border-radius: 999px;
            border: 1px solid rgba(166, 183, 255, 0.5);
            padding: 0.6rem 1rem;
            background: rgba(166, 183, 255, 0.12);
            color: #4338ca;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .invitation-card__action:hover {
            background: linear-gradient(135deg, var(--invite-accent), var(--invite-accent-dark));
            color: #ffffff;
            transform: translateY(-2px);
        }

        .invitation-empty {
            text-align: center;
            border-radius: 24px;
            padding: clamp(2.5rem, 6vw, 4rem);
            background: rgba(166, 183, 255, 0.08);
            border: 1px dashed rgba(166, 183, 255, 0.45);
            color: #4338ca;
        }

        .invitation-empty p {
            margin-top: 0.75rem;
            color: var(--invite-muted);
        }

        @media (max-width: 640px) {
            .wedding-hero {
                border-radius: 24px;
            }

            .invitation-card {
                padding: 1.1rem;
                border-radius: 20px;
            }

            .favorite-toggle {
                width: 2.25rem;
                height: 2.25rem;
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
            const FAVORITES_KEY = 'inkwise:wedding:favorites';
            let favorites;
            try {
                const stored = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
                favorites = new Set(stored);
            } catch (error) {
                console.warn('Unable to parse wedding favorites from storage.', error);
                favorites = new Set();
            }

            const updateStorage = () => {
                if (!window.localStorage) return;
                window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(favorites)));
            };

            const setFavoriteState = (button, active) => {
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                const baseLabel = button.getAttribute('data-label') || 'Save to favorites';
                button.setAttribute('title', active ? 'Remove from favorites' : baseLabel);
            };

            document.querySelectorAll('.favorite-toggle').forEach((button) => {
                const productId = button.getAttribute('data-product-id');
                if (!productId) return;

                button.dataset.label = button.dataset.label || button.getAttribute('aria-label') || 'Save to favorites';

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

                document.querySelectorAll('.invitation-card').forEach((card) => observer.observe(card));
            } else {
                document.querySelectorAll('.invitation-card').forEach((card) => card.classList.add('is-visible'));
            }

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
<main class="wedding-page">
    <section class="wedding-hero">
        <div class="wedding-hero__content">
            <span class="wedding-hero__eyebrow">Hand-crafted moments</span>
            <h1 class="wedding-hero__title">
                <span class="highlight-primary">Wedding invitations</span>
                <span class="highlight-secondary">for your story</span>
            </h1>
            <p class="wedding-hero__subtitle">
                Discover romantic templates with luxe finishes, layered textures, and typography that captures the magic of your celebration.
            </p>
        </div>
    </section>

    <section class="invitation-gallery">
        <div class="layout-container">
            <div class="invitation-grid" role="list">
                @forelse($products as $product)
                    @php
                        $uploads = $product->uploads ?? collect();
                        $firstUpload = $uploads->first();
                        $images = $product->product_images ?? $product->images ?? null;
                        $templateRef = $product->template ?? null;

                        $previewSrc = null;
                        if ($firstUpload && str_starts_with($firstUpload->mime_type ?? '', 'image/')) {
                            $previewSrc = asset('storage/uploads/products/' . $product->id . '/' . $firstUpload->filename);
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
                            $previewSrc = asset('images/placeholder.png');
                        }

                        $priceValue = $product->base_price ?? $product->unit_price ?? optional($templateRef)->base_price ?? optional($templateRef)->unit_price;
                        $eventLabel = $product->event_type ?? 'Invitation';
                        $themeLabel = $product->theme_style ?? 'Custom theme';
                        $previewUrl = route('product.preview', $product->id);

                        $ratings = $product->ratings ?? collect();
                        $averageRating = $ratings->avg('rating');
                        $ratingCount = $ratings->count();

                        $materials = $product->materials ?? collect();

                        $hasLowStockMaterials = $materials->some(function($material) {
                            return ($material->stock_quantity ?? 100) < 10;
                        });
                    @endphp
                    <article class="invitation-card" role="listitem" data-product-id="{{ $product->id }}" data-has-low-stock="{{ $hasLowStockMaterials ? 'true' : 'false' }}">
                        <div class="invitation-card__preview">
                            <button type="button"
                                    class="favorite-toggle"
                                    data-product-id="{{ $product->id }}"
                                    aria-label="Save {{ $product->name }} to favorites"
                                    aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9-8.5C1.33 9.5 2.15 6 5 4.8 7.38 3.77 9.55 4.89 12 7.4c2.45-2.51 4.62-3.63 7-2.6 2.85 1.2 3.68 4.7 2 7.7-2.5 4.15-9 8.5-9 8.5Z"/>
                                </svg>
                            </button>
                            <img src="{{ $previewSrc }}"
                                 alt="{{ $product->name }} invitation design"
                                 class="invitation-card__image preview-trigger"
                                 loading="lazy"
                                 data-product-id="{{ $product->id }}"
                                 data-preview-url="{{ $previewUrl }}"
                                 data-template="{{ $product->name }}">
                        </div>
                        <div class="invitation-card__body">
                            <h2 class="invitation-card__title">{{ $product->name }}</h2>
                            <p class="invitation-card__subtitle">{{ $themeLabel }}</p>
                            <span class="invitation-card__badge">{{ $eventLabel }}</span>
                            @if(!is_null($priceValue))
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
                        <h3>No wedding invitations yet</h3>
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




