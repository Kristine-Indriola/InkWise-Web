<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | InkWise</title>
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

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: bold;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #06b6d4;
            color: white;
        }

        .step.completed {
            background: #10b981;
            color: white;
        }
    </style>
</head>
<body>
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active">1</div>
            <div class="step">2</div>
        </div>

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Reset Password
            </h2>
            <p class="text-gray-500 text-sm mt-1">Enter your email to receive a verification code</p>
        </div>

        <!-- Forgot Password Form -->
        <form method="POST" action="{{ route('customer.password.email') }}" class="space-y-4">
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

            @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                {{ session('status') }}
            </div>
            @endif

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-opacity-50"
                    style="background-color: #0891b2 !important; color: white !important;">
                Send Verification Code
            </button>
        </form>

        <!-- Back to Login -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Remember your password?
            <a href="{{ route('dashboard') }}?show_login=true" class="text-indigo-600 hover:underline">Sign In</a>
        </p>
    </div>
</div>
</body>
</html>