<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InkWise</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-3xl font-bold text-center mb-6 text-indigo-600">Login</h1>
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-gray-700 font-medium">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email"
                    class="w-full p-3 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-medium">Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password"
                    class="w-full p-3 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>
            <button type="submit"
                class="w-full py-3 text-white font-semibold rounded-lg animate-gradient"
                style="background: linear-gradient(90deg, #8c52ff, #5ce1e6);">
                Login
            </button>
        </form>
        <p class="text-sm text-center text-gray-600 mt-4">
            Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Register</a>
        </p>
    </div>
</body>
</html>
