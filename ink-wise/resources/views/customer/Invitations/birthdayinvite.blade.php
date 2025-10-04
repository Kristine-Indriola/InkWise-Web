@php $invitationType = 'Birthday'; @endphp
@extends('customer.Invitations.invitations')

@section('title', 'Birthday Invitations')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <style>
        :root {
            --invite-accent: #f9a8d4;
            --invite-accent-dark: #f472b6;
            --invite-surface: #ffffff;
            --invite-muted: #6b7280;
            --invite-shadow: 0 24px 48px rgba(244, 114, 182, 0.22);
        }

        .birthday-page {
            display: flex;
            flex-direction: column;
            gap: clamp(2.25rem, 5vw, 3.5rem);
            padding-inline: clamp(1rem, 4vw, 3rem);
        }

        .birthday-hero {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: clamp(1.75rem, 5vw, 3.25rem);
            background:
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.95), rgba(249, 168, 212, 0.55)),
                linear-gradient(135deg, #fff0f6, #fdf2f8 55%, #fff7ed);
            box-shadow: 0 28px 55px rgba(244, 114, 182, 0.22);
            color: #111827;
            isolation: isolate;
        }

        .birthday-hero::before,
        .birthday-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            opacity: 0.55;
            transform: translate3d(0, 0, 0);
        }

        .birthday-hero::before {
            width: clamp(180px, 28vw, 320px);
            height: clamp(180px, 28vw, 320px);
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9), rgba(249, 168, 212, 0));
            top: -12%;
            right: 10%;
        }

        .birthday-hero::after {
            width: clamp(220px, 32vw, 380px);
            height: clamp(220px, 32vw, 380px);
            background: radial-gradient(circle, rgba(252, 211, 77, 0.35), rgba(255, 255, 255, 0));
            bottom: -18%;
            left: 8%;
        }

        .birthday-hero__content {
            position: relative;
            max-width: 680px;
            margin-inline: auto;
            text-align: center;
            z-index: 1;
        }

        .birthday-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            background: rgba(249, 168, 212, 0.2);
            color: #db2777;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .birthday-hero__title {
            margin-top: 1rem;
            font-size: clamp(2rem, 5vw, 2.8rem);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.1;
        }

        .birthday-hero__title span {
            display: inline-block;
        }

        .birthday-hero__title .highlight-primary {
            color: var(--invite-accent-dark);
        }

        .birthday-hero__title .highlight-secondary {
            color: #f97316;
        }

        .birthday-hero__subtitle {
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
            background: linear-gradient(180deg, rgba(249, 168, 212, 0.08), transparent 35%, transparent 65%, rgba(253, 186, 116, 0.08));
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
            box-shadow: 0 18px 40px rgba(244, 114, 182, 0.14);
            border: 1px solid rgba(249, 168, 212, 0.28);
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
            background: linear-gradient(135deg, rgba(249, 168, 212, 0.15), rgba(253, 186, 116, 0.1));
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
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 14px 26px rgba(249, 168, 212, 0.28);
            color: var(--invite-accent-dark);
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .favorite-toggle:hover {
            transform: translateY(-2px) scale(1.03);
            background: var(--invite-accent-dark);
            color: #ffffff;
        }

        .favorite-toggle svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: currentColor;
            stroke: currentColor;
        }

        .favorite-toggle.is-active {
            background: var(--invite-accent-dark);
            color: #ffffff;
            box-shadow: 0 18px 32px rgba(249, 168, 212, 0.38);
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
            background: rgba(253, 186, 116, 0.2);
            color: #f97316;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .invitation-card__price {
            font-size: 1rem;
            font-weight: 700;
            color: #10b981;
        }

        .invitation-card__muted {
            color: var(--invite-muted);
            font-size: 0.85rem;
        }

        .swatch-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .swatch-button {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            border: 2px solid rgba(249, 168, 212, 0.35);
            box-shadow: 0 6px 14px rgba(249, 168, 212, 0.18);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .swatch-button:hover,
        .swatch-button.is-active {
            transform: translateY(-1px);
            border-color: var(--invite-accent-dark);
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
            border: 1px solid rgba(249, 168, 212, 0.45);
            padding: 0.6rem 1rem;
            background: rgba(249, 168, 212, 0.18);
            color: #db2777;
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
            background: rgba(249, 168, 212, 0.08);
            border: 1px dashed rgba(249, 168, 212, 0.45);
            color: #db2777;
        }

        .invitation-empty p {
            margin-top: 0.75rem;
            color: var(--invite-muted);
        }

        @media (max-width: 640px) {
            .birthday-hero {
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
            const FAVORITES_KEY = 'inkwise:birthday:favorites';
            let favorites;
            try {
                const stored = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
                favorites = new Set(stored);
            } catch (error) {
                console.warn('Unable to parse birthday favorites from storage.', error);
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

            const cardObserver = () => {
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
            };

            const handleSwatches = () => {
                document.querySelectorAll('.invitation-card').forEach((card) => {
                    const previewImage = card.querySelector('.invitation-card__image');
                    const previewTriggers = card.querySelectorAll('.preview-trigger');
                    const previewVideo = card.querySelector('video.invitation-card__video');
                    const source = previewVideo ? previewVideo.querySelector('source') : null;
                    const swatches = card.querySelectorAll('.swatch-button');

                    if (!swatches.length) return;

                    swatches.forEach((button) => {
                        button.addEventListener('click', () => {
                            const newImage = button.getAttribute('data-image');
                            const newVideo = button.getAttribute('data-video');

                            swatches.forEach((sw) => sw.classList.remove('is-active'));
                            button.classList.add('is-active');

                            if (newImage && previewImage) {
                                previewImage.src = newImage;
                            }

                            if (newVideo && source && previewVideo) {
                                if (source.src !== newVideo) {
                                    source.src = newVideo;
                                    previewVideo.load();
                                }
                                previewVideo.dataset.active = 'true';
                                previewTriggers.forEach((trigger) => {
                                    trigger.setAttribute('data-preview-url', newVideo);
                                });
                            }
                        });
                    });

                    const firstSwatch = swatches[0];
                    if (firstSwatch) {
                        firstSwatch.classList.add('is-active');
                    }
                });
            };

            cardObserver();
            handleSwatches();
        });
    </script>
@endpush

@section('content')
@php
    $birthdayTemplates = [
        [
            'id' => 'birthday-minimal-18th',
            'name' => 'Pink & Black Minimal 18th Birthday Invitation',
            'theme' => 'Modern glam',
            'label' => 'Birthday',
            'price' => null,
            'image' => asset('customerimages/invite/birthday1.png'),
            'video' => asset('customerVideo/birthday/birthday1.mp4'),
            'swatches' => [
                ['color' => '#fbcfe8', 'video' => asset('customerVideo/birthday/birthday1.mp4'), 'image' => asset('customerimages/invite/birthday1.png')],
                ['color' => '#e9d5ff', 'video' => asset('customerVideo/birthday/birthday2.mp4'), 'image' => asset('customerimages/invite/birthday1-front.png')],
                ['color' => '#c026d3', 'video' => asset('customerVideo/birthday/birthday3.mp4'), 'image' => asset('customerimages/invite/birthday1-back.png')],
            ],
        ],
        [
            'id' => 'birthday-watercolor',
            'name' => 'Pink & White Watercolor Party Invitation',
            'theme' => 'Whimsical florals',
            'label' => 'Birthday',
            'price' => null,
            'image' => asset('customerimages/invite/birthday2.png'),
            'video' => asset('customerVideo/birthday/birthday4.mp4'),
            'swatches' => [
                ['color' => '#fce7f3', 'video' => asset('customerVideo/birthday/birthday4.mp4'), 'image' => asset('customerimages/invite/birthday2.png')],
                ['color' => '#b45309', 'video' => asset('customerVideo/birthday/birthday5.mp4'), 'image' => asset('customerimages/invite/birthday2-front.png')],
                ['color' => '#1d4ed8', 'video' => asset('customerVideo/birthday/birthday6.mp4'), 'image' => asset('customerimages/invite/birthday2-back.png')],
            ],
        ],
        [
            'id' => 'birthday-blue-gold',
            'name' => 'Blue & Gold Night Bash Invitation',
            'theme' => 'Glam soirée',
            'label' => 'Birthday',
            'price' => null,
            'image' => asset('customerimages/invite/birthday3.png'),
            'video' => asset('customerVideo/birthday/birthday7.mp4'),
            'swatches' => [
                ['color' => '#38bdf8', 'video' => asset('customerVideo/birthday/birthday7.mp4'), 'image' => asset('customerimages/invite/birthday3.png')],
                ['color' => '#facc15', 'video' => asset('customerVideo/birthday/birthday8.mp4'), 'image' => asset('customerimages/invite/birthday3-front.png')],
                ['color' => '#1e3a8a', 'video' => asset('customerVideo/birthday/birthday9.mp4'), 'image' => asset('customerimages/invite/birthday3-back.png')],
            ],
        ],
    ];
@endphp
<main class="birthday-page">
    <section class="birthday-hero">
        <div class="birthday-hero__content">
            <span class="birthday-hero__eyebrow">Celebrate in style</span>
            <h1 class="birthday-hero__title">
                <span class="highlight-primary">Birthday invitations</span>
                <span class="highlight-secondary">for every milestone</span>
            </h1>
            <p class="birthday-hero__subtitle">
                Cue the confetti with playful designs, luxe finishes, and bright color stories crafted for unforgettable parties.
            </p>
        </div>
    </section>

    <section class="invitation-gallery">
        <div class="layout-container">
            <div class="invitation-grid" role="list">
                @forelse($birthdayTemplates as $template)
                    <article class="invitation-card" role="listitem" data-product-id="{{ $template['id'] }}">
                        <div class="invitation-card__preview">
                            <button type="button"
                                    class="favorite-toggle"
                                    data-product-id="{{ $template['id'] }}"
                                    aria-label="Save {{ $template['name'] }} to favorites"
                                    aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9-8.5C1.33 9.5 2.15 6 5 4.8 7.38 3.77 9.55 4.89 12 7.4c2.45-2.51 4.62-3.63 7-2.6 2.85 1.2 3.68 4.7 2 7.7-2.5 4.15-9 8.5-9 8.5Z"/>
                                </svg>
                            </button>
                            <img src="{{ $template['image'] }}"
                                 alt="{{ $template['name'] }} invitation design"
                                 class="invitation-card__image preview-trigger"
                                 loading="lazy"
                                 data-preview-url="{{ $template['video'] }}"
                                 data-template="{{ $template['name'] }}">
                            <video class="hidden invitation-card__video" muted playsinline>
                                <source src="{{ $template['video'] }}" type="video/mp4">
                            </video>
                        </div>
                        <div class="invitation-card__body">
                            <h2 class="invitation-card__title">{{ $template['name'] }}</h2>
                            <p class="invitation-card__subtitle">{{ $template['theme'] }}</p>
                            <span class="invitation-card__badge">{{ $template['label'] }}</span>
                            @if(!is_null($template['price']))
                                <p class="invitation-card__price">Starting at ₱{{ number_format($template['price'], 2) }}</p>
                            @else
                                <p class="invitation-card__muted">Pricing available on request</p>
                            @endif

                            @if(!empty($template['swatches']))
                                <div class="swatch-list" role="list">
                                    @foreach($template['swatches'] as $swatch)
                                        <button type="button"
                                                class="swatch-button"
                                                style="background: {{ $swatch['color'] }};"
                                                data-video="{{ $swatch['video'] }}"
                                                data-image="{{ $swatch['image'] }}"
                                                aria-label="Switch to {{ $template['name'] }} colorway">
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <div class="invitation-card__actions">
                                <a href="{{ route('customer.profile.orderform') }}" class="invitation-card__action">
                                    Start order
                                </a>
                                <button type="button"
                                        class="invitation-card__action preview-trigger"
                                        data-preview-url="{{ $template['video'] }}">
                                    Quick preview
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="invitation-empty">
                        <h3>No birthday invitations yet</h3>
                        <p>We’re designing new party-perfect templates. Check back soon or message us for a custom concept.</p>
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
@endsection

