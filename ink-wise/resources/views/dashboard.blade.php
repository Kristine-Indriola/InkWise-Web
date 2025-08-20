<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>
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
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #5271ff;">nkwise</span>
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
<div class="flex items-center space-x-4 relative">
    <!-- Search Form -->
    <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
        <input type="text" name="query" placeholder="Search..." 
               class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
    </form>

    @guest
        <!-- Sign In Button (redirects to login page or opens modal) -->
    <a href="{{ route('login') }}" 
       id="openLogin"
       class="text-white px-5 py-2 font-semibold animate-gradient rounded-full"
       style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); font-family: 'Seasons', serif;">
       Sign in
    </a>
@else
    <!-- User Dropdown -->
    <div class="relative">
        <button id="userDropdownBtn" class="flex items-center">
            {{ Auth::user()->name }} <span class="ml-1">▼</span>
        </button>
        <div id="userDropdown" class="absolute right-0 mt-2 w-40 bg-white text-black rounded-lg shadow-lg hidden">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block px-4 py-2 w-full text-left">Log Out</button>
            </form>
        </div>
    </div>
@endguest

</div>
    </div>
</header>


    <!-- Main Content -->
    <main class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
            
            <!-- Left Content -->
            <div class="space-y-6 animate-fade-in-left">
                <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
                   <span style="color: #8c52ff;">Invitation</span>
                   <span style="color: black;">make</span>
                </h1>

                <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                    Custom Invitations & Giveaways Crafted with Care.
                </p>

                <div class="flex space-x-4">
                    <a href="{{ url('/order') }}" 
                       class="px-6 py-3 text-white font-semibold hover:scale-105 transition-transform animate-gradient"
                       style="background: linear-gradient(to right, #b2fefa, #0ed2f7); border-radius: 65px; font-family: 'Playfair Display', serif;">
                       Order Now
                    </a>
                    <a href="{{ url('/design/1') }}" 
                       class="px-6 py-3 font-semibold text-gray-800 bg-white hover:scale-105 transition-transform"
                       style="border: 2px solid transparent; border-radius: 65px; 
                              background-clip: padding-box, border-box; background-origin: border-box; 
                              background-image: linear-gradient(white, white),  linear-gradient(to right, #b2fefa, #0ed2f7); 
                              font-family: 'Playfair Display', serif;">
                       View Design
                    </a>
                </div>
            </div>

            <!-- Right Content: Flip Card -->
            <div class="flip-card animate-fade-in-right">
                <div class="flip-card-inner">
                    <!-- Front (Video) -->
                    <div class="flip-card-front bg-white shadow-lg rounded-2xl overflow-hidden flex items-center justify-center">
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

    @extends('layouts.app')
<!-- LOGIN MODAL -->   
    <div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md transform transition-all scale-95 animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">Login</h2>
                <button id="closeLogin" class="text-gray-400 hover:text-gray-600">✖</button>
            </div>
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="email" name="email" placeholder="Email"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <input type="password" name="password" placeholder="Password"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Login
                </button>
            </form>

            <!-- Google login -->
            <div class="mt-4">
                <a href="{{ route('google.redirect') }}"
                   class="flex items-center justify-center w-full px-4 py-2 border rounded-lg bg-white hover:bg-gray-100">
                    <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google"
                         class="w-5 h-5 mr-2">
                    <span class="text-gray-700">Continue with Google</span>
                </a>
            </div>
            <p class="mt-6 text-sm text-gray-600 text-center">
                Don’t have an account?
                <button id="openRegisterFromLogin" class="text-indigo-600 hover:underline">
                    Register
                </button>
            </p>
        </div>
    </div>
    <!-- REGISTER MODAL -->     @vite('resources/css/app.css')
    <div id="registerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md transform transition-all scale-95 animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">Register</h2>
                <button id="closeRegister" class="text-gray-400 hover:text-gray-600">✖</button>
            </div>
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <input type="text" name="name" placeholder="Full Name"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <input type="email" name="email" placeholder="Email"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <input type="password" name="password" placeholder="Password"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <input type="password" name="password_confirmation" placeholder="Confirm Password"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Register
                </button>
            </form>
            <!-- Google register -->
            <div class="mt-4">
                <a href="{{ route('google.redirect') }}"
                   class="flex items-center justify-center w-full px-4 py-2 border rounded-lg bg-white hover:bg-gray-100">
                    <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google"
                         class="w-5 h-5 mr-2">
                    <span class="text-gray-700">Sign up with Google</span>
                </a>
            </div>

            <p class="mt-6 text-sm text-gray-600 text-center">
                Already have an account?
                <button id="openLoginFromRegister" class="text-indigo-600 hover:underline">
                    Login
                </button>
            </p>
        </div>
    </div>
    



<!-- Categories Section -->
<section id="categories" class="py-16 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-sky-400 to-blue-600 animate-gradient-x"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-center mb-12 text-white drop-shadow-lg">Categories</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float">
        <img src="image/baptism.png" alt="Baptism Category" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Baptism</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-200">
        <img src="image/wedding.png" alt="Wedding Category" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Wedding</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-400">
        <img src="image/corporate.png" alt="Corporate Category" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Corporate</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-600">
        <img src="image/birthday.png" alt="Birthday Category" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Birthday</h3>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-16">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-6">Templates</h2>
        <p class="text-lg text-gray-600">Browse through our ready-made templates designed for all occasions.</p>
        
        <!-- Center templates -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-6 justify-center">
            <div class="bg-white shadow-md rounded-2xl p-6 hover:shadow-2xl transition-transform hover:scale-105">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Template 1</h3>
                <img src="{{ asset('image/template1.png') }}" alt="Template 1 Preview" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-sm text-gray-600">Elegant wedding invitation with modern design.</p>
            </div>
            <div class="bg-white shadow-md rounded-2xl p-6 hover:shadow-2xl transition-transform hover:scale-105">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Template 2</h3>
                <img src="{{ asset('image/template2.png') }}" alt="Template 2 Preview" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-sm text-gray-600">Colorful birthday theme with playful style.</p>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-16 bg-gray-50 animate-fade-in-up">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-6">About Us</h2>
        <p class="text-lg text-gray-700">
            InkWise is your trusted partner in creating elegant and personalized invitations and giveaways. 
            Our mission is to bring your special moments to life with creativity and style.
        </p>
    </div>
</section>


<!-- Contact Section -->
 
<section id="contact" class="py-16 bg-white shadow-inner">
  <div class="max-w-6xl mx-auto px-6">
    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Contact Us</h2>
    <p class="text-center text-gray-600 mb-10">
      Have questions or want to place a custom order? Reach out to us anytime!
    </p>
    
    <div class="grid md:grid-cols-2 gap-10">
      <!-- Contact Info -->
      <div class="bg-gradient-to-r from-blue-500 to-sky-500 p-8 rounded-2xl shadow-lg text-white">
        <h3 class="text-xl font-semibold mb-4">Merwen Printing Services – InkWise</h3>
        <p class="mb-3"><strong>📍 Address:</strong> 123 Rue de Paris, 75001 Paris, France</p>
        <p class="mb-3"><strong>📞 Phone:</strong> +33 1 23 45 67 89</p>
        <p class="mb-3"><strong>✉️ Email:</strong> support@inkwise-paris.com</p>
        <p><strong>🕒 Business Hours:</strong><br>Monday – Saturday: 9:00 AM – 7:00 PM</p>
      </div>

      <!-- Contact Form -->
      <form class="space-y-4 bg-white p-6 rounded-2xl shadow-lg" method="POST" action="#">
        <input type="text" placeholder="Your Name" class="w-full p-3 border rounded-lg text-black">
        <input type="email" placeholder="Your Email" class="w-full p-3 border rounded-lg text-black">
        <textarea placeholder="Your Message" rows="4" class="w-full p-3 border rounded-lg text-black"></textarea>
        <button class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Send Message</button>
      </form>
    </div>
  </div>
</section>

</body>
</html>
