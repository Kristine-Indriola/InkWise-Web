<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>customerprofile Dashboard • Inkwise</title>
  

  <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        
    </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customerprofile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
    <script src="{{ asset('js/customer/customerprofile.js') }}" defer></script>
    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
    
</head>

<body class="bg-gray-50 text-gray-800">
  <!-- Top Bar -->
  <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 w-full">
        <!-- Logo aligned left -->
        <div class="flex items-center animate-bounce-slow flex-shrink-0">
            <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC; color:#06b6d4;">I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #0891b2;">nkwise</span>
        </div>

        <!-- Navigation Links centered -->
        <nav class="hidden md:flex space-x-6 mx-auto">
            <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-[#06b6d4]">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-[#06b6d4]">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-[#06b6d4]">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-[#06b6d4]">About</a>
            <a href="#contact" class="text-gray-700 hover:text-[#06b6d4]">Contact</a>
        </nav>

        <!-- Sign In / User Dropdown aligned right -->
        <div class="flex items-center space-x-4 relative">
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
                <div class="relative group">
                    <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-[#e0f7fa] rounded hover:bg-[#06b6d4] hover:text-white">
                        {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
                    </button>
                    <div id="userDropdownMenu"
                         class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
                        <a href="{{ route('customerprofile.profile') }}"

                           class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">
                            Profile
                        </a>
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
      <!-- Welcome Section -->
<div class="welcome-section">
    <h1>Welcome to InkWise</h1>
    <p>Your Custom Invitations & Giveaways Hub</p>
</div>

  <!-- Layout -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-5 gap-6">
    <!-- Sidebar -->
    <aside class="sidebar rounded-2xl p-4 md:col-span-1 h-full">
      <nav class="space-y-2">
        <!-- My Account Dropdown -->
        <div x-data="{ open: true }" class="relative">
          <!-- Dropdown is open by default (open: true) -->
          <button @click="open = !open"
                  class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left font-medium bg-[#e0f7fa]">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15a1.5 1.5 0 0 0 1.5-1.5C21 16.5 17 14 12 14Z"/></svg>
            My Account
            <svg class="w-4 h-4 ml-auto transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
          </button>
          <div x-show="open" @click.away="open = false" class="mt-1 ml-6 space-y-1">
            <!-- Profile (with link) -->
            <a href="{{ route('customerprofile.profile') }}"

               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Profile
            </a>
            <!-- Addresses (with link) -->
            <a href="{{ route('customerprofile.addresses') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Addresses
            </a>
            <!-- My Favorites (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition cursor-pointer">
              My Favorites
            </div>
          </div>
        </div>
        <!-- Other Sidebar Items -->
        <a href="{{ route('customer.my_purchase') }}" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z"/></svg>
          <span class="font-medium">My Purchase</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3h10a2 2 0 0 1 2 2v14l-7-3-7 3V5a2 2 0 0 1 2-2z"/></svg>
          <span class="font-medium">Order History</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition relative group">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
    <path d="M5 4h14v2H5zm0 7h14v2H5zm0 7h9v2H5z"/>
  </svg>
  <span class="font-medium">Settings</span>
  <svg class="w-4 h-4 ml-auto transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
  <!-- Dropdown -->
  <div class="absolute left-0 top-full mt-1 w-48 bg-white rounded shadow-lg z-10 hidden group-hover:block">
    <a href="{{ route('customerprofile.settings', ['tab' => 'account']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Account Settings
    </a>
    <a href="{{ route('customerprofile.settings', ['tab' => 'privacy']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Privacy Settings
    </a>
  </div>
</a>

        <form method="POST" action="{{ route('customer.logout')}}">
          @csrf
          <button type="submit" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8a2 2 0 0 0-2 2v3h2V5h8v14h-8v-3h-2v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
            <span class="font-medium">Log Out</span>
          </button>
        </form>
      </nav>
    </aside>
    <!-- Main Content Area -->
    <section class="md:col-span-4">
      @yield('content')
    </section>
  </main>
</body>
</html>
