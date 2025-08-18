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
                <!-- Sign In Button -->
                <a href="{{ route('register') }}" 
                   class="text-white px-5 py-2 font-semibold animate-gradient"
                   style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); border-radius: 65px; font-family: 'Seasons', serif;">
                   Sign in
                </a>
            @else
                <!-- Customer Dropdown -->
                <div class="relative group">
                    <button class="flex items-center space-x-2 px-5 py-2 text-white font-semibold rounded-full animate-gradient"
                            style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                        {{ auth()->user()->name }}
                        <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-40 bg-white shadow-lg rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-50">
                        <a href="{{ route('logout') }}" 
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log out</a>
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

    <!-- Floating Sign In / Join Box -->
    <div id="signupModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8 relative animate-fade-in-up">
            <!-- Close Button -->
            <button id="closeSignup" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">&times;</button>

            <!-- Title -->
            <h2 class="text-3xl font-bold mb-6 text-center" style="color:#8c52ff; font-family:'Playfair Display', serif;">
                Join Inkwise
            </h2>

            <!-- Form -->
            <form action="{{ url('/register') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
                </div>

                <div class="flex items-start space-x-2 text-sm">
                    <input type="checkbox" required class="mt-1">
                    <span>By signing up, I accept InkWise <a href="{{ url('/terms') }}" class="text-indigo-600">Terms of Use</a> & <a href="{{ url('/privacy') }}" class="text-indigo-600">Privacy Policy</a>.</span>
                </div>

                <button type="button" 
                        class="w-full flex items-center justify-center space-x-2 border border-gray-300 rounded-lg py-2 hover:bg-gray-50">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
                    <span class="text-gray-700 font-medium">Log in with Google</span>
                </button>

                <button type="submit" 
                        class="w-full py-3 text-white font-semibold rounded-lg animate-gradient"
                        style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                    Create Account
                </button>

                <p class="text-sm text-center text-gray-600">
                    Already have an account? 
                    <a href="{{ url('/login') }}" class="text-indigo-600 hover:underline">Login</a>
                </p>
            </form>
        </div>
    </div>

<!-- Categories Section -->
<section id="categories" class="py-16 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-sky-400 to-blue-600 animate-gradient-x"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-center mb-12 text-white drop-shadow-lg">Categories</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float">
        <img src="image/baptism.png" alt="Baptism" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Baptism</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-200">
        <img src="image/wedding.png" alt="Wedding" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Wedding</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-400">
        <img src="image/corporate.png" alt="Corporate" class="w-full h-48 object-cover">
        <div class="p-4 text-center">
          <h3 class="text-lg font-semibold text-gray-800">Corporate</h3>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300 animate-float delay-600">
        <img src="image/birthday.png" alt="Birthday" class="w-full h-48 object-cover">
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
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white shadow-md rounded-lg p-4 hover:shadow-xl transition-transform hover:scale-105">Template 1</div>
            <div class="bg-white shadow-md rounded-lg p-4 hover:shadow-xl transition-transform hover:scale-105">Template 2</div>
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
<section id="contact" class="py-16 bg-gradient-to-r from-indigo-500 to-blue-600 text-white">
    <!-- Contact Us Section -->
<section id="contact" class="py-16 bg-white shadow-inner">
  <div class="max-w-6xl mx-auto px-6">
    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Contact Us</h2>
    <p class="text-center text-gray-600 mb-10">
      Have questions or want to place a custom order? Reach out to us anytime!
    </p>
    
    <div class="grid md:grid-cols-2 gap-10">
      <!-- Contact Info -->
      <div class="bg-gradient-to-r from-blue-500 to-skyblue-500 p-8 rounded-2xl shadow-lg text-white">
        <h3 class="text-xl font-semibold mb-4">Merwen Printing Services – InkWise</h3>
        <p class="mb-3"><strong>📍 Address:</strong> 123 Rue de Paris, 75001 Paris, France</p>
        <p class="mb-3"><strong>📞 Phone:</strong> +33 1 23 45 67 89</p>
        <p class="mb-3"><strong>✉️ Email:</strong> support@inkwise-paris.com</p>
        <p><strong>🕒 Business Hours:</strong><br>Monday – Saturday: 9:00 AM – 7:00 PM</p>
      </div>

    <div class="max-w-5xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-6">Contact Us</h2>
        <form class="space-y-4 max-w-xl mx-auto animate-fade-in-up">
            <input type="text" placeholder="Your Name" class="w-full p-3 border rounded-lg text-black">
            <input type="email" placeholder="Your Email" class="w-full p-3 border rounded-lg text-black">
            <textarea placeholder="Your Message" rows="4" class="w-full p-3 border rounded-lg text-black"></textarea>
            <button class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Send Message</button>
        </form>
    </div>
</section>
<div class="flex items-center space-x-4">
    @auth
        <span class="text-gray-700 font-semibold">Hello, {{ auth()->user()->email }}</span>
        <a href="{{ route('logout') }}" class="text-white px-5 py-2 font-semibold animate-gradient" style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); border-radius: 65px;">Logout</a>
    @else
        <a href="{{ route('register') }}" class="text-white px-5 py-2 font-semibold animate-gradient" style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); border-radius: 65px;">Sign in</a>
    @endauth
</div>
<div id="signupModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8 relative animate-fade-in-up">
        <!-- Close Button -->
        <button id="closeSignup" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">&times;</button>

        <!-- Title -->
        <h2 class="text-3xl font-bold mb-6 text-center" style="color:#8c52ff; font-family:'Playfair Display', serif;">
            Join Inkwise
        </h2>

        <!-- Form -->
        <form action="{{ url('/register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your name"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
            </div>

            <div class="flex items-start space-x-2 text-sm">
                <input type="checkbox" required class="mt-1">
                <span>By signing up, I accept InkWise <a href="{{ url('/terms') }}" class="text-indigo-600">Terms of Use</a> & <a href="{{ url('/privacy') }}" class="text-indigo-600">Privacy Policy</a>.</span>
            </div>
<a href="{{ route('google.login') }}" 
   class="w-full flex items-center justify-center space-x-2 border border-gray-300 rounded-lg py-2 hover:bg-gray-50">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
    <span class="text-gray-700 font-medium">Log in with Google</span>
</a>

            <button type="button" 
                    class="w-full flex items-center justify-center space-x-2 border border-gray-300 rounded-lg py-2 hover:bg-gray-50">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
                <span class="text-gray-700 font-medium">Log in with Google</span>
            </button>

            <button type="submit" 
                    class="w-full py-3 text-white font-semibold rounded-lg animate-gradient"
                    style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                Create Account
            </button>

            <p class="text-sm text-center text-gray-600">
                Already have an account? 
                <a href="{{ url('/login') }}" class="text-indigo-600 hover:underline">Login</a>
            </p>
        </form>
    </div>
</div>




<style>
@keyframes gradient-x {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}
.animate-gradient-x {
  background-size: 200% 200%;
  animation: gradient-x 8s ease infinite;
}

@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fade-in-up 1s ease forwards; }

@keyframes fade-in-left {
  from { opacity: 0; transform: translateX(-20px); }
  to { opacity: 1; transform: translateX(0); }
}
.animate-fade-in-left { animation: fade-in-left 1s ease forwards; }

@keyframes fade-in-right {
  from { opacity: 0; transform: translateX(20px); }
  to { opacity: 1; transform: translateX(0); }
}
.animate-fade-in-right { animation: fade-in-right 1s ease forwards; }

@keyframes fade-in-down {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fade-in-down 1s ease forwards; }

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.animate-float { animation: float 3s ease-in-out infinite; }
.animate-float.delay-200 { animation-delay: 0.2s; }
.animate-float.delay-400 { animation-delay: 0.4s; }
.animate-float.delay-600 { animation-delay: 0.6s; }

@keyframes gradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
.animate-gradient {
  background-size: 200% 200%;
  animation: gradient 5s ease infinite;
}

/* Flip Card Styles */
.flip-card { perspective: 1000px; }
.flip-card-inner {
    position: relative;
    width: 100%;
    height: 24rem;
    text-align: center;
    transition: transform 0.8s;
    transform-style: preserve-3d;
}
.flip-card:hover .flip-card-inner,
.flip-card.flipped .flip-card-inner { transform: rotateY(180deg); }
.flip-card-front, .flip-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 1rem;
}
.flip-card-back { transform: rotateY(180deg); }

html { scroll-behavior: smooth; }
</style>

<script>
    document.querySelectorAll('.flip-card').forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('flipped');
        });
    });

    const modal = document.getElementById('signupModal');
    const openBtn = document.getElementById('openSignup');
    const closeBtn = document.getElementById('closeSignup');

    openBtn.addEventListener('click', (e) => {
        e.preventDefault();
        modal.classList.remove('hidden');
    });
    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
</script>
</body>
</html
