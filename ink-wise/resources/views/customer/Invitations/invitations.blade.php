{{-- resources/views/customerinvitations/invitations.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Inkwise Invitations')</title>

    <!-- Fonts -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
    </style>



    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .nav-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 9999px;
            border: 1px solid #dbeafe;
            background-color: rgba(255, 255, 255, 0.9);
            color: #f472b6;
            transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 24px rgba(166, 183, 255, 0.25);
        }

        .nav-icon-button:hover {
            transform: translateY(-2px);
            background-color: #f8f5ff;
        }

        .nav-icon-button:focus {
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
    
    @stack('styles')
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">
    
    <!-- Custom JS -->
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/template.js') }}" defer></script>

    
    <!-- Alpine.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
</head>
<body class="antialiased bg-white">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-[#c7d2fe] shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <span class="text-4xl font-bold text-[#a6b7ff] transition-transform duration-200 group-hover:-translate-y-0.5"
                          style="font-family: Edwardian Script ITC;">I</span>
                    <span class="text-2xl font-bold text-gray-900" style="font-family: 'Playfair Display', serif;">nkwise</span>
                </a>

                @php
                    $invitationType = $invitationType ?? (
                        request()->routeIs('templates.wedding.invitations') ? 'Wedding' :
                        (request()->routeIs('templates.corporate.invitations') ? 'Corporate' :
                        (request()->routeIs('templates.baptism.invitations') ? 'Baptism' :
                        (request()->routeIs('templates.birthday.invitations') ? 'Birthday' : 'Invitations')))
                    );

                    $navLinks = [
                        ['label' => 'Home', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'icon' => null],
                        ['label' => 'Invitations', 'route' => route('templates.wedding.invitations'), 'active' => request()->routeIs('templates.*.invitations'), 'icon' => null],
                        ['label' => 'Giveaways', 'route' => route('templates.wedding.giveaways'), 'active' => request()->routeIs('templates.*.giveaways'), 'icon' => null],
                    ];

                    $hasFavoritesRoute = \Illuminate\Support\Facades\Route::has('customer.favorites');
                    $favoritesLink = [
                        'url' => $hasFavoritesRoute ? route('customer.favorites') : '#',
                        'disabled' => !$hasFavoritesRoute,
                        'label' => 'My favorites',
                    ];

                    $hasCartRoute = \Illuminate\Support\Facades\Route::has('customer.cart');
                    $cartLink = [
                        'url' => $hasCartRoute ? route('customer.cart') : '#',
                        'disabled' => !$hasCartRoute,
                        'label' => 'My cart',
                    ];
                @endphp

                <div class="flex items-center gap-3 lg:gap-6">
                    <button id="navToggle" class="inline-flex items-center justify-center lg:hidden w-10 h-10 rounded-full border border-[#c7d2fe] text-[#4f46e5] hover:bg-[#eef2ff] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff]"
                            aria-expanded="false" aria-controls="primaryNav">
                        <span class="sr-only">Toggle navigation</span>
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <nav id="primaryNav" class="nav-menu hidden lg:flex lg:items-center">
                        <ul class="flex flex-col lg:flex-row lg:items-center gap-5 lg:gap-7 text-sm font-semibold text-gray-700">
                            @foreach ($navLinks as $link)
                                <li>
                                    <a href="{{ $link['route'] }}"
                                       class="inline-flex items-center gap-2 px-3 py-2 rounded-full transition {{ $link['active'] ? 'text-[#4338ca] bg-[#eef2ff] shadow-sm' : 'hover:text-[#4338ca]' }}">
                                        @if(!empty($link['icon']))
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                {!! $link['icon'] !!}
                                            </svg>
                                        @endif
                                        {{ $link['label'] }}
                                    </a>
                                </li>
                            @endforeach
                            <li class="relative">
                                <button id="categoryToggle" type="button"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-transparent hover:border-[#c7d2fe] text-gray-700 hover:text-[#4338ca] transition"
                                        aria-expanded="false" aria-haspopup="true">
                                    {{ $invitationType }}
                                    <svg class="w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div id="categoryMenu" class="hidden absolute left-0 lg:left-auto lg:right-0 mt-2 w-52 rounded-2xl border border-[#dbeafe] bg-white/95 backdrop-blur shadow-xl overflow-hidden" role="menu">
                                    <a href="{{ route('templates.wedding.invitations') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]" role="menuitem">Wedding</a>
                                    <a href="{{ route('templates.corporate.invitations') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]" role="menuitem">Corporate</a>
                                    <a href="{{ route('templates.baptism.invitations') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]" role="menuitem">Baptism</a>
                                    <a href="{{ route('templates.birthday.invitations') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]" role="menuitem">Birthday</a>
                                </div>
                            </li>
                        </ul>
                    </nav>

                    <div class="flex items-center gap-3">
                        <form action="{{ url('/search') }}" method="GET" class="hidden lg:flex">
                            <label class="sr-only" for="invitation-search">Search</label>
                            <input id="invitation-search" type="text" name="query" placeholder="Search templates..."
                                   class="w-48 border border-[#dbeafe] rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#a6b7ff] focus:border-transparent placeholder:text-gray-400">
                        </form>

                        <div class="hidden lg:flex items-center gap-2">
                            <a href="{{ $favoritesLink['url'] }}"
                               class="nav-icon-button"
                               aria-label="{{ $favoritesLink['label'] }}"
                               title="{{ $favoritesLink['label'] }}"
                               @if($favoritesLink['disabled']) aria-disabled="true" @endif>
                                <i class="fi fi-br-comment-heart" aria-hidden="true"></i>
                            </a>
                            <a href="{{ $cartLink['url'] }}"
                               class="nav-icon-button"
                               aria-label="{{ $cartLink['label'] }}"
                               title="{{ $cartLink['label'] }}"
                               @if($cartLink['disabled']) aria-disabled="true" @endif>
                                <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
                            </a>
                        </div>

                        @guest
                            <a href="{{ route('customer.login') }}"
                               id="openLogin"
                               class="hidden sm:inline-flex items-center text-white px-5 py-2 font-semibold rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff] shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff]"
                               style="font-family: 'Seasons', serif;">
                                Sign in
                            </a>
                        @endguest

                        @auth
                            <div class="relative">
                                <button id="userDropdownBtn" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-full bg-[#4f46e5] text-white hover:bg-[#4338ca] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff]">
                                    {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
                                    <svg id="dropdownArrow" class="w-3.5 h-3.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div id="userDropdownMenu" class="hidden absolute right-0 mt-3 w-48 rounded-2xl border border-[#dbeafe] bg-white shadow-xl overflow-hidden">
                                    <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]">Profile</a>
                                    <form id="logout-form" action="{{ route('customer.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2.5 text-gray-700 hover:bg-[#eef2ff]">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>

            <div id="mobileNavPanel" class="lg:hidden hidden pb-6">
                <div class="flex flex-col gap-4 pt-4 border-t border-[#e0e7ff] mt-4">
                    <form action="{{ url('/search') }}" method="GET" class="flex">
                        <label class="sr-only" for="mobile-invitation-search">Search templates</label>
                        <input id="mobile-invitation-search" type="text" name="query" placeholder="Search templates..."
                               class="flex-1 border border-[#dbeafe] rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#a6b7ff] focus:border-transparent">
                    </form>

                    <div class="flex items-center gap-3">
                        <a href="{{ $favoritesLink['url'] }}"
                           class="nav-icon-button"
                           aria-label="{{ $favoritesLink['label'] }}"
                           title="{{ $favoritesLink['label'] }}"
                           @if($favoritesLink['disabled']) aria-disabled="true" @endif>
                            <i class="fi fi-br-comment-heart" aria-hidden="true"></i>
                        </a>
                        <a href="{{ $cartLink['url'] }}"
                           class="nav-icon-button"
                           aria-label="{{ $cartLink['label'] }}"
                           title="{{ $cartLink['label'] }}"
                           @if($cartLink['disabled']) aria-disabled="true" @endif>
                            <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
                        </a>
                    </div>

                    <ul class="flex flex-col gap-2 text-sm font-semibold text-gray-700">
                        @foreach ($navLinks as $link)
                            <li>
                                <a href="{{ $link['route'] }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ $link['active'] ? 'bg-[#eef2ff] text-[#4338ca]' : 'hover:bg-[#eef2ff] hover:text-[#4338ca]' }}">
                                    @if(!empty($link['icon']))
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            {!! $link['icon'] !!}
                                        </svg>
                                    @endif
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                        <li>
                            <button id="mobileCategoryToggle" type="button" aria-expanded="false" class="w-full flex items-center justify-between px-3 py-2 rounded-lg border border-[#dbeafe] text-gray-700">
                                {{ $invitationType }}
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div id="mobileCategoryMenu" class="hidden mt-2 rounded-lg border border-[#dbeafe] bg-white shadow-lg overflow-hidden">
                                <a href="{{ route('templates.wedding.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#eef2ff]">Wedding</a>
                                <a href="{{ route('templates.corporate.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#eef2ff]">Corporate</a>
                                <a href="{{ route('templates.baptism.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#eef2ff]">Baptism</a>
                                <a href="{{ route('templates.birthday.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#eef2ff]">Birthday</a>
                            </div>
                        </li>
                    </ul>

                    @guest
                        <a href="{{ route('customer.login') }}" class="inline-flex items-center justify-center text-white px-4 py-2 font-semibold rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff]">
                            Sign in
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="py-12 px-6">
        @yield('content')
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navToggle = document.getElementById('navToggle');
            const mobilePanel = document.getElementById('mobileNavPanel');
            const categoryToggle = document.getElementById('categoryToggle');
            const categoryMenu = document.getElementById('categoryMenu');
            const mobileCategoryToggle = document.getElementById('mobileCategoryToggle');
            const mobileCategoryMenu = document.getElementById('mobileCategoryMenu');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            const dropdownArrow = document.getElementById('dropdownArrow');

            const toggleHidden = (element, force) => {
                if (!element) return;
                if (typeof force === 'boolean') {
                    element.classList.toggle('hidden', force);
                } else {
                    element.classList.toggle('hidden');
                }
            };

            const hideCategoryMenu = () => {
                if (!categoryMenu) return;
                categoryMenu.classList.add('hidden');
                categoryToggle?.setAttribute('aria-expanded', 'false');
                categoryToggle?.querySelector('svg')?.classList.remove('rotate-180');
            };

            const hideMobileCategoryMenu = () => {
                if (!mobileCategoryMenu) return;
                mobileCategoryMenu.classList.add('hidden');
                mobileCategoryToggle?.setAttribute('aria-expanded', 'false');
            };

            const hideUserMenu = () => {
                if (!userDropdownMenu) return;
                userDropdownMenu.classList.add('hidden');
                dropdownArrow?.classList.remove('rotate-180');
            };

            navToggle?.addEventListener('click', () => {
                const expanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                toggleHidden(mobilePanel, expanded);
                if (!expanded) {
                    hideCategoryMenu();
                    hideMobileCategoryMenu();
                    hideUserMenu();
                }
            });

            categoryToggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = categoryMenu?.classList.contains('hidden');
                hideCategoryMenu();
                if (willOpen && categoryMenu) {
                    categoryMenu.classList.remove('hidden');
                    categoryToggle.setAttribute('aria-expanded', 'true');
                    categoryToggle.querySelector('svg')?.classList.add('rotate-180');
                }
            });

            mobileCategoryToggle?.addEventListener('click', () => {
                const willOpen = mobileCategoryMenu?.classList.contains('hidden');
                hideMobileCategoryMenu();
                if (willOpen && mobileCategoryMenu) {
                    mobileCategoryMenu.classList.remove('hidden');
                    mobileCategoryToggle.setAttribute('aria-expanded', 'true');
                }
            });

            userDropdownBtn?.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = userDropdownMenu?.classList.contains('hidden');
                hideUserMenu();
                if (willOpen && userDropdownMenu) {
                    userDropdownMenu.classList.remove('hidden');
                    dropdownArrow?.classList.add('rotate-180');
                }
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('#categoryToggle') && !event.target.closest('#categoryMenu')) {
                    hideCategoryMenu();
                }
                if (!event.target.closest('#mobileCategoryToggle') && !event.target.closest('#mobileCategoryMenu')) {
                    hideMobileCategoryMenu();
                }
                if (!event.target.closest('#userDropdownBtn') && !event.target.closest('#userDropdownMenu')) {
                    hideUserMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hideCategoryMenu();
                    hideMobileCategoryMenu();
                    hideUserMenu();
                    if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
                        mobilePanel.classList.add('hidden');
                        navToggle?.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });
    </script>
@stack('scripts')
</body>
</html>
