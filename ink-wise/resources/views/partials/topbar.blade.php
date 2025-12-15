{{-- resources/views/partials/topbar.blade.php --}}
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

<header class="fixed top-0 z-50 w-full border-b border-[#c7d2fe] bg-white/95 backdrop-blur shadow-sm" style="z-index:9999;">
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
                           class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition {{ request()->routeIs('dashboard') ? 'bg-[#eef2ff] text-[#4338ca] shadow-sm' : 'hover:text-[#4338ca]' }}"
                           aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ $currentEventRoutes['invitations'] }}"
                           class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition {{ request()->routeIs('templates.' . $currentEventKey . '.invitations') ? 'bg-[#eef2ff] text-[#4338ca] shadow-sm' : 'hover:text-[#4338ca]' }}"
                           aria-current="{{ request()->routeIs('templates.' . $currentEventKey . '.invitations') ? 'page' : 'false' }}">
                            Invitations
                        </a>
                    </li>
                    <li>
                        <a href="{{ $currentEventRoutes['giveaways'] }}"
                           class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition {{ request()->routeIs('templates.' . $currentEventKey . '.giveaways') ? 'bg-[#eef2ff] text-[#4338ca] shadow-sm' : 'hover:text-[#4338ca]' }}"
                           aria-current="{{ request()->routeIs('templates.' . $currentEventKey . '.giveaways') ? 'page' : 'false' }}">
                            Giveaways
                        </a>
                    </li>
                    <li class="relative">
                        <button id="categoryToggle" type="button" aria-haspopup="true" aria-expanded="false"
                                class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-gray-700 transition hover:border-[#c7d2fe] hover:text-[#4338ca]">
                            {{ $resolvedInvitationType }}
                            <svg class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div id="categoryMenu" class="absolute left-0 right-auto mt-2 hidden w-56 rounded-2xl border border-[#dbeafe] bg-white/95 backdrop-blur shadow-2xl lg:right-0">
                            <ul>
                                @foreach ($navLinks as $category)
                                    <li>
                                        <a href="{{ $category['route'] }}"
                                           class="flex items-center justify-between px-5 py-2.5 text-sm text-gray-700 transition hover:bg-[#eef2ff] {{ $category['isActive'] ? 'font-semibold text-[#4338ca]' : '' }}">
                                            <span>{{ $category['label'] }}</span>
                                            @if($category['isActive'])
                                                <span class="text-xs text-[#4338ca]">Current</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="flex items-center gap-3">
                <form action="{{ url('/search') }}" method="GET" class="hidden lg:flex">
                    <label for="desktop-invitation-search" class="sr-only">Search templates</label>
                    <input id="desktop-invitation-search" type="text" name="query" value="{{ $searchValue }}" placeholder="Search templates..."
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
                <label for="mobile-invitation-search" class="sr-only">Search templates</label>
                <input id="mobile-invitation-search" type="text" name="query" value="{{ $searchValue }}" placeholder="Search templates..."
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
                       class="flex items-center justify-between rounded-lg px-4 py-2 {{ request()->routeIs('dashboard') ? 'bg-[#eef2ff] text-[#4338ca]' : 'hover:bg-[#eef2ff] hover:text-[#4338ca]' }}"
                       aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                        <span>Home</span>
                        @if(request()->routeIs('dashboard'))
                            <i class="bi bi-dot text-2xl"></i>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="{{ $currentEventRoutes['invitations'] }}"
                       class="flex items-center justify-between rounded-lg px-4 py-2 {{ request()->routeIs('templates.' . $currentEventKey . '.invitations') ? 'bg-[#eef2ff] text-[#4338ca]' : 'hover:bg-[#eef2ff] hover:text-[#4338ca]' }}"
                       aria-current="{{ request()->routeIs('templates.' . $currentEventKey . '.invitations') ? 'page' : 'false' }}">
                        <span>Invitations</span>
                        @if(request()->routeIs('templates.' . $currentEventKey . '.invitations'))
                            <i class="bi bi-dot text-2xl"></i>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="{{ $currentEventRoutes['giveaways'] }}"
                       class="flex items-center justify-between rounded-lg px-4 py-2 {{ request()->routeIs('templates.' . $currentEventKey . '.giveaways') ? 'bg-[#eef2ff] text-[#4338ca]' : 'hover:bg-[#eef2ff] hover:text-[#4338ca]' }}"
                       aria-current="{{ request()->routeIs('templates.' . $currentEventKey . '.giveaways') ? 'page' : 'false' }}">
                        <span>Giveaways</span>
                        @if(request()->routeIs('templates.' . $currentEventKey . '.giveaways'))
                            <i class="bi bi-dot text-2xl"></i>
                        @endif
                    </a>
                </li>
                <li>
                    <button id="mobileCategoryToggle" type="button" aria-expanded="false"
                            class="flex w-full items-center justify-between rounded-lg border border-[#dbeafe] px-4 py-2 text-gray-700">
                        {{ $resolvedInvitationType }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div id="mobileCategoryMenu" class="mt-2 hidden rounded-xl border border-[#dbeafe] bg-white shadow-lg">
                        @foreach ($navLinks as $category)
                            <a href="{{ $category['route'] }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 transition hover:bg-[#eef2ff] {{ $category['isActive'] ? 'font-semibold text-[#4338ca]' : '' }}">
                                <span>{{ $category['label'] }}</span>
                                @if($category['isActive'])
                                    <i class="bi bi-check-lg"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
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
            { triggerId: 'categoryToggle', menuId: 'categoryMenu' },
            { triggerId: 'mobileCategoryToggle', menuId: 'mobileCategoryMenu' },
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
