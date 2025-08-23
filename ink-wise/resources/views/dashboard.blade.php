
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>
   @vite('resources/css/costumer.css')
    @vite('resources/js/costumer.js')
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body class="antialiased bg-gray-50">

   <!-- Top Navigation Bar -->
<header class="bg-white shadow animate-fade-in-down">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center animate-bounce-slow">
            <span class="text-5xl font-bold" style="font-family: Edwardian Script ITC; color: #8c52ff;">I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden md:flex space-x-6">
            <a href="#dashboard" class="text-gray-700 hover:text-indigo-600 transition">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-indigo-600 transition">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-indigo-600 transition">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-indigo-600 transition">About</a>
            <a href="#contact" class="text-gray-700 hover:text-indigo-600 transition">Contact</a>
        </nav>

        <!-- Search + Sign Up / Customer Name -->
  <div class="flex items-center space-x-3">
      <form action="{{ route('dashboard') }}" method="GET" class="hidden md:flex">
        <input type="text" name="query" placeholder="Search..."
          class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
      </form>

      @guest
    <a href="#" id="openLogin"
       class="text-white px-5 py-2 font-semibold animate-gradient rounded-full"
       style="background: #f6b3b2; font-family: 'Seasons', serif;">
       Sign in
    </a>
@endguest

@auth
<div class="relative">
    <!-- User Button -->
    <button 
        id="userDropdownBtn"
        class="flex items-center px-5 py-2 font-semibold text-white rounded-full animate-gradient"
        style="background: #f6b3b2; font-family: 'Seasons', serif;">
        {{ Auth::user()->name }}
        <span class="ml-1">▼</span>
    </button>

    <!-- Dropdown Menu -->
    <div 
        id="userDropdown" 
        class="absolute right-0 mt-2 w-40 bg-white text-black rounded-lg shadow-lg hidden">
        <form method="POST" action="{{ route('costumer.logout') }}" class="px-4 py-2">
            @csrf
            <button type="submit" class="block w-full text-left hover:bg-gray-100 rounded">
                Log Out
            </button>
        </form>
    </div>
</div>
@endauth

    </div>
  </div>
</header>


    <!-- Main Content -->
    <main class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
            
            <!-- Left Content -->
            <div class="space-y-6 animate-fade-in-left">
                <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
                   <span style="color: #f6b3b2;">Invitation</span>
                   <span style="color: black;">maker</span>
                </h1>

                <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                    Custom Invitations & Giveaways Crafted with Care.
                </p>

                <div class="flex space-x-4">
                    <a href="{{ url('/order') }}" 
                       class="px-6 py-3 text-white font-semibold hover:scale-105 transition-transform animate-gradient"
                       style="background: #f6b3b2; border-radius: 65px; font-family: 'Playfair Display', serif;">
                       Order Now
                    </a>
                    <a href="{{ url('/design/1') }}" 
                       class="px-6 py-3 font-semibold text-gray-800 bg-white hover:scale-105 transition-transform"
                       style="border: 2px solid transparent; border-radius: 65px; 
                              background-clip: padding-box, border-box; background-origin: border-box; 
                              background-image: linear-gradient(white, white),  linear-gradient(to right, #f6b3b2); 
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
                            <source src="{{ asset('Video/invitation.mp4') }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <!-- Back (Image) -->
                    <div class="flip-card-back bg-white shadow-lg rounded-2xl overflow-hidden">
                        <img src="{{ asset('image/invitation.png') }}" alt="Invitation Design" class="w-full h-96 object-cover">
                    </div>
                </div>
            </div>
        </div>
    </main>
    
   {{-- include modals --}}
@include('auth.costumer.login')
@include('auth.costumer.register')

    {{-- Categories Section --}}
    @include('Costumerpartials.categories')

    {{-- Templates Section --}}
    @include('Costumerpartials.templates')

    {{-- About Section --}}
    @include('Costumerpartials.about')

    {{-- Contact Section --}}
    @include('Costumerpartials.contact')

</body>
</html>
