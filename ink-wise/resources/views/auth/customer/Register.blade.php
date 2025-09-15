<div id="registerModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-2">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 relative transform transition-all scale-95 hover:scale-100 duration-300 overflow-y-auto"
         style="max-height: 90vh;">
        <!-- Close button -->
        <button id="closeRegister"
                class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition text-base font-bold">
            ✖
        </button>

        <!-- Modal Header -->
        <div class="text-center mb-5">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Create Account
            </h2>
            <p class="text-gray-500 text-sm mt-1">Join InkWise and start your journey</p>
        </div>

        <!-- Register Form -->
        <form method="POST" action="{{ route('customer.register') }}" class="space-y-3">
            @csrf

            <!-- Names Row -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" required
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">Middle Name</label>
                    <input type="text" name="middle_name"
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" required
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
            </div>

            <!-- Birthdate -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Birthdate</label>
                <input type="date" name="birthdate" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Contact Number -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number"
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Email</label>
                <input type="email" name="email" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Password</label>
                <input type="password" name="password" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-700 hover:to-blue-600 text-white font-semibold py-2 rounded-lg shadow-md transition text-xs">
                Sign Up
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-3">
            <hr class="flex-1 border-gray-300">
            <span class="px-2 text-[10px] text-gray-500">or</span>
            <hr class="flex-1 border-gray-300">
        </div>

        <!-- Google Login -->
        <a href="{{ route('google.redirect') }}"
           class="w-full h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 transition text-xs shadow-sm">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                 class="w-3.5 h-3.5 mr-1">
            Continue with Google
        </a>

        <!-- Terms and Privacy Message -->
        <p class="text-[11px] text-gray-500 text-center mt-2 mb-1">
            By signing up, you agree to InkWise's <a href="#" class="underline hover:text-indigo-600">Terms of Services</a> &amp; <a href="#" class="underline hover:text-indigo-600">privacy policy</a>
        </p>

        <!-- Switch to Login -->
        <p class="text-center text-xs text-gray-600 mt-2">
            Already have an account?
            <a href="#" id="openLoginFromRegister" class="text-indigo-600 font-medium hover:underline">Sign In</a>
        </p>
    </div>
</div>

<style>
    .bg-white.overflow-y-auto::-webkit-scrollbar {
        width: 0 !important;
        background: transparent;
    }

    .bg-white.overflow-y-auto {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;     /* Firefox */
    }
</style>
