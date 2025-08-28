<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Templates</title>

    {{-- Laravel vite --}}
    @vite('resources/css/costumertemplate.css')
    @vite('resources/js/costumertemplate.js')
    @vite('resources/css/costumer.css')
    @vite('resources/css/costumer.css')

</head>
<body class="antialiased bg-gray-50 birthday">


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

          <!-- Search + Sign Up / Customer Name -->

          <!-- Right Side (Search + User Dropdown) -->
        <div class="flex items-center space-x-3">
            
            {{-- Search bar only shows when NOT logged in --}}
            @guest
            <form action="{{ route('dashboard') }}" method="GET" class="hidden md:flex">
                <input type="text" name="query" placeholder="Search..."
                    class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
            </form>
            @endguest

            {{-- Authenticated User --}}
            @auth
            <div class="relative">
                <!-- User Button -->
                <button 
                    id="userDropdownBtn"
                    class="flex items-center px-5 py-2 font-semibold text-white rounded-full animate-gradient"
                    style="font-family: 'Seasons', serif;">
                    {{ Auth::user()->name }}
                    <span class="ml-1">▼</span>
                </button>

                <!-- Dropdown Menu -->
                <div 
                    id="userDropdown" 
                    class="absolute right-0 mt-2 w-40 bg-white text-black rounded-lg shadow-lg hidden">
                    
                    <!-- Logout -->
                    <form method="POST" action="{{ route('costumer.logout') }}" class="px-4 py-2">
                        @csrf
                        <button type="submit" class="block w-full text-left hover:bg-gray-100 rounded">
                            Log Out
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
                    <span class="cursive">B</span>irthday 
                    <span class="cursive">T</span>emplates
             </h1>
            <p class="page-subtitle">Choose from our elegant and creative designs made for your special day.</p>

            <div class="cards-grid">
                <!-- Invitation Card -->
                <div class="template-card" onclick="openTemplateModal('invitation')">
                    <img src="/costumerimage/happy.png" alt="Wedding Invitation" class="card-image">
                    <div class="card-overlay">
                        <h2 class="card-title">Birthday Invitations</h2>
                        <p class="card-text">Playful and stylish invitation templates for birthdays.</p>
                    </div>
                </div>

                <!-- Giveaways Card -->
                <div class="template-card" onclick="openTemplateModal('giveaways')">
                    <img src="/costumerimage/glass.png" alt="Wedding Giveaway" class="card-image">
                    <div class="card-overlay">
                        <h2 class="card-title">Birthday Giveaways</h2>
                        <p class="card-text">Exciting giveaway designs to celebrate in style.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

  
   

</body>
</html>
