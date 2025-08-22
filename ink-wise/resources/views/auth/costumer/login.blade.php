<!-- resources/views/auth/Costumerlogin.blade.php -->

<div id="loginModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 relative">
        
        <!-- Close button -->
        <button id="closeLogin" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">
            ✖
        </button>

        <!-- Modal Title -->
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Sign In</h2>

        <!-- Login Form -->
        <form method="POST" action="{{ route('costumer.login') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('password')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-indigo-600">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>

            <!-- Submit -->
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition">
                Sign In
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-4">
            <hr class="flex-1 border-gray-300">
            <span class="px-2 text-sm text-gray-500">or</span>
            <hr class="flex-1 border-gray-300">
        </div>

        <!-- Google Login -->
        <a href="{{ route('google.redirect') }}"
           class="w-full flex items-center justify-center border border-gray-300 rounded-lg py-2 hover:bg-gray-50 transition">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-5 h-5 mr-2">
            Continue with Google
        </a>

        <!-- Switch to Register -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Don’t have an account? 
            <a href="#" id="openRegisterFromLogin" class="text-indigo-600 hover:underline">Register</a>
        </p>
    </div>
</div>
