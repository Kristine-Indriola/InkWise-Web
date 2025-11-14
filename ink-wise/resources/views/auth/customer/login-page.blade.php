<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign In - InkWise</title>
    <link rel="icon" type="image/png" href="{{ asset('adminimage/inkwise.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-100 via-purple-50 to-blue-100 min-h-screen flex items-center justify-center px-4">
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 relative transform transition-all">
        
        <!-- Back to Home Link -->
        <a href="{{ route('dashboard') }}" class="absolute top-3 left-3 text-gray-400 hover:text-indigo-600 transition text-sm flex items-center">
            ← Back to Home
        </a>

        <!-- Modal Header -->
        <div class="text-center mb-6 mt-8">
            <div class="flex justify-center mb-4">
                <img src="{{ asset('adminimage/inkwise.png') }}" alt="InkWise Logo" class="h-16 w-16">
            </div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Customer Sign In
            </h2>
            <p class="text-gray-500 text-sm mt-2">Welcome back! Please enter your details.</p>
        </div>

        <!-- Display Errors -->
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('customer.login') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3 text-base">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3 text-base">
            </div>

            <!-- Forgot Password & Remember Me -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-indigo-600">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>
                <a href="{{ route('customer.password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot Password?</a>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                Sign In
            </button>
        </form>

        <!-- Switch to Register -->
        <p class="text-center text-sm text-gray-600 mt-6">
            Don't have an account? 
            <a href="{{ route('customer.register.form') }}" class="text-indigo-600 hover:underline font-semibold">Register Now</a>
        </p>
    </div>

</body>
</html>
