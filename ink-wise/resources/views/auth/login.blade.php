@extends('layouts.app')
<!-- LOGIN MODAL -->   
    <div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md transform transition-all scale-95 animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">Login</h2>
                <button id="closeLogin" class="text-gray-400 hover:text-gray-600">✖</button>
            </div>
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="email" name="email" placeholder="Email"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <input type="password" name="password" placeholder="Password"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Login
                </button>
            </form>

            <!-- Google login -->
            <div class="mt-4">
                <a href="{{ route('google.redirect') }}"
                   class="flex items-center justify-center w-full px-4 py-2 border rounded-lg bg-white hover:bg-gray-100">
                    <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google"
                         class="w-5 h-5 mr-2">
                    <span class="text-gray-700">Continue with Google</span>
                </a>
            </div>
            <p class="mt-6 text-sm text-gray-600 text-center">
                Don’t have an account?
                <button id="openRegisterFromLogin" class="text-indigo-600 hover:underline">
                    Register
                </button>
            </p>
        </div>
    </div>
    