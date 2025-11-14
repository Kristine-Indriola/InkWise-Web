@extends('layouts.customerprofile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="flex-1">
                <!-- Header with Logo and Help Link -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-3">
                        <!-- InkWise Logo -->
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">I</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800">Email Verification</h1>
                    </div>
                    <a href="#" class="text-orange-500 hover:text-orange-600 text-sm font-medium transition duration-200">Need help?</a>
                </div>

                <!-- Back Arrow Button -->
                <div class="mb-6">
                    <button onclick="history.back()" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 transition duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-sm font-medium">Back</span>
                    </button>
                </div>

                <!-- Main Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 text-center border border-gray-100">
                    <!-- Mail Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 bg-pink-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-pink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Title -->
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Verification Email Sent</h2>

                    <!-- Email Message -->
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        A verification email has been sent to <span class="font-medium">{{ Auth::user()->email ? substr(Auth::user()->email, 0, 3) . '*******' . substr(Auth::user()->email, -1) : 'your email' }}</span>.
                        Please check your inbox and click the verification link to proceed.
                    </p>

                    <!-- Resend Section -->
                    <div id="resend-section" class="border-t pt-6">
                        <div id="resend-link" class="hidden">
                            <p class="text-gray-600 mb-4">Did not receive it?</p>
                            <a href="#" onclick="resendEmail()" class="inline-flex items-center justify-center gap-2 w-full bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-medium transition duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Resend Email
                            </a>
                        </div>

                        <div id="cooldown-timer" class="block">
                            <p class="text-gray-600 text-center">
                                Please wait <span id="countdown" class="font-medium text-orange-500">60</span> seconds to resend.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:w-80">
                <div class="sticky top-8 space-y-6">
                    <!-- Email Tips -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Email Verification Tips
                            </h3>

                            <div class="space-y-4">
                                <!-- Tip 1 -->
                                <div class="border-l-4 border-pink-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        Check your inbox
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        The verification email should arrive within a few minutes. Check your primary inbox first.
                                    </p>
                                </div>

                                <!-- Tip 2 -->
                                <div class="border-l-4 border-pink-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        Check spam/junk folder
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Sometimes verification emails end up in spam. Check your junk or spam folder if you don't see it.
                                    </p>
                                </div>

                                <!-- Tip 3 -->
                                <div class="border-l-4 border-pink-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        Still not receiving?
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Use the resend option after the countdown, or contact support if issues persist.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Reminder -->
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-6 border border-pink-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-pink-800">Security Reminder</h4>
                        </div>
                        <ul class="text-sm text-pink-700 space-y-2">
                            <li>• Never share verification links</li>
                            <li>• Links expire after 24 hours</li>
                            <li>• Each link can only be used once</li>
                            <li>• Contact us if you suspect unauthorized access</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Send verification email automatically when page loads
    sendVerificationEmail();

    let countdown = 60;
    const countdownElement = document.getElementById('countdown');
    const resendLink = document.getElementById('resend-link');
    const cooldownTimer = document.getElementById('cooldown-timer');

    // Start countdown timer
    const timer = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;

        if (countdown <= 0) {
            clearInterval(timer);
            cooldownTimer.classList.add('hidden');
            resendLink.classList.remove('hidden');
        }
    }, 1000);

    // Send verification email function
    function sendVerificationEmail() {
        const url = '{{ route("customerprofile.send-verification-email") }}';
        console.log('Sending request to:', url);
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response status text:', response.statusText);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Parsed response data:', data);
            if (!data.success) {
                alert('Failed to send verification email: ' + data.message);
            } else {
                alert('Verification email sent successfully!');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Failed to send verification email. Error: ' + error.message);
        });
    }

    // Resend email function
    window.resendEmail = function() {
        // Reset countdown
        countdown = 60;
        countdownElement.textContent = countdown;

        // Show cooldown timer again
        cooldownTimer.classList.remove('hidden');
        resendLink.classList.add('hidden');

        // Send email
        sendVerificationEmail();

        // Restart timer
        const newTimer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(newTimer);
                cooldownTimer.classList.add('hidden');
                resendLink.classList.remove('hidden');
            }
        }, 1000);
    };
});
</script>
@endsection