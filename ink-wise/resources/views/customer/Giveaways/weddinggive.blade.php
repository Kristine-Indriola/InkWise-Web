@php $invitationType = 'Wedding'; @endphp
@extends('customer.Invitations.invitations')

@section('title', 'Wedding Giveaways')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --give-accent: #a6b7ff;
            --give-accent-dark: #8f9dff;
            --give-surface: #ffffff;
            --give-muted: #6b7280;
            --give-shadow: 0 24px 48px rgba(70, 89, 182, 0.18);
        }

        .giveaway-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .giveaway-hero {
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

        .giveaway-hero::before,
        .giveaway-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            opacity: 0.55;
            transform: translate3d(0,0,0);
        }

        .giveaway-hero::before {
            width: clamp(180px, 28vw, 320px);
            height: clamp(180px, 28vw, 320px);
            background: radial-gradient(circle, rgba(255, 255, 255, 0.85), rgba(166, 183, 255, 0));
            top: -12%;
            right: 12%;
        }

        .giveaway-hero::after {
            width: clamp(220px, 32vw, 380px);
            height: clamp(220px, 32vw, 380px);
            background: radial-gradient(circle, rgba(140, 154, 255, 0.4), rgba(255, 255, 255, 0));
            bottom: -18%;
            left: 8%;
        }

        .giveaway-hero__content {
            position: relative;
            max-width: 680px;
            margin-inline: auto;
            text-align: center;
            z-index: 1;
        }

        .giveaway-hero__eyebrow {
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

        .giveaway-hero__title {
            margin-top: 1rem;
            font-size: clamp(2rem, 5vw, 2.8rem);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.1;
        }

        .giveaway-hero__title .highlight-primary {
            color: var(--give-accent-dark);
        }

        .giveaway-hero__title .highlight-secondary {
            color: #4338ca;
        }

        .giveaway-hero__subtitle {
            margin-top: 0.85rem;
            font-size: clamp(0.95rem, 2vw, 1.1rem);
            color: var(--give-muted);
        }

        .giveaway-gallery {
            position: relative;
        }

        .giveaway-gallery::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(166, 183, 255, 0.08), transparent 35%, transparent 65%, rgba(166, 183, 255, 0.05));
            pointer-events: none;
        }

        .giveaway-gallery .layout-container {
            position: relative;
            z-index: 1;
        }

        .giveaway-grid {
            display: grid;
            gap: clamp(1.75rem, 3.5vw, 2.5rem);
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .giveaway-card {
            position: relative;
            display: flex;
            flex-direction: column;
            background: var(--give-surface);
            border-radius: 24px;
            padding: 1.35rem;
            box-shadow: 0 18px 38px rgba(79, 70, 229, 0.12);
            border: 1px solid rgba(166, 183, 255, 0.18);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            opacity: 0;
            transform: translateY(28px) scale(0.98);
        }

        .giveaway-card.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .giveaway-card:hover {
            transform: translateY(-10px) scale(1.01);
            box-shadow: var(--give-shadow);
        }

        .giveaway-card__preview {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 4 / 3;
            background: linear-gradient(135deg, rgba(166, 183, 255, 0.15), rgba(166, 183, 255, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .giveaway-card__image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform 0.35s ease;
        }

        .giveaway-card:hover .giveaway-card__image {
            transform: scale(1.04);
        }

        .favorite-toggle {
            position: absolute;
            top: 0.85rem;
            right: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 14px 26px rgba(166, 183, 255, 0.28);
            color: var(--give-accent-dark);
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .favorite-toggle:hover {
            transform: translateY(-2px) scale(1.04);
            background: var(--give-accent-dark);
            color: #ffffff;
        }

        .favorite-toggle svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: currentColor;
            stroke: currentColor;
        }

        .favorite-toggle.is-active {
            background: var(--give-accent-dark);
            color: #ffffff;
            box-shadow: 0 18px 32px rgba(166, 183, 255, 0.4);
        }

        .giveaway-card__body {
            margin-top: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-align: left;
        }

        .giveaway-card__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            background: rgba(166, 183, 255, 0.18);
            color: #4338ca;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .giveaway-card__title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
        }

        .giveaway-card__subtitle {
            color: var(--give-muted);
            font-size: 0.92rem;
        }

        .giveaway-card__price {
            font-size: 1rem;
            font-weight: 700;
            color: #059669;
        }

        .giveaway-card__meta {
            display: grid;
            gap: 0.35rem;
            font-size: 0.85rem;
            color: var(--give-muted);
        }

        .giveaway-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .giveaway-card__chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(166, 183, 255, 0.14);
            color: #4338ca;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .giveaway-card__actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .giveaway-card__action {
            flex: 1 1 auto;
            border-radius: 999px;
            border: 1px solid rgba(166, 183, 255, 0.45);
            padding: 0.65rem 1rem;
            background: rgba(166, 183, 255, 0.12);
            color: #4338ca;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            text-align: center;
        }

        .giveaway-card__action:hover {
            background: linear-gradient(135deg, var(--give-accent), var(--give-accent-dark));
            color: #ffffff;
            transform: translateY(-2px);
        }

        .giveaway-empty {
            text-align: center;
            border-radius: 24px;
            padding: clamp(2.5rem, 6vw, 4rem);
            background: rgba(166, 183, 255, 0.08);
            border: 1px dashed rgba(166, 183, 255, 0.45);
            color: #4338ca;
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
                padding: 1.15rem;
                border-radius: 20px;
            }

            .favorite-toggle {
                width: 2.25rem;
                height: 2.25rem;
            }

            .giveaway-card__actions {
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const FAVORITES_KEY = 'inkwise:wedding:giveaways:favorites';
            let favorites;

            try {
                const stored = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
                favorites = new Set(stored);
            } catch (error) {
                console.warn('Unable to parse wedding giveaway favorites from storage.', error);
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
        });
    </script>
@endpush

@section('content')
@php
    $hasProducts = isset($products) && $products->count();
@endphp
<main class="giveaway-page">
    <section class="giveaway-hero">
        <div class="giveaway-hero__content">
            <span class="giveaway-hero__eyebrow">Thoughtful tokens</span>
            <h1 class="giveaway-hero__title">
                <span class="highlight-primary">Wedding giveaways</span>
                <span class="highlight-secondary">to share your joy</span>
            </h1>
            <p class="giveaway-hero__subtitle">
                Curate keepsakes that feel bespoke—personalized details, softened palettes, and packaging ready to delight every guest.
            </p>
        </div>
    </section>

    <section class="giveaway-gallery">
        <div class="layout-container">
            @if(!$hasProducts)
                <div class="giveaway-empty">
                    <h2>No giveaways available yet</h2>
                    <p>We’re crafting new keepsakes. Check back soon for fresh designs, or message us for a custom concept.</p>
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
                            $primaryBulk = $product->bulkOrders?->sortBy('min_qty')->first();
                            $bulkRange = null;
                            if ($primaryBulk) {
                                $min = $primaryBulk->min_qty;
                                $max = $primaryBulk->max_qty;
                                if ($min && $max) {
                                    $bulkRange = $min . ' – ' . $max . ' pcs';
                                } elseif ($min) {
                                    $bulkRange = 'Minimum ' . $min . ' pcs';
                                } elseif ($max) {
                                    $bulkRange = 'Up to ' . $max . ' pcs';
                                }

                                if (!$priceValue && $primaryBulk->price_per_unit) {
                                    $priceValue = $primaryBulk->price_per_unit;
                                }
                            }

                            $materials = $product->materials?->map(function ($productMaterial) {
                                return $productMaterial->material->material_name
                                    ?? $productMaterial->item
                                    ?? $productMaterial->type;
                            })->filter()->unique()->take(3)->implode(', ');
                            $previewUrl = route('product.preview', $product->id);
                        @endphp
                        <article class="giveaway-card" role="listitem" data-product-id="{{ $product->id }}">
                            <div class="giveaway-card__preview">
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
                                     data-preview-url="{{ $previewUrl }}"
                                     data-template="{{ $product->name }}">
                            </div>

                            <div class="giveaway-card__body">
                                @if(!empty($product->theme_style))
                                    <span class="giveaway-card__badge">{{ $product->theme_style }}</span>
                                @endif

                                <h2 class="giveaway-card__title">{{ $product->name }}</h2>
                                @if(!empty($product->description))
                                    <p class="giveaway-card__subtitle">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 120) }}</p>
                                @endif

                                @if(!is_null($priceValue))
                                    <div class="giveaway-card__price">Starting at ₱{{ number_format($priceValue, 2) }}</div>
                                @else
                                    <div class="giveaway-card__price" style="color: var(--give-muted); font-weight: 500;">Pricing available on request</div>
                                @endif

                                <div class="giveaway-card__meta">
                                    @if($bulkRange)
                                        <span>Suggested quantities: {{ $bulkRange }}</span>
                                    @endif
                                    @if(!empty($product->lead_time))
                                        <span>Lead time: {{ $product->lead_time }}</span>
                                    @endif
                                    @if($materials)
                                        <span>Crafted with: {{ $materials }}</span>
                                    @endif
                                    @if(!empty($product->date_available))
                                        <span>Ready by: {{ \Illuminate\Support\Carbon::parse($product->date_available)->format('M d, Y') }}</span>
                                    @endif
                                </div>

                                <div class="giveaway-card__chips" aria-label="Giveaway highlights">
                                    @if(!empty($product->event_type))
                                        <span class="giveaway-card__chip">{{ $product->event_type }}</span>
                                    @endif
                                    @if($primaryBulk && $primaryBulk->price_per_unit)
                                        <span class="giveaway-card__chip">Bulk rate ₱{{ number_format($primaryBulk->price_per_unit, 2) }}</span>
                                    @endif
                                </div>

                                <div class="giveaway-card__actions">
                                    <a href="{{ route('custome.rprofile.orderform') }}" class="giveaway-card__action">Start order</a>
                                    <button type="button"
                                            class="giveaway-card__action preview-trigger"
                                            data-preview-url="{{ $previewUrl }}">
                                        Quick preview
                                    </button>
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
@endsection
