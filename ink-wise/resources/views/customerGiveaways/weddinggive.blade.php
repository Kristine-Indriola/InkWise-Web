<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Giveaways</title>
   
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
    </style>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customertemplate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templates.css') }}">

    <!-- Custom JS -->
    <script src="{{ asset('js/customertemplate.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>

<!-- JS -->
<script src="{{ asset('js/customertemplate.js') }}"></script>
<script src="{{ asset('js/customer.js') }}" defer></script>

</head>
<body id="wedding-give" class="antialiased bg-gray-50 wedding">

    <!-- Dashboard Navbar/Header -->
    <header class="bg-white shadow animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-20">
            
           <!-- Logo -->
           <div class="flex items-center animate-bounce-slow">
               <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC;">I</span>
               <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
           </div>

            <!-- Navigation -->
            <nav class="space-x-4 flex items-center">
                <a href="{{ route('dashboard') }}" class="nav-link">Home</a>
                <a href="{{ route('templates.wedding') }}" class="nav-link active">Wedding</a>
                <a href="{{ route('templates.wedding.invitations') }}" class="nav-link active">Invitation</a>
                <a href="{{ route('templates.wedding.giveaways') }}" class="nav-link">Giveaways</a>
            </nav>

            <!-- Right Side (Search + User Dropdown) -->
            <div class="flex items-center space-x-3">
                
                {{-- Search bar only shows when NOT logged in --}}
                @guest
                <form action="{{ route('dashboard') }}" method="GET" class="hidden md:flex">
                    <input type="text" name="query" placeholder="Search..."
                        class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
                </form>
                @endguest

                 @auth('customer')
   <!-- User Dropdown -->
<div class="relative">
    <!-- Dropdown Button -->
    <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
        {{ Auth::guard('customer')->user()->name }}
    </button>
    <!-- Dropdown Menu -->
    <div id="userDropdownMenu"
         class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg hidden">
        <!-- Profile -->
        <a href="{{ route('customerprofile.dashboard') }}"
           class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
            Profile
        </a>

        <!-- Logout -->
        <a href="#"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
            Logout
        </a>
    </div>
</div>

<!-- Hidden logout form -->
<form id="logout-form" action="{{ route('customer.logout') }}" method="POST" class="hidden">
    @csrf
</form>

    
@endauth
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="py-12 px-6 text-center">
        <h1 class="page-title">
            <span class="cursive">W</span>edding 
            <span class="cursive">G</span>iveaways
        </h1>
        <p class="page-subtitle mb-10">Choose from our curated selection of elegant giveaway designs.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">

            <!-- Giveaway 1 -->
            <div class="template-card shadow-lg rounded-2xl overflow-hidden bg-white hover:shadow-2xl transition">
                <img src="/customerimage/glass.png" alt="Glass Souvenir" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="card-title text-lg font-semibold">Elegant Glass Souvenir</h2>
                    <p class="text-sm text-gray-600 mb-3">Crystal-clear glasses etched with love.</p>
                    <div class="flex space-x-2 justify-center mb-3">
                        <span class="w-5 h-5 rounded-full bg-blue-400"></span>
                        <span class="w-5 h-5 rounded-full bg-pink-300"></span>
                        <span class="w-5 h-5 rounded-full bg-gold-400"></span>
                    </div>
                    <button class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600">Choose Template</button>
                </div>
            </div>

            <!-- Giveaway 2 -->
            <div class="template-card shadow-lg rounded-2xl overflow-hidden bg-white hover:shadow-2xl transition">
                <img src="/customerimage/pouch.png" alt="Wedding Pouch" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="card-title text-lg font-semibold">Custom Fabric Pouches</h2>
                    <p class="text-sm text-gray-600 mb-3">Soft, stylish pouches with embroidery.</p>
                    <div class="flex space-x-2 justify-center mb-3">
                        <span class="w-5 h-5 rounded-full bg-beige-300"></span>
                        <span class="w-5 h-5 rounded-full bg-brown-500"></span>
                        <span class="w-5 h-5 rounded-full bg-gray-700"></span>
                    </div>
                    <button class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600">Choose Template</button>
                </div>
            </div>

            <!-- Giveaway 3 -->
            <div class="template-card shadow-lg rounded-2xl overflow-hidden bg-white hover:shadow-2xl transition">
                <img src="/customerimage/candle.png" alt="Wedding Candle" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="card-title text-lg font-semibold">Scented Candle Favors</h2>
                    <p class="text-sm text-gray-600 mb-3">Romantic candle scents for a cozy touch.</p>
                    <div class="flex space-x-2 justify-center mb-3">
                        <span class="w-5 h-5 rounded-full bg-white"></span>
                        <span class="w-5 h-5 rounded-full bg-rose-400"></span>
                        <span class="w-5 h-5 rounded-full bg-lavender"></span>
                    </div>
                    <button class="px-4 py-2 bg-rose-500 text-white rounded-lg hover:bg-rose-600">Choose Template</button>
                </div>
            </div>

            <!-- Giveaway 4 -->
            <div class="template-card shadow-lg rounded-2xl overflow-hidden bg-white hover:shadow-2xl transition">
                <img src="/customerimage/keychain.png" alt="Wedding Keychain" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="card-title text-lg font-semibold">Personalized Keychains</h2>
                    <p class="text-sm text-gray-600 mb-3">Practical keepsakes engraved with initials.</p>
                    <div class="flex space-x-2 justify-center mb-3">
                        <span class="w-5 h-5 rounded-full bg-black"></span>
                        <span class="w-5 h-5 rounded-full bg-silver"></span>
                        <span class="w-5 h-5 rounded-full bg-gold-500"></span>
                    </div>
                    <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Choose Template</button>
                </div>
            </div>

            <!-- Giveaway 5 -->
            <div class="template-card shadow-lg rounded-2xl overflow-hidden bg-white hover:shadow-2xl transition">
                <img src="/customerimage/mini-plant.png" alt="Mini Plant" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="card-title text-lg font-semibold">Mini Plant Souvenirs</h2>
                    <p class="text-sm text-gray-600 mb-3">Eco-friendly gifts that grow with love.</p>
                    <div class="flex space-x-2 justify-center mb-3">
                        <span class="w-5 h-5 rounded-full bg-green-500"></span>
                        <span class="w-5 h-5 rounded-full bg-brown-400"></span>
                        <span class="w-5 h-5 rounded-full bg-white"></span>
                    </div>
                    <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Choose Template</button>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
