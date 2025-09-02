<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Costumer Dashboard • Inkwise</title>

  <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        
    </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/costumer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/costumerprofile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/costumertemplates.css') }}">
    <script src="{{ asset('js/costumertemplate.js') }}" defer></script>
    <script src="{{ asset('js/costumerprofile.js') }}" defer></script>
    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
    


</head>

<body class="bg-gray-50 text-gray-800">
  <!-- Top Bar -->
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
            <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-[#f6b3b2]">Home</a>
            <a href="{{ route('templates.wedding') }}" class="text-gray-700 hover:text-[#f6b3b2]">Wedding</a>
                <a href="{{ route('templates.birthday') }}" class="text-gray-700 hover:text-[#f6b3b2]">Birthday</a>
                <a href="{{ route('templates.baptism') }}" class="text-gray-700 hover:text-[#f6b3b2]">Baptism</a>
                <a href="{{ route('templates.corporate') }}" class="text-gray-700 hover:text-[#f6b3b2]">Corporate</a>
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
               style="font-family: 'Seasons', serif;">
               Sign in
            </a>
            @endguest

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
        <a href="#" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <!-- user icon -->
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15a1.5 1.5 0 0 0 1.5-1.5C21 16.5 17 14 12 14Z"/></svg>
          <span class="font-medium">Edit Profile</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z"/></svg>
          <span class="font-medium">My Orders</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3h10a2 2 0 0 1 2 2v14l-7-3-7 3V5a2 2 0 0 1 2-2z"/></svg>
          <span class="font-medium">Order History</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 4h14v2H5zm0 7h14v2H5zm0 7h9v2H5z"/></svg>
          <span class="font-medium">Saved Design</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-1 2h2v3h-2z"/></svg>
          <span class="font-medium">FeedBack & Ratings</span>
        </a>
        <form method="POST" action="{{ route('costumer.logout', []) ?? '#' }}">
          @csrf
          <button type="submit" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8a2 2 0 0 0-2 2v3h2V5h8v14h-8v-3h-2v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
            <span class="font-medium">Log Out</span>
          </button>
        </form>
      </nav>
    </aside>

    <!-- Main Card -->
    <section class="md:col-span-4">
      <div class="card bg-white p-6 md:p-8 border border-gray-100">
        <h2 class="text-xl font-semibold mb-6">Profile Photo</h2>

        <!-- Photo + buttons -->
        <div class="flex items-center gap-5">
          <div id="avatarWrap" class="w-24 h-24 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center">
            <img id="avatarImg"
                 src="{{ asset('images/default-avatar.png') }}"
                 alt="Profile"
                 class="w-full h-full object-cover hidden"
                 onerror="this.classList.add('hidden');" />
            <span id="avatarFallback" class="text-3xl text-gray-400">👤</span>
          </div>

          <div class="flex gap-3">
            <button id="removePhoto"
              class="px-4 py-2 rounded-xl border border-gray-300 hover:bg-gray-50">
              Remove Photo
            </button>

            <label class="px-4 py-2 rounded-xl border border-gray-300 hover:bg-gray-50 cursor-pointer">
              Change photo
              <input id="photoInput" type="file" accept="image/*" class="hidden">
            </label>
          </div>
        </div>

        <form method="POST" action="{{ route('costumer.profile.update') }}" class="mt-8 space-y-4">
          @csrf
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-600">Name</label>
              <input type="text" name="name" placeholder="Enter FullName"
                     class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm text-gray-600">Email</label>
                <input type="email" name="email" placeholder="Enter Email Address"
                       class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
              </div>
              <div>
                <label class="text-sm text-gray-600">Phone Number</label>
                <input type="text" name="phone" placeholder="Enter Phone Number"
                       class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
              </div>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">Delivery Address</label>
              <input type="text" name="address" placeholder="Enter Address"
                     class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
            </div>
            <div>
              <label class="text-sm text-gray-600">New Password</label>
              <input type="password" name="password" placeholder="Enter New Password"
                     class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
            </div>
            <div>
              <label class="text-sm text-gray-600">Confirm password</label>
              <input type="password" name="password_confirmation" placeholder="Enter Confirm Password"
                     class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
            </div>
          </div>

          <div class="pt-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:opacity-95">
              Update Profile
            </button>
            @if (session('status'))
              <span class="ml-3 text-sm text-green-600">{{ session('status') }}</span>
            @endif
          </div>
        </form>
      </div>
    </section>
  </main>

  <!-- Help bubble -->
  <div id="help" class="fixed bottom-5 right-5 bg-white help-bubble p-4 border">
    <div class="flex items-center justify-between">
      <div class="font-semibold text-sm">Need Help? <span class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-1"></span></div>
      <button id="helpClose" class="text-gray-400 hover:text-gray-600" aria-label="Close">
        ✕
      </button>
    </div>
    <p class="text-xs text-gray-600 mt-2">You can ask me how to change your name, password, or profile photo.</p>
    <button class="mt-3 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs">Start Help</button>
  </div>

</body>
</html>
