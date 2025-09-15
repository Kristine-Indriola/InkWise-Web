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
    <header class="shadow animate-fade-in-down bg-white border-b-2 border-[#06b6d4]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">

            <!-- Logo -->
            <div class="flex items-center animate-bounce-slow">
                <span class="text-5xl font-bold logo-i text-[#06b6d4]" style="font-family: Edwardian Script ITC;">I</span>
                <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
            </div>

            <!-- Navigation Links -->
            @php
                // Detect current invitation type from route or a passed variable
                $invitationType = $invitationType ?? (
                    request()->routeIs('templates.wedding.invitations') ? 'Wedding' :
                    (request()->routeIs('templates.corporate.invitations') ? 'Corporate' :
                    (request()->routeIs('templates.baptism.invitations') ? 'Baptism' :
                    (request()->routeIs('templates.birthday.invitations') ? 'Birthday' : 'Invitations')))
                );
            @endphp

            <nav class="hidden md:flex space-x-6">
                <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-[#06b6d4]">Home</a>
                <!-- Categories Dropdown -->
                <div class="relative group">
                    <button class="flex items-center text-gray-700 hover:text-[#06b6d4] transition font-semibold focus:outline-none">
                        {{ $invitationType }}
                        <span class="ml-1">▼</span>
                    </button>
                    <div
                        class="absolute left-0 mt-2 w-48 bg-white border border-[#e0f7fa] rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
                        style="top:100%;">
                        <a href="{{ route('templates.wedding.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">Wedding</a>
                        <a href="{{ route('templates.corporate.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">Corporate</a>
                        <a href="{{ route('templates.baptism.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">Baptism</a>
                        <a href="{{ route('templates.birthday.invitations') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">Birthday</a>
                    </div>
                </div>
                <a href="{{ route('templates.wedding.invitations') }}" class="text-[#06b6d4] font-bold border-b-2 border-[#06b6d4]">Invitations</a>
                <a href="{{ route('templates.wedding.giveaways') }}" class="text-gray-700 hover:text-[#06b6d4]">Giveaways</a>
            </nav>

            <!-- Search + Sign Up / Customer Name -->
            <div class="flex items-center space-x-4 relative">
                <!-- Search Form -->
                <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
                    <input type="text" name="query" placeholder="Search..." 
                           class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-[#06b6d4]">
                </form>
            
                {{-- If not logged in --}}
                @guest
                    <a href="{{ route('customer.login') }}"
                       id="openLogin"
                       class="text-white px-5 py-2 font-semibold animate-ocean rounded-full"
                       style="font-family: 'Seasons', serif;">
                       Sign in
                    </a>
                @endguest

                {{-- If logged in --}}
                @auth
                    <div class="relative">
                        <!-- Dropdown Button -->
                        <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-[#06b6d4] hover:bg-[#0891b2] rounded text-white font-semibold">
                            {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
                            <span id="dropdownArrow" class="ml-1 transition-transform">▼</span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userDropdownMenu"
                             class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg hidden border border-[#e0f7fa]">
                            <a href="{{ route('customerprofile.dashboard') }}"
                               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">Profile</a>

                            <!-- Logout -->
                            <form id="logout-form" action="{{ route('customer.logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="py-12 px-6">
        @yield('content')
    </main>
@stack('scripts')
</body>
</html>
