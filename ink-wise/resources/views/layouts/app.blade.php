<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body class="antialiased bg-gray-50">

    <!-- Top Navigation Bar -->
    <header class="bg-white shadow flex justify-between items-center px-6 py-4">
        <h1 class="text-xl font-bold text-indigo-600">InkWise</h1>

        <nav>
            @guest
                <!-- Buttons that trigger modals -->
                <button onclick="openModal('loginModal')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Login</button>
                <button onclick="openModal('registerModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Register</button>
            @endguest

            @auth
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Dashboard</a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Logout</button>
                </form>
            @endauth
        </nav>
    </header>

    <!-- Main Content -->
    <main class="p-6">
        @yield('content')
    </main>

    <!-- 🔹 Login Modal -->
    <div id="loginModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-lg p-8 w-96 relative">
            <button onclick="closeModal('loginModal')" class="absolute top-3 right-3 text-gray-500 hover:text-black">✖</button>
            <h2 class="text-xl font-bold text-center mb-4">Login</h2>
            <form action="{{ route('login') }}" method="POST">
                @csrf
                <input type="email" name="email" placeholder="Email" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <input type="password" name="password" placeholder="Password" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">Login</button>
            </form>
        </div>
    </div>

    <!-- 🔹 Register Modal -->
    <div id="registerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-lg p-8 w-96 relative">
            <button onclick="closeModal('registerModal')" class="absolute top-3 right-3 text-gray-500 hover:text-black">✖</button>
            <h2 class="text-xl font-bold text-center mb-4">Register</h2>
            <form action="{{ route('register') }}" method="POST">
                @csrf
                <input type="text" name="name" placeholder="Full Name" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <input type="email" name="email" placeholder="Email" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <input type="password" name="password" placeholder="Password" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <input type="password" name="password_confirmation" placeholder="Confirm Password" required class="w-full mb-3 px-4 py-2 border rounded-lg">
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">Register</button>
            </form>
        </div>
    </div>

    <!-- JS for Modal -->
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>

</body>
</html>
