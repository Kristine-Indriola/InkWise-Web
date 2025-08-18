<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - InkWise</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-3xl font-bold text-center mb-6 text-indigo-600">Create Account</h1>
        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <!-- Name Field -->
            <div>
                <label for="name" class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="name" id="name" required placeholder="Enter your full name"
                       class="w-full p-3 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-gray-700 font-medium">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email"
                       class="w-full p-3 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-gray-700 font-medium">Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password"
                       class="w-full p-3 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>

            <!-- Register Button -->
            <button type="submit"
                class="w-full py-3 text-white font-semibold rounded-lg animate-gradient"
                style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                Register
            </button>
        </form>

        <!-- OR Divider -->
        <div class="flex items-center my-4">
            <hr class="flex-grow border-gray-300">
            <span class="px-2 text-gray-500">OR</span>
            <hr class="flex-grow border-gray-300">
        </div>

        <!-- Google Login Button -->
        <form action="{{ route('google.login') }}" method="POST">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center justify-center space-x-2 border border-gray-300 rounded-lg py-2 hover:bg-gray-50">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
                <span class="text-gray-700 font-medium">Log in with Google</span>
            </button>
        </form>

        <p class="text-sm text-center text-gray-600 mt-4">
            Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a>
        </p>
    </div>
</body>
</html>
