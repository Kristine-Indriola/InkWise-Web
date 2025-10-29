@extends('layouts.customerprofile')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Main Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Success Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Email Verified!</h1>

            <!-- Success Message -->
            <p class="text-gray-600 mb-8 leading-relaxed">
                Your email has been successfully verified. You can now proceed to change your password.
            </p>

            <!-- Continue Button -->
            <a href="{{ route('customerprofile.password-change-confirm') }}" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-medium transition duration-200 inline-block text-center">
                Continue to Change Password
            </a>
        </div>
    </div>
</div>
@endsection