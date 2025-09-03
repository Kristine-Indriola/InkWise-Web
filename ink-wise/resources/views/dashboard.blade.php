
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
    <link rel="stylesheet" href="{{ asset('css/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templates.css') }}">

    <!-- Custom JS -->
    <script src="{{ asset('js/customer.js') }}" defer></script>
    <script src="{{ asset('js/customertemplate.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">



</head>
<body id="dashboard" class="antialiased bg-gray-50">

   <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center animate-bounce-slow">
           <span class="text-5xl font-bold logo-i"style="font-family: Edwardian Script ITC;" >I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden md:flex space-x-6">
            <a href="#dashboard" class="text-gray-700 hover:text-[#f6b3b2]">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-[#f6b3b2]">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-[#f6b3b2]">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-[#f6b3b2]">About</a>
            <a href="#contact" class="text-gray-700 hover:text-[#f6b3b2]">Contact</a>
        </nav>

        <!-- Search + Sign Up / customer Name -->
  <div class="flex items-center space-x-4 relative">
    <!-- Search Form -->
    <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
        <input type="text" name="query" placeholder="Search..." 
               class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
    </form>
  
   @guest('customer')
    <!-- Sign In Button -->
    <a href="{{ route('customer.login') }}"
       id="openLogin"
       class="text-white px-5 py-2 font-semibold animate-gradient rounded-full"
       style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); font-family: 'Seasons', serif;">
       Sign in
    </a>
@endguest

@auth('customer')
   <!-- User Dropdown -->
<div class="relative">
    <!-- Dropdown Button -->
    <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
        {{ Auth::guard('customer')->user()->name }}
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
</header>


<main class="py-6 px-4">
        @yield('content')
    </main>

<!-- Main Content -->
<main class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
        
        <!-- Left Content -->
        <div class="space-y-6 animate-fade-in-left">
            <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
               <span style="color: #d17c79;">Invitation</span>
               <span style="color: black;">maker</span>
            </h1>

            <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                Custom Invitations & Giveaways Crafted with Care.
            </p>

            <div class="flex space-x-4">
    <!-- Order Now -->
    <a href="{{ route('order.birthday') }}"
       class="px-6 py-3 text-white font-semibold hover:scale-105 transition-transform animate-gradient rounded-full"
       style="font-family: 'Playfair Display', serif;">
       Order Now
    </a>

    <a href="#categories"  
   class="px-6 py-3 font-semibold text-gray-800 bg-white hover:scale-105 transition-transform"
   style="border: 2px solid transparent; border-radius: 65px; 
          background-clip: padding-box, border-box; background-origin: border-box; 
          background-image: linear-gradient(white, white),  
          linear-gradient(135deg, #e97d69, #faa291, #fcb2a6, #fec5bb, #fed9d3); 
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
                        <source src="{{ asset('customerVideo/invitation.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <!-- Back (Image) -->
                <div class="flip-card-back bg-white shadow-lg rounded-2xl overflow-hidden">
                    <img src="{{ asset('customerimage/invitation.png') }}" alt="Invitation Design" class="w-full h-96 object-cover">
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
@include('customerpartials.templates')

{{-- Categories Section --}}
@include('customerpartials.categories')


{{-- About Section --}}
@include('customerpartials.about')

{{-- Contact Section --}}
@include('customerpartials.contact')




</body>
</html>
