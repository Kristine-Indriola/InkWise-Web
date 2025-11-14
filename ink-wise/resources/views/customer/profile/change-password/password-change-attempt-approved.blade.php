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
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Password Change Verification</h1>

            <!-- Approval Details -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="text-green-600 font-medium">Email Verified ✓</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Time:</span>
                        <span class="text-gray-800">{{ now()->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <p class="text-gray-600 mb-6 leading-relaxed">
                Your email verification has been completed successfully. You will be redirected to the password change form shortly.
            </p>

            <!-- Progress Indicator -->
            <div class="mb-6">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-green-500 h-2 rounded-full transition-all duration-3000 ease-out" style="width: 0%"></div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Redirecting in <span id="countdown">3</span> seconds...</p>
            </div>

            <!-- Manual Redirect Link -->
            <p class="text-sm text-gray-500">
                Not redirecting? <a href="{{ route('customerprofile.password-change-confirm') }}" class="text-orange-500 hover:text-orange-600 font-medium">Click here</a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let countdown = 3;
    const countdownElement = document.getElementById('countdown');
    const progressBar = document.getElementById('progress-bar');

    // Update progress bar and countdown
    const timer = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;

        // Update progress bar (3 seconds total, so 33.33% per second)
        const progress = ((3 - countdown) / 3) * 100;
        progressBar.style.width = progress + '%';

        if (countdown <= 0) {
            clearInterval(timer);
            // Redirect to password change confirm page
            window.location.href = '{{ route("customerprofile.password-change-confirm") }}';
        }
    }, 1000);
});
</script>
@endsection