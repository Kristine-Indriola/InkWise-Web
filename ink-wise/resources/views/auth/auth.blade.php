<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise - Auth</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body class="antialiased bg-gray-50">

    <!-- Top Navigation -->
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-indigo-600">InkWise</h1>
        <nav class="space-x-4">
            <button @click="showLogin = true"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700">
                Login
            </button>
            <button @click="showRegister = true"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg shadow hover:bg-gray-300">
                Register
            </button>
        </nav>
    </header>

    <!-- Alpine State -->
    <div x-data="{ showLogin: false, showRegister: false }">

        <!-- Background Overlay -->
        <div x-show="showLogin || showRegister" 
             class="fixed inset-0 bg-black bg-opacity-50 z-40"
             x-transition.opacity></div>

        <!-- LOGIN MODAL -->
        <div x-show="showLogin" 
             class="fixed inset-0 flex items-center justify-center z-50"
             x-transition>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">
                <button @click="showLogin = false" 
                        class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">&times;</button>
                <h2 class="text-2xl font-bold text-indigo-600 mb-4">Login</h2>
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Password</label>
                        <input type="password" name="password" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 rounded-lg shadow hover:bg-indigo-700">
                        Login
                    </button>
                </form>
            </div>
        </div>

        <!-- REGISTER MODAL -->
        <div x-show="showRegister" 
             class="fixed inset-0 flex items-center justify-center z-50"
             x-transition>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">
                <button @click="showRegister = false" 
                        class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">&times;</button>
                <h2 class="text-2xl font-bold text-indigo-600 mb-4">Register</h2>
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input type="text" name="name" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Password</label>
                        <input type="password" name="password" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300">
                    </div>
                    <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 rounded-lg shadow hover:bg-indigo-700">
                        Register
                    </button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>
