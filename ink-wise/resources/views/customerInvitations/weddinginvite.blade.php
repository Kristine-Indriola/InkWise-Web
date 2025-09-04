<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Invitation Templates</title>

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
    <script src="{{ asset('js/customer.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>

<!-- JS -->
<script src="{{ asset('js/customertemplate.js') }}"></script>


</head>
<body class="antialiased bg-gray-50 wedding">

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
                <a href="{{ route('templates.wedding.invitations') }}" class="nav-link active">Invitations</a>
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

                @auth
    <div class="relative">
        <!-- Dropdown Button -->
        <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
            {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
            <span id="dropdownArrow" class="ml-1 transition-transform">▼</span>
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
            <form id="logout-form" action="{{ route('customer.logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">
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
    <main class="py-12 px-6 text-center">
        <h1 class="page-title">
            <span class="cursive">W</span>edding 
            <span class="cursive">I</span>nvitations
        </h1>
        <p class="page-subtitle mb-10">Choose from our curated selection of elegant giveaway designs.</p>

        <!-- Templates grid (5 samples) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">

            <!-- Card component: copy for each sample -->
            <!-- Sample 1 – Classic Florals -->
            <article class="group bg-white rounded-2xl shadow hover:shadow-2xl transition p-4">
                <figure class="overflow-hidden rounded-xl">
                    <img src="{{ asset('customerimage/wedding/invite1.jpg') }}" alt="Classic Florals"
                         class="w-full h-80 object-cover transform group-hover:scale-105 transition duration-500">
                </figure>
                <div class="pt-4 text-center">
                    <h3 class="text-lg font-semibold editable" contenteditable="true">Classic Florals</h3>
                    <p class="text-sm text-gray-500 editable" contenteditable="true">Elegant serif + soft florals</p>


                    <!-- color swatches -->
<div class="flex justify-center gap-3 mt-3 color-swatches">
    <span class="w-5 h-5 rounded-full border bg-rose-400 cursor-pointer" data-color="#fda4af"></span>
    <span class="w-5 h-5 rounded-full border bg-emerald-400 cursor-pointer" data-color="#34d399"></span>
    <span class="w-5 h-5 rounded-full border bg-slate-700 cursor-pointer" data-color="#334155"></span>
</div>


                    <a href="#"
                       class="mt-4 inline-block px-4 py-2 rounded-lg border border-indigo-400 text-indigo-600 font-semibold hover:bg-indigo-50 transition">
                        Choose Template
                    </a>
                </div>
            </article>

            <!-- Sample 2 – Minimal Monogram -->
            <article class="group bg-white rounded-2xl shadow hover:shadow-2xl transition p-4">
                <figure class="overflow-hidden rounded-xl">
                    <img src="{{ asset('customerimage/wedding/invite2.jpg') }}" alt="Minimal Monogram"
                         class="w-full h-80 object-cover transform group-hover:scale-105 transition duration-500">
                </figure>
                <div class="pt-4 text-center">
                    <h3 class="text-lg font-semibold">Minimal Monogram</h3>
                    <p class="text-sm text-gray-500">Clean layout with initials</p>
                    <div class="flex justify-center gap-3 mt-3">
                        <span class="w-5 h-5 rounded-full border bg-indigo-500"></span>
                        <span class="w-5 h-5 rounded-full border bg-amber-400"></span>
                        <span class="w-5 h-5 rounded-full border bg-neutral-800"></span>
                    </div>
                    <a href="#"
                       class="mt-4 inline-block px-4 py-2 rounded-lg border border-indigo-400 text-indigo-600 font-semibold hover:bg-indigo-50 transition">
                        Choose Template
                    </a>
                </div>
            </article>

            <!-- Sample 3 – Sunset Save-the-Date -->
            <article class="group bg-white rounded-2xl shadow hover:shadow-2xl transition p-4">
                <figure class="overflow-hidden rounded-xl">
                    <img src="{{ asset('customerimage/invite3.png') }}" alt="Sunset Save the Date"
                         class="w-full h-80 object-cover transform group-hover:scale-105 transition duration-500">
                </figure>
                <div class="pt-4 text-center">
                    <h3 class="text-lg font-semibold">Sunset Save-the-Date</h3>
                    <p class="text-sm text-gray-500">Warm tones + script headline</p>
                    <div class="flex justify-center gap-3 mt-3">
                        <span class="w-5 h-5 rounded-full border bg-orange-400"></span>
                        <span class="w-5 h-5 rounded-full border bg-blue-500"></span>
                        <span class="w-5 h-5 rounded-full border bg-pink-500"></span>
                    </div>
                    <a href="#"
                       class="mt-4 inline-block px-4 py-2 rounded-lg border border-indigo-400 text-indigo-600 font-semibold hover:bg-indigo-50 transition">
                        Choose Template
                    </a>
                </div>
            </article>

            <!-- Sample 4 – Modern Arch -->
            <article class="group bg-white rounded-2xl shadow hover:shadow-2xl transition p-4">
                <figure class="overflow-hidden rounded-xl">
                    <img src="{{ asset('customerimage/wedding/invite4.jpg') }}" alt="Modern Arch"
                         class="w-full h-80 object-cover transform group-hover:scale-105 transition duration-500">
                </figure>
                <div class="pt-4 text-center">
                    <h3 class="text-lg font-semibold">Modern Arch</h3>
                    <p class="text-sm text-gray-500">Arched photo cutout</p>
                    <div class="flex justify-center gap-3 mt-3">
                        <span class="w-5 h-5 rounded-full border bg-teal-400"></span>
                        <span class="w-5 h-5 rounded-full border bg-fuchsia-500"></span>
                        <span class="w-5 h-5 rounded-full border bg-stone-700"></span>
                    </div>
                    <a href="#"
                       class="mt-4 inline-block px-4 py-2 rounded-lg border border-indigo-400 text-indigo-600 font-semibold hover:bg-indigo-50 transition">
                        Choose Template
                    </a>
                </div>
            </article>

            <!-- Sample 5 – Luxe Foil Look -->
            <article class="group bg-white rounded-2xl shadow hover:shadow-2xl transition p-4">
                <figure class="overflow-hidden rounded-xl">
                    <img src="{{ asset('customerimage/wedding/invite5.jpg') }}" alt="Luxe Foil Look"
                         class="w-full h-80 object-cover transform group-hover:scale-105 transition duration-500">
                </figure>
                <div class="pt-4 text-center">
                    <h3 class="text-lg font-semibold">Luxe Foil Look</h3>
                    <p class="text-sm text-gray-500">Dark background + gold accents</p>
                    <div class="flex justify-center gap-3 mt-3">
                        <span class="w-5 h-5 rounded-full border bg-yellow-500"></span>
                        <span class="w-5 h-5 rounded-full border bg-violet-500"></span>
                        <span class="w-5 h-5 rounded-full border bg-lime-400"></span>
                    </div>
                    <a href="#"
                       class="mt-4 inline-block px-4 py-2 rounded-lg border border-indigo-400 text-indigo-600 font-semibold hover:bg-indigo-50 transition">
                        Choose Template
                    </a>
                </div>
            </article>

        </div>

       
    </main>

</body>
</html>
