<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>InkWise | @yield('title', 'Home')</title>

    {{-- Tailwind and Laravel Vite --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    {{-- Custom Styles --}}
    <!-- CSS -->
<link rel="stylesheet" href="{{ asset('css/costumertemplates.css') }}">

<!-- JS -->
<script src="{{ asset('js/costumertemplates.js') }}"></script>


</head>
<body class="antialiased bg-gray-50 text-gray-800">

    <!-- ======================= Navbar ======================= -->
    <header class="bg-white shadow-md fixed w-full top-0 left-0 z-50">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center h-16">
            <!-- Logo -->
            <a href="/" class="text-2xl font-bold text-indigo-700">InkWise</a>

            <!-- Navigation -->
            <nav class="hidden md:flex gap-6">
                <a href="/" class="text-gray-700 hover:text-indigo-600 transition">Home</a>
                <a href="/templates" class="text-gray-700 hover:text-indigo-600 transition">Templates</a>
                <a href="/about" class="text-gray-700 hover:text-indigo-600 transition">About</a>
                <a href="/contact" class="text-gray-700 hover:text-indigo-600 transition">Contact</a>
            </nav>

            <!-- Auth Section -->
            <div>
                @auth
                    <!-- If logged in -->
                    <div class="relative group">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-full shadow hover:bg-indigo-700 transition">
                            {{ Auth::user()->name }}
                        </button>
                        <div class="absolute right-0 mt-2 w-40 bg-white border rounded-md shadow-lg hidden group-hover:block">
                            <a href="/dashboard" class="block px-4 py-2 hover:bg-gray-100">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- If guest -->
                    <a href="/login" class="bg-indigo-600 text-white px-4 py-2 rounded-full shadow hover:bg-indigo-700 transition">
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Spacer to prevent content from hiding behind navbar -->
    <div class="h-16"></div>

    <!-- ======================= Main Content ======================= -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    <!-- ======================= Footer ======================= -->
    <footer class="bg-indigo-700 text-white py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <p>&copy; {{ date('Y') }} InkWise. All rights reserved.</p>
            <div class="flex gap-4">
                <a href="#" class="hover:text-gray-300">Facebook</a>
                <a href="#" class="hover:text-gray-300">Instagram</a>
                <a href="#" class="hover:text-gray-300">LinkedIn</a>
            </div>
        </div>
    </footer>

    {{-- Custom Scripts --}}
    <script src="{{ asset('js/templates.js') }}"></script>
</body>
</html>
