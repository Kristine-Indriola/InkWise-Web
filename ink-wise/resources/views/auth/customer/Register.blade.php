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
        <form method="POST" action="{{ route('customer.register') }}" class="space-y-3" id="customerRegisterForm">
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

            <!-- Names Row -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                </div>
            </div>

            <!-- Birthdate -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Birthdate</label>
                <input type="date" name="birthdate" value="{{ old('birthdate') }}" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Contact Number -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Email</label>
                    <div class="mt-1 flex gap-2">
                        <input type="email" name="email" id="registerEmail" value="{{ old('email') }}" required
                               class="flex-1 px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
                        <button type="button" id="sendVerificationCode"
                                class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold shadow-md hover:bg-indigo-700 focus:outline-none">
                            Send Code
                        </button>
                    </div>
                </div>
                <p id="verificationStatus" class="text-[11px] text-gray-500"></p>
            </div>

            <!-- Verification Code -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Verification Code</label>
                <input type="text" name="verification_code" id="verificationCode" value="{{ old('verification_code') }}" required maxlength="6"
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs uppercase tracking-widest">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Password</label>
                <input type="password" name="password" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 w-full px-2 py-1.5 border rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs">
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-700 hover:to-blue-600 text-white font-semibold py-2 rounded-lg shadow-md transition text-xs">
                Sign Up
            </button>
        </form>

        {{-- Third-party login coming soon --}}

        <!-- Terms and Privacy Message -->
        <p class="text-[11px] text-gray-500 text-center mt-2 mb-1">
            By signing up, you agree to InkWise's <a href="#" class="underline hover:text-indigo-600">Terms of Services</a> &amp; <a href="#" class="underline hover:text-indigo-600">privacy policy</a>
        </p>

        <!-- Switch to Login -->
        <p class="text-center text-xs text-gray-600 mt-2">
            Already have an account?
            <a href="#" id="openLoginFromRegister" class="text-indigo-600 font-medium hover:underline">Sign In</a>
        </p>
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
    document.addEventListener('DOMContentLoaded', () => {
    const sendButton = document.getElementById('sendVerificationCode');
    const emailInput = document.getElementById('registerEmail');
    const statusText = document.getElementById('verificationStatus');
    const codeInput = document.getElementById('verificationCode');
        const form = document.getElementById('customerRegisterForm');

        if (!sendButton || !emailInput || !statusText || !form || !codeInput) {
            return;
        }

        const csrfToken = form.querySelector('input[name="_token"]').value;

        codeInput.addEventListener('input', (event) => {
            const sanitized = event.target.value.replace(/\D/g, '').slice(0, 6);
            event.target.value = sanitized;
        });

        const setStatus = (message, type = 'info') => {
            const colorMap = {
                info: 'text-gray-500',
                success: 'text-green-600',
                error: 'text-red-600',
            };

            statusText.textContent = message;
            statusText.className = `text-[11px] ${colorMap[type] ?? colorMap.info}`;
        };

        let cooldownTimer;

        const startCooldown = (seconds = 60) => {
            let remaining = seconds;
            sendButton.disabled = true;
            sendButton.classList.add('opacity-60', 'cursor-not-allowed');
            sendButton.textContent = `Resend in ${remaining}s`;

            cooldownTimer = setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(cooldownTimer);
                    sendButton.disabled = false;
                    sendButton.classList.remove('opacity-60', 'cursor-not-allowed');
                    sendButton.textContent = 'Send Code';
                    return;
                }

                sendButton.textContent = `Resend in ${remaining}s`;
            }, 1000);
        };

        sendButton.addEventListener('click', async () => {
            const email = emailInput.value.trim();

            if (!email) {
                setStatus('Enter your email before requesting a code.', 'error');
                emailInput.focus();
                return;
            }

            clearInterval(cooldownTimer);
            sendButton.disabled = true;
            sendButton.classList.add('opacity-60', 'cursor-not-allowed');
            sendButton.textContent = 'Sending…';
            setStatus('Sending verification code…');

            try {
                const response = await fetch('{{ route('customer.register.send-code') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email }),
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
                sendButton.classList.remove('opacity-60', 'cursor-not-allowed');
                sendButton.textContent = 'Send Code';
                setStatus(error.message, 'error');
            }
        });
    });
</script>
