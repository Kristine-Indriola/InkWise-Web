@php
    $invitationType = 'Corporate';
    $products = $products ?? collect();
@endphp
@extends('customer.Invitations.invitations')

@section('title', 'Corporate Invitations')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --corp-accent: #f9cf9d;
            --corp-accent-dark: #f2a65a;
            --corp-surface: #ffffff;
            --corp-muted: #6b7280;
            --corp-shadow: 0 24px 48px rgba(242, 166, 90, 0.18);
        }

        .corporate-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .corporate-hero {
            position: relative;
            padding: clamp(1.75rem, 5vw, 3.25rem);
            text-align: center;
            color: #0f172a;
        }

        .corporate-hero__content {
            max-width: 680px;
            margin-inline: auto;
        }

        .corporate-hero__title {
            margin-top: 0.5rem;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-family: 'ITC New Baskerville', 'Baskerville', 'Times New Roman', serif;
            font-weight: 700;
            line-height: 1.05;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        .corporate-hero__subtitle {
            margin-top: 0.75rem;
            font-size: clamp(0.8rem, 2vw, 1.1rem);
            font-family: 'ITC New Baskerville', 'Baskerville', 'Times New Roman', serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #1f2937;
        }

        .corporate-gallery {
            position: relative;
            padding-bottom: 2rem;
        }

        .corporate-gallery::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top, rgba(242, 166, 90, 0.18), transparent 55%);
            pointer-events: none;
        }

        .corporate-gallery::after {
            content: "";
            position: absolute;
            inset: auto 8% -10% 8%;
            height: 220px;
            background: rgba(255, 255, 255, 0.5);
            filter: blur(80px);
            pointer-events: none;
        }

        .corporate-gallery .layout-container {
            position: relative;
            z-index: 1;
        }

        .corporate-grid {
            display: grid;
            gap: clamp(1.25rem, 3vw, 2.5rem);
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            justify-items: center;
        }

        .corporate-card {
            position: relative;
            width: min(300px, 100%);
            aspect-ratio: 2 / 3.4;
            border-radius: 26px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(242, 166, 90, 0.22), rgba(255, 255, 255, 0.65));
            box-shadow: 0 18px 40px rgba(242, 166, 90, 0.16);
            transition: transform 0.35s ease, box-shadow 0.35s ease, filter 0.35s ease;
            isolation: isolate;
            opacity: 0;
            transform: translateY(24px) scale(0.98);
        }

        .corporate-card.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .corporate-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(0, 0, 0, 0.25));
            opacity: 0;
            transition: opacity 0.35s ease;
            pointer-events: none;
        }

        .corporate-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 32px 60px rgba(242, 166, 90, 0.35);
            filter: drop-shadow(0 12px 25px rgba(31, 41, 55, 0.15));
        }

        .corporate-card:hover::after {
            opacity: 1;
        }

        .corporate-card__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scale(1.05);
            transition: transform 0.35s ease, filter 0.35s ease;
            cursor: pointer;
            border-radius: inherit;
        }

        .corporate-card:hover .corporate-card__image {
            transform: scale(1.08);
            filter: saturate(1.05) contrast(1.05);
        }

        .corporate-card__info {
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

        .corporate-card:hover .corporate-card__info,
        .corporate-card:focus-within .corporate-card__info {
            transform: translateY(0);
            opacity: 1;
        }

        .corporate-card__info::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.5));
            border-radius: 0 0 26px 26px;
            z-index: -1;
        }

        .corporate-card__name {
            font-family: 'The Seasons', 'Playfair Display', serif;
            font-size: 1rem;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .corporate-card__price {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .corporate-card__ratings {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.88);
            letter-spacing: 0.03em;
        }

        .corporate-card__rating-stars {
            display: flex;
            gap: 0.15rem;
        }

        .corporate-card__rating-star {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .corporate-card__rating-star.filled {
            color: #fcd34d;
            text-shadow: 0 0 6px rgba(252, 211, 77, 0.6);
        }

        .corporate-card__rating-detail {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .corporate-card__rating-empty {
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
            background: rgba(255, 255, 255, 0.85);
            color: var(--corp-accent-dark);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.18);
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

        .corporate-empty {
            text-align: center;
            border-radius: 24px;
            padding: clamp(2.5rem, 6vw, 4rem);
            background: rgba(242, 166, 90, 0.08);
            border: 1px dashed rgba(242, 166, 90, 0.45);
            color: #b45309;
        }

        .corporate-empty p {
            margin-top: 0.75rem;
            color: var(--corp-muted);
        }

        .rating-modal-overlay {
            display: none;
        }

        @media (max-width: 640px) {
            .corporate-hero {
                border-radius: 24px;
            }

            .corporate-card {
                width: min(240px, 100%);
                border-radius: 20px;
            }

            .favorite-toggle {
                width: 2rem;
                height: 2rem;
            }

            .corporate-card__info {
                transform: none;
                opacity: 1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const FAVORITES_KEY = 'inkwise:corporate:favorites';
            let favorites;
            try {
                const stored = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
                favorites = new Set(stored);
            } catch (error) {
                console.warn('Unable to parse corporate favorites from storage.', error);
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

                document.querySelectorAll('.corporate-card').forEach((card) => observer.observe(card));
            } else {
                document.querySelectorAll('.corporate-card').forEach((card) => card.classList.add('is-visible'));
            }

            // Rating modal support (parity with other templates)
            const ratingModal = document.getElementById('ratingModal');
            const ratingModalClose = document.getElementById('ratingModalClose');
            if (ratingModal && ratingModalClose) {
                window.productRatings = @json($ratingsData ?? []);

                const showRatingModal = (productId) => {
                    const productData = window.productRatings[productId];
                    if (!productData) return;
                    ratingModal.querySelector('#ratingModalTitle').textContent = `Reviews for ${productData.name}`;
                };

                ratingModalClose.addEventListener('click', () => {
                    ratingModal.classList.remove('is-visible');
                    ratingModal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                });
            }
        });
    </script>
@endpush

@section('content')
<main class="corporate-page">
    <section class="corporate-hero">
        <div class="corporate-hero__content">
            <h1 class="corporate-hero__title">CORPORATE INVITATIONS</h1>
            <p class="corporate-hero__subtitle">INSPIRE PROFESSIONAL GATHERINGS</p>
        </div>
    </section>

    <section class="corporate-gallery">
        <div class="layout-container">
            <div class="corporate-grid" role="list">
                @forelse($products as $product)
                    @php
                        $uploads = $product->uploads ?? collect();
                        $firstUpload = $uploads->first();
                        $images = $product->product_images ?? $product->images ?? null;
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

                        $previewUrl = route('product.preview', $product->id);
                        $priceValue = $product->base_price
                            ?? $product->unit_price
                            ?? optional($templateRef)->base_price
                            ?? optional($templateRef)->unit_price;
                        $ratingSummary = $ratingsData[$product->id] ?? [];
                        $ratingCount = $ratingSummary['rating_count'] ?? 0;
                        $averageRating = $ratingSummary['average_rating'] ?? null;
                    @endphp
                    <article class="corporate-card" role="listitem" data-product-id="{{ $product->id }}">
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
                             alt="{{ $product->name }} corporate invitation"
                             class="corporate-card__image preview-trigger"
                             loading="lazy"
                             data-product-id="{{ $product->id }}"
                             data-preview-url="{{ $previewUrl }}"
                             data-template="{{ $product->name }}">
                        <div class="corporate-card__info preview-trigger"
                             data-product-id="{{ $product->id }}"
                             data-preview-url="{{ $previewUrl }}"
                             data-template="{{ $product->name }}"
                             role="button"
                             tabindex="0">
                            <h3 class="corporate-card__name">{{ \Illuminate\Support\Str::limit($product->name, 28) }}</h3>
                            <span class="corporate-card__price">
                                @if(!is_null($priceValue))
                                    ₱{{ number_format($priceValue, 2) }}
                                @else
                                    Custom quote
                                @endif
                            </span>
                            <div class="corporate-card__ratings rating-trigger"
                                 role="button"
                                 tabindex="0"
                                 data-product-id="{{ $product->id }}"
                                 aria-label="{{ $ratingCount > 0 ? 'View reviews for '.$product->name : 'Be the first to review '.$product->name }}">
                                @if($ratingCount > 0)
                                    @php
                                        $roundedAverage = min(5, max(0, (int) round($averageRating ?? 0)));
                                    @endphp
                                    <div class="corporate-card__rating-stars" aria-hidden="true">
                                        @for($star = 1; $star <= 5; $star++)
                                            <span class="corporate-card__rating-star {{ $star <= $roundedAverage ? 'filled' : '' }}">★</span>
                                        @endfor
                                    </div>
                                    <span class="corporate-card__rating-detail">
                                        {{ number_format($averageRating ?? 0, 1) }} · {{ $ratingCount }} review{{ $ratingCount === 1 ? '' : 's' }}
                                    </span>
                                @else
                                    <span class="corporate-card__rating-empty">New · Be the first to review</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="corporate-empty">
                        <h3>No corporate invitations yet</h3>
                        <p>We’re curating professional suites. Please check back soon or contact us for bespoke concepts.</p>
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

<div id="ratingModal" class="rating-modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="rating-modal">
        <div class="rating-modal-header">
            <h3 class="rating-modal-title" id="ratingModalTitle">Customer Reviews</h3>
            <button type="button" class="rating-modal-close" id="ratingModalClose" aria-label="Close reviews">×</button>
        </div>
        <div class="rating-modal-body" id="ratingModalBody"></div>
    </div>
</div>
@endsection

