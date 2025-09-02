

<div id="registerModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm sm:max-w-md p-6 relative transform transition-all scale-95">

        <!-- Close button -->
        <button id="closeRegister"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            ✖
        </button>

        <!-- Modal Title -->
        <h2 class="text-xl font-bold text-center text-gray-800 mb-5">Create Account</h2>

        <!-- Register Form -->
        <form method="POST" action="{{ route('costumer.register') }}">
    @csrf
    <div>
        <label for="name">Name</label>
        <input type="text" name="name" required>
    </div>

    <div>
        <label for="email">Email</label>
        <input type="email" name="email" required>
    </div>

    <div>
        <label for="password">Password</label>
        <input type="password" name="password" required>
    </div>

    <div>
        <label for="password_confirmation">Confirm Password</label>
        <input type="password" name="password_confirmation" required>
    </div>

    <button type="submit">Register</button>
</form>
        <!-- Submit -->
        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition">
            Sign Up
        </button>

        <!-- Divider -->
        <div class="flex items-center my-4">
            <hr class="flex-1 border-gray-300">
            <span class="px-2 text-sm text-gray-500">or</span>
            <hr class="flex-1 border-gray-300">
        </div>

        <!-- Google Login -->
        <a href="{{ route('google.redirect') }}"
            class="w-full h-10 flex items-center justify-center border border-gray-300 rounded-lg 
                   hover:bg-gray-50 transition text-base">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-5 h-5 mr-2">
            Continue with Google
        </a>

        <!-- Switch to Login -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Already have an account?
            <a href="#" id="openLoginFromRegister" class="text-indigo-600 hover:underline">Sign In</a>
        </p>
    </div>
</div>
