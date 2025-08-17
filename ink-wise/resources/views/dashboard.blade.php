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
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <span class="text-5xl font-bold" style="font-family: Edwardian Script ITC; color: #8c52ff;">I</span>
                <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #5271ff;">nkwise</span>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden md:flex space-x-6">
                <a href="{{ url('/das') }}" class="text-gray-700 hover:text-indigo-600">Home</a>
                <a href="{{ url('/categories') }}" class="text-gray-700 hover:text-indigo-600">Categories</a>
                <a href="{{ url('/templates') }}" class="text-gray-700 hover:text-indigo-600">Templates</a>
                <a href="{{ url('/about') }}" class="text-gray-700 hover:text-indigo-600">About</a>
                <a href="{{ url('/contact') }}" class="text-gray-700 hover:text-indigo-600">Contact</a>
            </nav>

            <!-- Search + Sign Up -->
            <div class="flex items-center space-x-4">
                <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
                    <input type="text" name="query" placeholder="Search..." 
                           class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
                </form>
                <!-- Sign Up Button -->
                <a href="#" id="openSignup" 
                   class="text-white px-5 py-2 font-semibold"
                   style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); border-radius: 65px; font-family: 'Seasons', serif;">
                   Sign in
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
            
            <!-- Left Content -->
            <div class="space-y-6">
                <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
                   <span style="color: #8c52ff;">Invitation</span>
                <span style="color: black;">make</span>
               </h1>

                <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                    Custom Invitations & Giveaways Crafted with Care.
                </p>

                <div class="flex space-x-4">
                    <a href="{{ url('/order') }}" 
                       class="px-6 py-3 text-white font-semibold"
                       style="background: linear-gradient(90deg, #8c52ff, #5ce1e6); border-radius: 65px; font-family: 'Playfair Display', serif;">
                       Order Now
                    </a>
                    <a href="{{ url('/design/1') }}" 
                       class="px-6 py-3 font-semibold text-gray-800 bg-white"
                       style="border: 2px solid transparent; border-radius: 65px; 
                              background-clip: padding-box, border-box; background-origin: border-box; 
                              background-image: linear-gradient(white, white), linear-gradient(90deg, #8c52ff, #5ce1e6); 
                              font-family: 'Playfair Display', serif;">
                       View Design
                    </a>
                </div>
            </div>

            <!-- Right Content: Flip Card -->
            <div class="flip-card">
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
        <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8 relative">
            <!-- Close Button -->
            <button id="closeSignup" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">&times;</button>

            <!-- Title -->
            <h2 class="text-3xl font-bold mb-6 text-center" style="color:#8c52ff; font-family:'Playfair Display', serif;">
                Join Inkwise
            </h2>

            <!-- Form -->
            <form action="{{ url('/register') }}" method="POST" class="space-y-4">
                @csrf
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3">
                </div>

                <!-- Terms -->
                <div class="flex items-start space-x-2 text-sm">
                    <input type="checkbox" required class="mt-1">
                    <span>By signing up, I accept InkWise <a href="{{ url('/terms') }}" class="text-indigo-600">Terms of Use</a> & <a href="{{ url('/privacy') }}" class="text-indigo-600">Privacy Policy</a>.</span>
                </div>

                <!-- Google Button -->
                <button type="button" 
                        class="w-full flex items-center justify-center space-x-2 border border-gray-300 rounded-lg py-2 hover:bg-gray-50">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
                    <span class="text-gray-700 font-medium">Log in with Google</span>
                </button>

                <!-- Create Account -->
                <button type="submit" 
                        class="w-full py-3 text-white font-semibold rounded-lg"
                        style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                    Create Account
                </button>

                <!-- Already have account -->
                <p class="text-sm text-center text-gray-600">
                    Already have an account? 
                    <a href="{{ url('/login') }}" class="text-indigo-600 hover:underline">Login</a>
                </p>
            </form>
        </div>
    </div>


    <!-- Flip Card Styles -->
    <style>
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
        .flip-card.flipped .flip-card-inner {
            transform: rotateY(180deg);
        }
        .flip-card-front, .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 1rem;
        }
        .flip-card-back { transform: rotateY(180deg); }
    </style>

    <script>
        document.querySelectorAll('.flip-card').forEach(card => {
            card.addEventListener('click', () => {
                card.classList.toggle('flipped');
            });
        });

        // Modal logic
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
</html>
