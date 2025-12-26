<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Giveaway Selection — InkWise</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
    </style>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .nav-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 9999px;
            border: 1px solid #dbeafe;
            background-color: rgba(255, 255, 255, 0.92);
            color: #f472b6;
            transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 24px rgba(166, 183, 255, 0.25);
        }

        .nav-icon-button:hover {
            transform: translateY(-1px);
            background-color: #fdf2ff;
        }

        .nav-icon-button:focus-visible {
            outline: 2px solid #a6b7ff;
            outline-offset: 2px;
        }

        .nav-icon-button[aria-disabled="true"] {
            opacity: 0.6;
            pointer-events: none;
        }

        .nav-icon-button i {
            font-size: 1.15rem;
        }

        @media (max-width: 1024px) {
            .nav-icon-button {
                box-shadow: none;
            }
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/customer/orderflow-envelope.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/orderflow-giveaways.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/preview-modal.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
</head>
<body class="antialiased bg-white min-h-screen envelope-body">
    @php
        $favoritesEnabled = \Illuminate\Support\Facades\Route::has('customer.favorites');
        $cartRoute = \Illuminate\Support\Facades\Route::has('customer.cart')
            ? route('customer.cart')
            : '/order/addtocart';
        $searchValue = request('query', '');
        $bootCatalog = collect($initialCatalog ?? []);
        $inlineCatalogJson = $bootCatalog->isEmpty()
            ? '[]'
            : $bootCatalog->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp

    <header class="fixed top-0 z-40 w-full border-b border-[#c7d2fe] bg-white/95 backdrop-blur shadow-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="group flex items-center gap-2" aria-label="Inkwise home">
                <span class="text-4xl font-bold text-[#a6b7ff] transition-transform duration-200 group-hover:-translate-y-0.5" style="font-family: Edwardian Script ITC;">I</span>
                <span class="text-2xl font-bold text-gray-900" style="font-family: 'Playfair Display', serif;">nkwise</span>
            </a>

            <div class="flex items-center gap-3 lg:gap-6">
                <button id="navToggle" aria-controls="mobileNavPanel" aria-expanded="false" type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c7d2fe] text-[#4f46e5] transition hover:bg-[#eef2ff] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff] lg:hidden">
                    <span class="sr-only">Toggle navigation menu</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <nav id="primaryNav" class="hidden lg:flex">
                    <ul class="flex items-center gap-4 text-sm font-semibold text-gray-700">
                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition hover:text-[#4338ca]"
                               aria-current="false">
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order.envelope') }}"
                               class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition hover:text-[#4338ca]"
                               aria-current="false">
                                Envelope
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order.giveaways') }}"
                               class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition bg-[#eef2ff] text-[#4338ca] shadow-sm"
                               aria-current="page">
                                Giveaways
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order.summary') }}"
                               class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition hover:text-[#4338ca]"
                               aria-current="false">
                                Summary
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="flex items-center gap-3">
                    <form action="{{ url('/search') }}" method="GET" class="hidden lg:flex">
                        <label for="desktop-giveaway-search" class="sr-only">Search giveaways</label>
                        <input id="desktop-giveaway-search" type="text" name="query" value="{{ $searchValue }}" placeholder="Search giveaways..."
                               class="w-52 rounded-full border border-[#dbeafe] px-4 py-2 text-sm placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#a6b7ff]">
                    </form>

                    <div class="hidden items-center gap-2 lg:flex">
                        <a href="{{ $favoritesEnabled ? route('customer.favorites') : '#' }}"
                           class="nav-icon-button"
                           aria-label="My favorites"
                           title="My favorites"
                           @unless($favoritesEnabled) aria-disabled="true" @endunless>
                            <i class="fi fi-br-comment-heart" aria-hidden="true"></i>
                        </a>
                        <a href="{{ $cartRoute }}"
                           class="nav-icon-button"
                           aria-label="My cart"
                           title="My cart">
                            <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
                        </a>
                    </div>

                    @guest
                        <a href="{{ route('customer.login') }}"
                           class="hidden items-center rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff] px-5 py-2 font-semibold text-white shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff] sm:inline-flex"
                           style="font-family: 'Seasons', serif;">
                            Sign in
                        </a>
                    @endguest

                    @auth
                        <div class="relative">
                            <button id="userDropdownBtn" type="button" aria-expanded="false"
                                    class="inline-flex items-center gap-2 rounded-full bg-[#4f46e5] px-3 py-2 text-sm text-white transition hover:bg-[#4338ca] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff]">
                                <span>{{ Auth::user()->customer?->first_name ?? Auth::user()->email }}</span>
                                <svg class="h-3.5 w-3.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div id="userDropdownMenu" class="absolute right-0 mt-3 hidden w-48 rounded-2xl border border-[#dbeafe] bg-white shadow-xl">
                                <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2.5 text-sm text-gray-700 transition hover:bg-[#eef2ff]">Profile</a>
                                <form action="{{ route('customer.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-[#eef2ff]">Logout</button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
        <div id="mobileNavPanel" class="mx-auto hidden w-full max-w-7xl border-t border-[#e0e7ff] bg-white px-4 pb-6 pt-4 shadow-inner lg:hidden">
            <div class="space-y-5">
                <form action="{{ url('/search') }}" method="GET">
                    <label for="mobile-giveaway-search" class="sr-only">Search giveaways</label>
                    <input id="mobile-giveaway-search" type="text" name="query" value="{{ $searchValue }}" placeholder="Search giveaways..."
                           class="w-full rounded-full border border-[#dbeafe] px-4 py-2 text-sm placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#a6b7ff]">
                </form>

                <div class="flex items-center gap-3">
                    <a href="{{ $favoritesEnabled ? route('customer.favorites') : '#' }}"
                       class="nav-icon-button"
                       aria-label="My favorites"
                       title="My favorites"
                       @unless($favoritesEnabled) aria-disabled="true" @endunless>
                        <i class="fi fi-br-comment-heart" aria-hidden="true"></i>
                    </a>
                    <a href="{{ $cartRoute }}" class="nav-icon-button" aria-label="My cart" title="My cart">
                        <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
                    </a>
                </div>

                <ul class="space-y-2 text-sm font-semibold text-gray-700">
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center justify-between rounded-lg px-4 py-2 hover:bg-[#eef2ff] hover:text-[#4338ca]"
                           aria-current="false">
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('order.envelope') }}"
                           class="flex items-center justify-between rounded-lg px-4 py-2 hover:bg-[#eef2ff] hover:text-[#4338ca]"
                           aria-current="false">
                            <span>Envelope</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('order.giveaways') }}"
                           class="flex items-center justify-between rounded-lg px-4 py-2 bg-[#eef2ff] text-[#4338ca]"
                           aria-current="page">
                            <span>Giveaways</span>
                            <i class="bi bi-dot text-2xl"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('order.summary') }}"
                           class="flex items-center justify-between rounded-lg px-4 py-2 hover:bg-[#eef2ff] hover:text-[#4338ca]"
                           aria-current="false">
                            <span>Summary</span>
                        </a>
                    </li>
                </ul>

                @guest
                    <a href="{{ route('customer.login') }}" class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff] px-5 py-2 font-semibold text-white">
                        Sign in
                    </a>
                @endguest

                @auth
                    <div class="rounded-2xl border border-[#dbeafe] px-4 py-3 text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">{{ Auth::user()->customer?->first_name ?? Auth::user()->email }}</p>
                        <div class="mt-3 flex flex-col gap-2">
                            <a href="{{ route('customer.dashboard') }}" class="rounded-lg border border-transparent px-3 py-2 text-left transition hover:border-[#dbeafe]">Profile</a>
                            <form action="{{ route('customer.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-[#eef2ff]">Logout</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>
    <main class="giveaways-shell envelope-shell pt-24 sm:pt-28"
		  data-summary-url="{{ route('order.summary') }}"
		  data-summary-api="{{ route('order.summary.json') }}"
		  data-options-url="{{ route('api.giveaways') }}"
		  data-sync-url="{{ route('order.giveaways.store') }}"
		  data-clear-url="{{ route('order.giveaways.clear') }}"
		  data-storage-key="inkwise-finalstep"
		  data-placeholder="{{ asset('images/no-image.png') }}">
        <header class="giveaways-header envelope-header">
            <div class="giveaways-header__content envelope-header__content">
                <a href="{{ route('order.envelope') }}" class="giveaways-header__back envelope-header__back" aria-label="Back to envelope options">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
					Back to envelopes
				</a>
				<h1>Choose wedding giveaways</h1>
				<p>Curate thoughtful tokens to complement your invitation set. Pick from our handcrafted options or upload your inspiration for a custom quote.</p>
			</div>
		</header>

        <div class="giveaways-layout envelope-layout">
            <section class="giveaways-options envelope-options">
                <div class="giveaways-card envelope-card">
                    <header class="giveaway-card__header">
                        <span class="giveaway-card__badge">Giveaway catalog</span>
                    </header>
                    <div id="giveawaysGrid" class="giveaways-grid envelope-grid" role="list" aria-live="polite"></div>
                    <div class="giveaways-empty is-dynamic" id="giveawaysEmptyState" hidden>
                        <h2>No giveaways match your filters</h2>
                        <p>Adjust your search or upload inspiration below so we can craft something custom for you.</p>
                    </div>
                </div>
            </section>

            <aside class="giveaways-summary envelope-summary">
				<div class="summary-card">
					<header class="summary-card__header">
						<div>
							<h2>Your giveaways</h2>
							<p>We’ll keep your choices in sync with the rest of your order.</p>
						</div>
						<div class="flex flex-col items-end gap-2">
							<span id="giveawaysStatusBadge" class="summary-card__badge">Pending</span>
						</div>
					</header>
					<div id="giveawaySummaryBody" class="summary-card__body">
						<p class="summary-empty">Choose a giveaway to see the details here.</p>
					</div>
				</div>

				<div class="summary-actions">
					<button type="button" class="btn btn-secondary" id="skipGiveawaysBtn" data-target="{{ route('order.summary') }}">Skip giveaways</button>
					<button type="button" class="primary-action" id="giveawaysContinueBtn" data-target="{{ route('order.summary') }}" disabled>Continue to order summary</button>
				</div>
				<p class="summary-note">You can revisit this step before finalizing your order. Your progress is saved automatically.</p>
			</aside>
		</div>

		<div id="giveawayToast" class="giveaway-toast" aria-live="polite" hidden></div>
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

	<script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropdownControllers = [];
            const navToggle = document.getElementById('navToggle');
            const mobilePanel = document.getElementById('mobileNavPanel');

            const registerDropdown = ({ triggerId, menuId }) => {
                const trigger = document.getElementById(triggerId);
                const menu = document.getElementById(menuId);
                if (!trigger || !menu) {
                    return null;
                }

                const indicator = trigger.querySelector('svg');

                const open = () => {
                    menu.classList.remove('hidden');
                    trigger.setAttribute('aria-expanded', 'true');
                    indicator?.classList.add('rotate-180');
                };

                const close = () => {
                    menu.classList.add('hidden');
                    trigger.setAttribute('aria-expanded', 'false');
                    indicator?.classList.remove('rotate-180');
                };

                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const willOpen = menu.classList.contains('hidden');
                    closeAllDropdowns();
                    if (willOpen) {
                        open();
                    }
                });

                return { trigger, menu, close };
            };

            const closeAllDropdowns = () => {
                dropdownControllers.forEach((controller) => controller.close());
            };

            [
                { triggerId: 'userDropdownBtn', menuId: 'userDropdownMenu' },
            ].forEach((config) => {
                const controller = registerDropdown(config);
                if (controller) {
                    dropdownControllers.push(controller);
                }
            });

            document.addEventListener('click', (event) => {
                dropdownControllers.forEach((controller) => {
                    if (controller.menu.classList.contains('hidden')) {
                        return;
                    }
                    if (controller.menu.contains(event.target) || controller.trigger.contains(event.target)) {
                        return;
                    }
                    controller.close();
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }
                closeAllDropdowns();
                if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
                    mobilePanel.classList.add('hidden');
                    navToggle?.setAttribute('aria-expanded', 'false');
                }
            });

            navToggle?.addEventListener('click', () => {
                const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                mobilePanel?.classList.toggle('hidden', isOpen);
                if (!isOpen) {
                    closeAllDropdowns();
                }
            });

            const storageKey = 'inkwise-finalstep';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const navIcons = Array.from(document.querySelectorAll('.nav-icon-button'));

            const serverHasOrder = async () => {
                try {
                    const response = await fetch('/order/summary.json', {
                        method: 'GET',
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    return response.ok;
                } catch (error) {
                    return false;
                }
            };

            const createOrderFromSummary = async (summary) => {
                if (!summary) {
                    return false;
                }
                const productId = summary.productId ?? summary.product_id;
                if (!productId) {
                    return false;
                }
                const quantity = Number(summary.quantity ?? 10);
                try {
                    const response = await fetch('/order/cart/items', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ product_id: Number(productId), quantity }),
                    });
                    return response.ok;
                } catch (error) {
                    return false;
                }
            };

            navIcons.forEach((icon) => {
                if (icon.getAttribute('aria-disabled') === 'true') {
                    icon.setAttribute('data-was-aria-disabled', 'true');
                    icon.removeAttribute('aria-disabled');
                    icon.style.pointerEvents = 'auto';
                    icon.setAttribute('tabindex', '0');
                    icon.setAttribute('role', 'button');
                    icon.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            icon.click();
                        }
                    });
                }

                icon.addEventListener('click', async (event) => {
                    event.preventDefault();
                    try {
                        if (await serverHasOrder()) {
                            window.location.href = '/order/addtocart';
                            return;
                        }

                        let summary = null;
                        try {
                            const raw = window.sessionStorage.getItem(storageKey);
                            summary = raw ? JSON.parse(raw) : null;
                        } catch (error) {
                            summary = null;
                        }

                        if (summary && (summary.productId || summary.product_id)) {
                            const created = await createOrderFromSummary(summary);
                            if (created) {
                                window.location.href = '/order/addtocart';
                                return;
                            }
                        }

                        const href = icon.getAttribute('href');
                        if (href && href !== '#') {
                            window.location.href = href;
                            return;
                        }

                        window.location.href = '/order/addtocart';
                    } catch (error) {
                        window.location.href = '/order/addtocart';
                    }
                });
            });
        });
    </script>

    <script id="giveawayCatalogData" type="application/json">{!! $inlineCatalogJson !!}</script>
    <script src="{{ asset('js/customer/preview-modal.js') }}" defer></script>
    <script src="{{ asset('js/customer/orderflow-giveaways.js') }}" defer></script>
	@if(!empty($orderSummary))
		<script>
			document.addEventListener('DOMContentLoaded', () => {
				const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
				window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
			});
		</script>
	@endif
</body>
</html>
