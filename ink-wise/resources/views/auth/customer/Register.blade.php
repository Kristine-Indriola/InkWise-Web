<div id="registerModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-5 relative">

        <!-- Close button -->
        <button id="closeRegister" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">
            ✖
        </button>

        <!-- Modal Title -->
        <h2 class="text-xl font-bold text-center text-gray-800 mb-4">Create Account</h2>

        <!-- Register Form -->
        <form method="POST" action="{{ route('customer.register') }}" class="space-y-3">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm 
                              focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-sm">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm 
                              focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-sm">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm 
                              focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-sm">
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm 
                              focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-sm">
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded-lg shadow-md transition text-sm">
                Sign Up
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-3">
            <hr class="flex-1 border-gray-300">
            <span class="px-2 text-xs text-gray-500">or</span>
            <hr class="flex-1 border-gray-300">
        </div>

        <!-- Google Register -->
        <a href="{{ route('google.redirect') }}"
           class="w-full flex items-center justify-center border border-gray-300 rounded-lg py-2 hover:bg-gray-50 transition text-sm">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-4 h-4 mr-2">
            Continue with Google
        </a>

        <!-- Switch to Login -->
        <p class="text-center text-xs text-gray-600 mt-3">
            Already have an account?
            <a href="#" id="openLoginFromRegister" class="text-indigo-600 hover:underline">Sign In</a>
        </p>
    </div>
</div>
