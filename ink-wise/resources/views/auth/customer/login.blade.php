@php
    $showLoginModal = $errors->has('email') || $errors->has('password') || session('auth_error') || session('error');
@endphp

<div id="loginModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 px-2 {{ $showLoginModal ? '' : 'hidden' }}">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 relative transform transition-all scale-95 hover:scale-100 duration-300">

        <!-- Close button -->
        <button id="closeLogin" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition text-base font-bold">
            ✖
        </button>

        <!-- Modal Header -->
        <div class="text-center mb-5">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Sign In
            </h2>
            <p class="text-gray-500 text-sm mt-1">Welcome back! Please enter your details.</p>
        </div>

        <!-- Login Form -->
        <form method="POST" action="{{ route('customer.login') }}" class="space-y-4">
            @csrf

            @if (session('auth_error'))
            <p class="text-sm text-red-500">{{ session('auth_error') }}</p>
            @endif

            @if (session('error'))
            <p class="text-sm text-red-500">{{ session('error') }}</p>
            @endif

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                @error('email')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                @error('password')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Forgot Password -->
            <div class="text-right">
                <a href="{{ route('customer.password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot Password?</a>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-indigo-600">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                Sign In
            </button>
        </form> 

        {{-- Third-party login coming soon --}}

        <!-- Switch to Register -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Don’t have an account? 
            <a href="{{ route('customer.register') }}" id="openRegisterFromLogin" class="text-indigo-600 hover:underline">Register</a>
        </p>
    </div>
</div>
