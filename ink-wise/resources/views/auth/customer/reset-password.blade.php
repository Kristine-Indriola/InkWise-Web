<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | InkWise</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $viteManifestPresent = file_exists(public_path('build/manifest.json'));
    @endphp

    @if ($viteManifestPresent)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endif

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');

        :root {
            --color-primary: #06b6d4;
            --color-primary-dark: #0e7490;
            --color-ink: #0f172a;
            --shadow-elevated: 0 24px 50px rgba(15, 23, 42, 0.08);
            --font-display: 'Playfair Display', serif;
            --font-accent: 'Seasons', serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            position: relative;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            color: var(--color-ink);
            font-family: var(--font-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(2rem, 5vw, 4rem) clamp(1.5rem, 4vw, 3rem);
        }
    </style>
</head>
<body>
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Reset Password
            </h2>
            <p class="text-gray-500 text-sm mt-1">Enter the code from your email and your new password.</p>
        </div>

        <!-- Reset Password Form -->
        <form method="POST" action="{{ route('customer.password.store') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                @error('email')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reset Code -->
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">Reset Code</label>
                <input id="code" type="text" name="code" value="{{ old('code') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base"
                       placeholder="Enter the 6-digit code from your email">
                @error('code')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                @error('password')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                @error('password_confirmation')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                {{ session('status') }}
            </div>
            @endif

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-700 hover:to-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow-md transition">
                Reset Password
            </button>
        </form>

        <!-- Back to Login -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Remember your password?
            <a href="{{ route('customer.login.form') }}" class="text-indigo-600 hover:underline">Sign In</a>
        </p>
    </div>
</div>
</body>
</html>