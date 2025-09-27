<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Templates</title>
   
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
    </style>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customertemplate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templates.css') }}">

    <!-- Custom JS -->
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>

</head>
<body class="antialiased bg-gray-50 corporate">


    <!-- Dashboard Navbar/Header -->
    <header class="bg-white shadow animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-20">
            
           <div class="flex items-center animate-bounce-slow">
           <span class="text-5xl font-bold logo-i"style="font-family: Edwardian Script ITC;" >I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
        </div>

            <!-- Navigation -->
            <nav class="space-x-4 flex items-center">
                <a href="{{ route('dashboard') }}" class="nav-link">Home</a>
                <a href="{{ route('templates.wedding') }}" class="nav-link active">Wedding</a>
                <a href="{{ route('templates.birthday') }}" class="nav-link">Birthday</a>
                <a href="{{ route('templates.baptism') }}" class="nav-link">Baptism</a>
                <a href="{{ route('templates.corporate') }}" class="nav-link">Corporate</a>
            </nav>

          <!-- Search + Sign Up / customer Name -->

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
            <a href="{{ route('customer.dashboard') }}"
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


            </nav>
        </div>
    </header>

    <!-- Page Content -->
    <main class="py-8 px-4">
        <div class="template-container text-center ">
           <h1 class="page-title">
                    <span class="cursive">C</span>orporate 
                    <span class="cursive">T</span>emplates
             </h1>
            <p class="page-subtitle">Choose from our elegant and creative designs made for your special day.</p>

            <div class="cards-grid">
                <!-- Invitation Card -->
                <div class="template-card" onclick="openTemplateModal('invitation')">
                    <img src="/customerimages/image/corporateinvite.png" alt="Wedding Invitation" class="card-image">
                    <div class="card-overlay">
                        <h2 class="card-title">Corporate  Invitations</h2>
                        <p class="card-text">Elegant and professional invitations for company events.</p>
                    </div>
                </div>

                <!-- Giveaways Card -->
                <div class="template-card" onclick="openTemplateModal('giveaways')">
                    <img src="/customerimages/image/corporategive.png" alt="Wedding Giveaway" class="card-image">
                    <div class="card-overlay">
                        <h2 class="card-title">Corporate  Giveaways</h2>
                        <p class="card-text">Smart and practical giveaways for business partners.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

  
   

</body>
</html>
