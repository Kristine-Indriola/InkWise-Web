<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>


   <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
   </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->

    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">

    <!-- Custom JS -->
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
    <script src="{{ asset('js/customer/template.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
      <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
</head>
<body id="dashboard" class="antialiased bg-white">

    
   <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down bg-white w-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center animate-bounce-slow flex-shrink-0">
            <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC; color:#06b6d4;">I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #0891b2;">nkwise</span>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden md:flex flex-wrap space-x-6">
            <a href="#dashboard" class="text-gray-700 hover:text-[#06b6d4]">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-[#06b6d4]">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-[#06b6d4]">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-[#06b6d4]">About</a>
            <a href="#contact" class="text-gray-700 hover:text-[#06b6d4]">Contact</a>
        </nav>

        <!-- Search + Sign Up / customer Name -->

<div class="flex items-center space-x-4 relative min-w-0">
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
        <!-- Dropdown Menu -->
        <div class="relative min-w-0 group">
            <!-- Dropdown Button -->
            <button id="userDropdownBtn" type="button"
                class="flex items-center px-3 py-2 bg-[#e0f7fa] rounded hover:bg-[#06b6d4] hover:text-white min-w-0 max-w-[140px] overflow-hidden transition-colors duration-200 focus:outline-none">
                <span class="truncate">
                    {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
                </span>
            </button>

            <!-- Dropdown Menu -->
            <div id="userDropdownMenu"
                 class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
                <!-- Profile -->
                <a href="{{ route('customer.profile.update') }}"
                   class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                    My Account
                </a>
                <!-- My Purchase (no link) -->
                <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                    My Purchase
                </div>
                <!-- My Favorites (no link) -->
                <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                    My Favorites
                </div>
                <!-- Logout -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                        Logout
                    </button>
                </form>
            </div>

        <div class="flex items-center space-x-4 relative min-w-0">
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
    <div class="relative min-w-0 group">
        <button id="userDropdownBtn" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
            {{-- Display customer's name or fallback --}}
            <span>{{ Auth::user()->customer?->first_name ?? Auth::user()->email ?? 'Customer' }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="userDropdownMenu"
             class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
            <!-- Profile -->
            <a href="{{ route('customer.profile.index') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                My Account
            </a>
            <!-- My Purchase (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                My Purchase
            </div>
            <!-- My Favorites (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                My Favorites
            </div>
            <!-- Logout -->
            <form method="POST" action="{{ route('customer.logout') }}">
    @csrf
    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">Logout</button>
</form>

        </div>
    @endauth
</div>
</header>


<main class="py-6 px-4">
        @yield('content')
    </main>

<!-- Main Content -->
<main class="py-12 bg-white min-h-screen">
    <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
        
        <!-- Left Content -->
        <div class="space-y-6 animate-fade-in-left">
            <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
               <span style="color: #06b6d4;">Invitation</span>
               <span style="color: #0891b2;">maker</span>
            </h1>

            <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                Custom Invitations & Giveaways Crafted with Care.
            </p>

            <div class="flex space-x-4">
    <!-- Order Now -->
    <a href="{{ route('templates.wedding.invitations') }}"
       class="px-6 py-3 text-white font-semibold hover:scale-105 transition-transform rounded-full"
       style="background:#06b6d4; font-family: 'Playfair Display', serif;">
       Order Now
    </a>

    <a href="#categories"  
   class="px-6 py-3 font-semibold text-gray-800 bg-white hover:scale-105 transition-transform"
   style="border: 2px solid #06b6d4; border-radius: 65px; 
          background-clip: padding-box, border-box; background-origin: border-box; 
          background-image: linear-gradient(white, white),  
          linear-gradient(135deg, #06b6d4, #0891b2); 
          font-family: 'Playfair Display', serif;">
   View Design
</a>
</div>

        </div>

        <!-- Right Content: Flip Card -->
        <div class="flip-card animate-fade-in-right">
            <div class="flip-card-inner">
                <!-- Front (Video) -->
                <div class="flip-card-front bg-white shadow-lg rounded-4x3 overflow-hidden flex items-center justify-center">
                    <video class="w-full h-96 object-cover rounded-2xl" autoplay loop muted>
                        <source src="{{ asset('customerVideo/Video/invitation.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <!-- Back (Image) -->
                <div class="flip-card-back bg-white shadow-lg rounded-2xl overflow-hidden">
                    <img src="{{ asset('customerimages/image/invitation.png') }}" alt="Invitation Design" class="w-full h-96 object-cover">
                </div>
            </div>
        </div>
    </div>
</main>

{{-- include modals --}}
{{-- Login and Register Modals --}}
@include('auth.customer.login')
@include('auth.customer.register')

{{-- Templates Section --}}
@include('customer.partials.templates')

{{-- Categories Section --}}
@include('customer.partials.categories')

{{-- About Section --}}
@include('customer.partials.about')

{{-- Contact Section --}}
@include('customer.partials.contact')
</body>
</html>
