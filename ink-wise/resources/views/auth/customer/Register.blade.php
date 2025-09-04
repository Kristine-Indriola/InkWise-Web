<div id="registerModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-2">
    <div
        class="bg-white rounded-md shadow-md w-full max-w-xs p-4 relative transform transition-all">

        <!-- Close button -->
        <button id="closeRegister"
            class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 transition text-sm">
            ✖
        </button>

        <!-- Modal Title -->
        <h2 class="text-base font-semibold text-center text-gray-800 mb-3">Create Account</h2>

        <!-- Register Form -->
        <form method="POST" action="{{ route('customer.register') }}" class="space-y-2">
            @csrf

            <div>
                <label for="first_name" class="block text-xs font-medium text-gray-700">First Name</label>
                <input type="text" name="first_name" required
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="middle_name" class="block text-xs font-medium text-gray-700">Middle Name</label>
                <input type="text" name="middle_name"
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="last_name" class="block text-xs font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" required
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="contact_number" class="block text-xs font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number"
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-gray-700">Email</label>
                <input type="email" name="email" required
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-gray-700">Password</label>
                <input type="password" name="password" required
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                    class="mt-0.5 w-full px-2 py-1 border rounded focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Submit -->
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-1.5 rounded shadow-sm transition text-xs">
                Sign Up
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-2">
            <hr class="flex-1 border-gray-300">
            <span class="px-1 text-[10px] text-gray-500">or</span>
            <hr class="flex-1 border-gray-300">
        </div>

        <!-- Google Login -->
        <a href="{{ route('google.redirect') }}"
            class="w-full h-7 flex items-center justify-center border border-gray-300 rounded 
                   hover:bg-gray-50 transition text-xs">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-3.5 h-3.5 mr-1">
            Continue with Google
        </a>

        <!-- Switch to Login -->
        <p class="text-center text-[11px] text-gray-600 mt-2">
            Already have an account?
            <a href="#" id="openLoginFromRegister" class="text-indigo-600 hover:underline">Sign In</a>
        </p>
    </div>
</div>
