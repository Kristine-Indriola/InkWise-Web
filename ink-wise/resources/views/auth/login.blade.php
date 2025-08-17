<x-app-layout>
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
            <h2 class="text-2xl font-bold text-center mb-6" style="font-family: 'Playfair Display', serif;">
                Login to InkWise
            </h2>
            <form method="POST" action="{{ url('/login') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium">Password</label>
                    <input type="password" name="password" required class="w-full border rounded px-3 py-2">
                </div>
                <button type="submit" class="w-full text-white font-semibold py-2 rounded"
                        style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                    Login
                </button>
            </form>
            <p class="mt-4 text-center text-sm">
                Don’t have an account? <a href="{{ url('/register') }}" class="text-indigo-600">Sign Up</a>
            </p>
        </div>
    </div>
</x-app-layout>
