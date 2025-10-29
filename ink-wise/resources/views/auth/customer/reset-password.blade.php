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

        .code-inputs {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .code-input {
            width: 40px;
            height: 40px;
            text-align: center;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            transition: border-color 0.2s;
        }

        .code-input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
    </style>
</head>
<body>
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step completed">1</div>
            <div class="step @if($errors->has('password') || $errors->has('password_confirmation')) completed @else active @endif">2</div>
        </div>

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                @if($errors->has('password') || $errors->has('password_confirmation'))
                    Set New Password
                @else
                    Verify Code
                @endif
            </h2>
            <p class="text-gray-500 text-sm mt-1">
                @if($errors->has('password') || $errors->has('password_confirmation'))
                    Code verified! Create your new password
                @else
                    Enter the 6-digit code sent to your email
                @endif
            </p>
        </div>

        <!-- Reset Password Form -->
        <form method="POST" action="{{ route('customer.password.store') }}" class="space-y-4">
            @csrf

            <!-- Hidden Email (from session/token) -->
            <input type="hidden" name="email" value="{{ old('email', session('email')) }}">

            <!-- Code Verification Section -->
            <div id="codeSection" class="space-y-4 @if($errors->has('password') || $errors->has('password_confirmation')) hidden @endif">
            <!-- Verification Code -->
            <div>
                <label class="block text-sm font-medium text-gray-700 text-center mb-2">Verification Code</label>
                <div class="code-inputs">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                </div>
                <input type="hidden" name="code" id="code">
                @error('code')
                <p class="text-sm text-red-500 mt-1 text-center">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verify Code Button -->
            <button type="button" id="verifyCodeBtn"
                    class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: #0891b2 !important; color: white !important;">
                Verify Code
            </button>
        </div>

        <!-- Password Reset Section (Hidden initially) -->
        <div id="passwordSection" class="space-y-4 @if($errors->has('password') || $errors->has('password_confirmation')) block @else hidden @endif">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                    Set New Password
                </h2>
                <p class="text-gray-500 text-sm mt-1">Code verified! Create your new password</p>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input id="password" type="password" name="password"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base"
                       placeholder="Enter new password">
                @error('password')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base"
                       placeholder="Confirm new password">
                @error('password_confirmation')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-opacity-50"
                    style="background-color: #0891b2 !important; color: white !important;">
                Reset Password
            </button>
        </div>
        </form>

        <!-- Back Links -->
        <div class="text-center mt-4 space-y-2">
            <p class="text-sm text-gray-600">
                Didn't receive the code?
                <a href="{{ route('customer.password.request') }}" class="text-indigo-600 hover:underline">Resend</a>
            </p>
        </div>
    </div>
</div>

<script>
    // Code input handling
    const codeInputs = document.querySelectorAll('.code-input');
    const hiddenCodeInput = document.getElementById('code');
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');
    const codeSection = document.getElementById('codeSection');
    const passwordSection = document.getElementById('passwordSection');
    const stepIndicator = document.querySelector('.step-indicator');

    codeInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            // Only allow numbers
            e.target.value = e.target.value.replace(/[^0-9]/g, '');

            // Auto-focus next input
            if (e.target.value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }

            // Update hidden input
            updateHiddenCode();

            // Enable/disable verify button
            toggleVerifyButton();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/[^0-9]/g, '').slice(0, 6);

            digits.split('').forEach((digit, i) => {
                if (codeInputs[i]) {
                    codeInputs[i].value = digit;
                }
            });

            updateHiddenCode();
            toggleVerifyButton();

            // Focus last filled input or next empty one
            const lastIndex = Math.min(digits.length, codeInputs.length - 1);
            codeInputs[lastIndex].focus();
        });
    });

    function updateHiddenCode() {
        const code = Array.from(codeInputs).map(input => input.value).join('');
        hiddenCodeInput.value = code;
    }

    function toggleVerifyButton() {
        const code = hiddenCodeInput.value;
        verifyCodeBtn.disabled = code.length !== 6;
        verifyCodeBtn.classList.toggle('opacity-50', code.length !== 6);
        verifyCodeBtn.classList.toggle('cursor-not-allowed', code.length !== 6);
    }

    // Verify code button click handler
    verifyCodeBtn.addEventListener('click', () => {
        const code = hiddenCodeInput.value;
        if (code.length === 6) {
            // Submit the form with just the code for verification
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('email', document.querySelector('input[name="email"]').value);
            formData.append('code', code);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Code verified, transition to password section
                    codeSection.classList.add('hidden');
                    passwordSection.classList.remove('hidden');

                    // Update step indicator
                    stepIndicator.innerHTML = `
                        <div class="step completed">1</div>
                        <div class="step completed">2</div>
                    `;

                    // Make password fields required
                    document.getElementById('password').required = true;
                    document.getElementById('password_confirmation').required = true;

                    // Focus on password field
                    document.getElementById('password').focus();
                } else {
                    // Show error
                    alert(data.message || 'Invalid code. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    });

    // Auto-focus first input
    if (codeSection && !codeSection.classList.contains('hidden')) {
        codeInputs[0].focus();
    } else if (passwordSection && !passwordSection.classList.contains('hidden')) {
        document.getElementById('password').focus();
        // Make password fields required
        document.getElementById('password').required = true;
        document.getElementById('password_confirmation').required = true;
    }

    // Initialize verify button state
    toggleVerifyButton();
</script>
</body>
</html>