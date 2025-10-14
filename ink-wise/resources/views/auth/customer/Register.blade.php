<div id="registerModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-2">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 relative transform transition-all scale-95 hover:scale-100 duration-300 overflow-y-auto"
         style="max-height: 90vh;">
        <!-- Close button -->
        <button id="closeRegister"
                class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition text-base font-bold">
            ✖
        </button>

        <!-- Modal Header -->
        <div class="text-center mb-5">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Create Account
            </h2>
            <p class="text-gray-500 text-sm mt-1">Join InkWise and start your journey</p>
        </div>

        <!-- Register Form -->
        <form method="POST" action="{{ route('customer.register') }}" id="customerRegisterForm" class="space-y-6">
            @csrf
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg p-2">
                    <ul class="list-disc ml-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="registerSteps" class="space-y-6">
                <!-- Step 1 -->
                <div class="register-step" data-step="1">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                        </div>
                    </div>
                    <div class="mt-4">
               <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Birthdate</label>
               <input type="date" name="birthdate" value="{{ old('birthdate') }}" required
                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                    </div>
                    <div class="flex items-center justify-end mt-6">
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-next-step>Next</button>
                    </div>
                    <p class="text-center text-xs text-gray-600 mt-4">
                        Already have an account?
                        <a href="#" id="openLoginFromRegister" class="text-indigo-600 font-medium hover:underline">Sign In</a>
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="register-step hidden" data-step="2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Contact Number</label>
               <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                               class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-prev-step>Back</button>
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-next-step>Next</button>
                    </div>
                    <p class="text-center text-xs text-gray-600 mt-4">
                        Already have an account?
                        <a href="#" class="text-indigo-600 font-medium hover:underline" data-open-login>Sign In</a>
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="register-step hidden" data-step="3">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Email</label>
                            <div class="mt-1 flex gap-2">
                    <input type="email" name="email" id="registerEmail" value="{{ old('email') }}" required
                                       class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                                <button type="button"
                                        class="px-3 py-2 text-xs font-semibold text-white bg-indigo-500 hover:bg-indigo-600 rounded-lg shadow-sm transition"
                                        id="sendVerificationCode">
                                    Send Code
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Verification Code</label>
                <input type="text" name="verification_code" id="verificationCode" value="{{ old('verification_code') }}" required maxlength="6"
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition uppercase tracking-widest">
                        </div>
                        <p id="verificationStatus" class="text-xs text-gray-500"></p>
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-prev-step>Back</button>
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-next-step>Next</button>
                    </div>
                    <p class="text-center text-xs text-gray-600 mt-4">
                        Already have an account?
                        <a href="#" class="text-indigo-600 font-medium hover:underline" data-open-login>Sign In</a>
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="register-step hidden" data-step="4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Password</label>
                            <input type="password" name="password" required
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Confirm Password</label>
                            <input type="password" name="password_confirmation" required
                                   class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 transition">
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700" data-prev-step>Back</button>
                        <button type="button" id="submitRegister"
                                class="bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-700 hover:to-blue-600 text-white font-semibold py-2 px-6 rounded-xl shadow-lg transition text-sm">
                            Sign Up
                        </button>
                    </div>
                    <label for="terms" class="mt-3 flex items-start gap-2 text-[11px] text-gray-500 leading-tight text-center justify-center">
               <input type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>By signing up, you agree to InkWise's <a href="#" class="underline hover:text-indigo-600">Terms of Service</a> and <a href="#" class="underline hover:text-indigo-600">Privacy Policy</a>.</span>
                    </label>
                    <p class="text-center text-xs text-gray-600 mt-4">
                        Already have an account?
                        <a href="#" class="text-indigo-600 font-medium hover:underline" data-open-login>Sign In</a>
                    </p>
                </div>
            </div>
        </form>

        {{-- Third-party login coming soon --}}
    </div>
</div>

<style>
    .bg-white.overflow-y-auto::-webkit-scrollbar {
        width: 0 !important;
        background: transparent;
    }

    .bg-white.overflow-y-auto {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;     /* Firefox */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = Array.from(document.querySelectorAll('#registerSteps .register-step'));
        if (!steps.length) {
            return;
        }

        let activeIndex = 0;

        const goToStep = (index) => {
            steps.forEach((step, idx) => {
                step.classList.toggle('hidden', idx !== index);
            });
            activeIndex = index;
        };

        document.querySelectorAll('[data-next-step]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (activeIndex < steps.length - 1) {
                    goToStep(activeIndex + 1);
                }
            });
        });

        document.querySelectorAll('[data-prev-step]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (activeIndex > 0) {
                    goToStep(activeIndex - 1);
                }
            });
        });

        document.querySelectorAll('[data-open-login]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const loginTrigger = document.getElementById('openLoginFromRegister');
                if (loginTrigger) {
                    loginTrigger.click();
                }
            });
        });

        goToStep(activeIndex);

        // Email verification code sending
        const sendButton = document.getElementById('sendVerificationCode');
        const emailInput = document.getElementById('registerEmail');
        const statusElement = document.getElementById('verificationStatus');
        let cooldownTime = 60;
        let cooldownInterval;

        function startCooldown() {
            sendButton.disabled = true;
            sendButton.textContent = `Resend in ${cooldownTime}s`;
            cooldownInterval = setInterval(() => {
                cooldownTime--;
                sendButton.textContent = `Resend in ${cooldownTime}s`;
                if (cooldownTime <= 0) {
                    clearInterval(cooldownInterval);
                    sendButton.disabled = false;
                    sendButton.textContent = 'Send Code';
                    cooldownTime = 60;
                }
            }, 1000);
        }

        function setStatus(message, type) {
            statusElement.textContent = message;
            statusElement.className = `text-xs ${type === 'error' ? 'text-red-500' : 'text-green-500'}`;
        }

        sendButton.addEventListener('click', async () => {
            const email = emailInput.value.trim();
            if (!email) {
                setStatus('Please enter your email address.', 'error');
                return;
            }
            sendButton.disabled = true;
            sendButton.textContent = 'Sending...';
            try {
                const response = await fetch('/customer/register/send-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email })
                });
                const body = await response.json();
                if (!response.ok) {
                    const message = body?.errors?.email?.[0] ?? body?.message ?? 'Unable to send verification code.';
                    throw new Error(message);
                }
                setStatus(body.message ?? 'Verification code sent! Check your inbox.', 'success');
                startCooldown();
            } catch (error) {
                sendButton.disabled = false;
                sendButton.textContent = 'Send Code';
                setStatus(error.message, 'error');
            }
        });

        document.getElementById('submitRegister').addEventListener('click', () => {
            document.getElementById('customerRegisterForm').submit();
        });

        // Show modal if there are errors
        if (document.querySelector('.bg-red-50')) {
            document.getElementById('registerModal').classList.remove('hidden');
        }
    });
</script>
