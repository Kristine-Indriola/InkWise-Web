<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | InkWise</title>
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

        .back-link-wrapper {
            position: absolute;
            top: clamp(1rem, 4vw, 2.25rem);
            left: clamp(1rem, 4vw, 2.25rem);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(15, 23, 42, 0.6);
            text-decoration: none;
            transition: color 0.18s ease, transform 0.18s ease;
        }

        .back-link:hover {
            color: var(--color-primary-dark);
            transform: translateX(-2px);
        }

        .auth-shell {
            width: min(1060px, 100%);
            display: grid;
            gap: clamp(2rem, 4vw, 3rem);
            background: #ffffff;
            border-radius: 28px;
            box-shadow: var(--shadow-elevated);
            padding: clamp(2rem, 4vw, 3rem);
            border: 1px solid rgba(15, 23, 42, 0.05);
        }

        @media (min-width: 960px) {
            .auth-shell {
                grid-template-columns: 1fr 1.05fr;
                align-items: stretch;
            }
        }

        .auth-intro {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.12), rgba(4, 29, 66, 0.08));
            border-radius: 22px;
            padding: clamp(1.75rem, 3vw, 2.75rem);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: clamp(1.5rem, 2.5vw, 2.5rem);
        }

        .eyebrow {
            font-size: 0.75rem;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: rgba(15, 23, 42, 0.54);
            margin: 0;
        }

        .intro-title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 3vw, 2.65rem);
            line-height: 1.25;
            margin: 0.75rem 0 0;
            color: var(--color-ink);
        }
/* sample */
        .intro-copy {
            font-size: 0.95rem;
            line-height: 1.65;
            color: rgba(15, 23, 42, 0.7);
            margin: 1rem 0 0;
        }

        .intro-link {
            align-self: flex-start;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--color-primary-dark);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.18s ease;
        }

        .intro-link:hover {
            color: var(--color-primary);
        }

        .auth-pane {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            position: relative;
            background: #ffffff;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            width: min(100%, 460px);
            padding: clamp(1.75rem, 3vw, 2.5rem);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            max-height: 86vh;
            overflow-y: auto;
        }

        .auth-card::-webkit-scrollbar {
            width: 0 !important;
            background: transparent;
        }

        .auth-card {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .auth-card__action {
            display: flex;
            justify-content: flex-end;
        }

        .link-quiet {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(15, 23, 42, 0.6);
            text-decoration: none;
            transition: color 0.18s ease;
        }

        .link-quiet:hover {
            color: var(--color-primary-dark);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .auth-title {
            font-family: var(--font-display);
            font-size: 1.9rem;
            margin: 0;
            color: var(--color-ink);
        }

        .auth-subtitle {
            margin: 0.45rem 0 0;
            font-size: 0.9rem;
            color: rgba(15, 23, 42, 0.58);
        }

        .link-primary {
            color: var(--color-primary-dark);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.18s ease;
        }

        .link-primary:hover {
            color: var(--color-ink);
        }

        .link-inline {
            color: var(--color-primary-dark);
            text-decoration: underline;
        }

        .link-inline:hover {
            color: var(--color-ink);
        }

        .form-stack {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .step-link {
            background: transparent;
            border: 0;
            color: var(--color-primary-dark);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            transition: color 0.18s ease;
        }

        .step-link:hover {
            color: var(--color-ink);
        }

        .step-link:focus {
            outline: 2px solid rgba(6, 182, 212, 0.3);
            outline-offset: 2px;
            border-radius: 4px;
        }

        .support-text {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.75rem;
            color: rgba(15, 23, 42, 0.56);
        }

        .btn-inkwise {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.55rem 1.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #ffffff;
            background-image: linear-gradient(135deg, #06b6d4, #0e7490);
            border-radius: 0.9rem;
            box-shadow: 0 12px 22px rgba(14, 116, 144, 0.2);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            border: none;
        }

        .btn-inkwise:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(14, 116, 144, 0.25);
            filter: brightness(1.04);
        }

        .btn-inkwise:focus {
            outline: 2px solid rgba(6, 182, 212, 0.4);
            outline-offset: 2px;
        }

        .btn-inkwise[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-inkwise--small {
            padding: 0.45rem 1rem;
            font-size: 0.78rem;
        }
    </style>
</head>
<body>
    <div class="back-link-wrapper">
        <a href="{{ url('/') }}" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to home
        </a>
    </div>

    <div class="auth-shell">
        <section class="auth-intro">
            <div>
                <p class="eyebrow">Welcome to InkWise</p>
                <h1 class="intro-title">
                    Crafted invitations that celebrate every milestone.
                </h1>
                <p class="intro-copy">
                    Join a community of creators and event planners who rely on InkWise for elegant designs,
                    smart automation, and a seamless customer experience.
                </p>
            </div>
            <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="intro-link">
                I already have an account
            </a>
        </section>

        <section class="auth-pane">
            <div class="auth-card">
                <div class="auth-card__action">
                    <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="link-quiet">
                        Sign in
                    </a>
                </div>

                <div class="auth-header">
                    <h2 class="auth-title">Create Account</h2>
                    <p class="auth-subtitle">Join InkWise and start your journey</p>
                </div>

                <form method="POST" action="{{ route('customer.register') }}" id="customerRegisterForm" class="form-stack">
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
                        <div class="register-step" data-step="1">
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Middle Name</label>
                                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Last Name</label>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Birthdate</label>
                                <input type="date" name="birthdate" value="{{ old('birthdate') }}" required
                                       class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                            </div>
                            <div class="flex items-center justify-end mt-6">
                                <button type="button" class="step-link" data-next-step>Next</button>
                            </div>
                            <p class="support-text">
                                Already have an account?
                                <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="link-primary">Sign In</a>
                            </p>
                        </div>

                        <div class="register-step hidden" data-step="2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                                       class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                            </div>
                            <div class="flex items-center justify-between mt-6">
                                <button type="button" class="step-link" data-prev-step>Back</button>
                                <button type="button" class="step-link" data-next-step>Next</button>
                            </div>
                            <p class="support-text">
                                Already have an account?
                                <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="link-primary">Sign In</a>
                            </p>
                        </div>

                        <div class="register-step hidden" data-step="3">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Email</label>
                                    <div class="mt-1 flex gap-2">
                                        <input type="email" name="email" id="registerEmail" value="{{ old('email') }}" required
                                               class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                        <button type="button"
                                                class="btn-inkwise btn-inkwise--small"
                                                id="sendVerificationCode">
                                            Send Code
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Verification Code</label>
                                    <input type="text" name="verification_code" id="verificationCode" value="{{ old('verification_code') }}" required maxlength="6"
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition uppercase tracking-widest">
                                </div>
                                <p id="verificationStatus" class="text-xs text-gray-500"></p>
                            </div>
                            <div class="flex items-center justify-between mt-6">
                                <button type="button" class="step-link" data-prev-step>Back</button>
                                <button type="button" class="step-link" data-next-step>Next</button>
                            </div>
                            <p class="support-text">
                                Already have an account?
                                <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="link-primary">Sign In</a>
                            </p>
                        </div>

                        <div class="register-step hidden" data-step="4">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Password</label>
                                    <input type="password" name="password" required
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Confirm Password</label>
                                    <input type="password" name="password_confirmation" required
                                           class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-500 transition">
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-6">
                                <button type="button" class="step-link" data-prev-step>Back</button>
                                <button type="button" id="submitRegister"
                                        class="btn-inkwise">
                                    Sign Up
                                </button>
                            </div>
                            <label for="terms" class="mt-3 flex items-start gap-2 text-[11px] text-gray-500 leading-tight text-center justify-center">
                                <input type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required
                                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span>By signing up, you agree to InkWise's <a href="#" class="link-inline">Terms of Service</a> and <a href="#" class="link-inline">Privacy Policy</a>.</span>
                            </label>
                            <p class="support-text">
                                Already have an account?
                                <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="link-primary">Sign In</a>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const registerForm = document.getElementById('customerRegisterForm');
            const steps = Array.from(document.querySelectorAll('#registerSteps .register-step'));
            if (!steps.length || !registerForm) {
                return;
            }

            let activeIndex = steps.findIndex((step) => !step.classList.contains('hidden'));
            if (activeIndex < 0) {
                activeIndex = 0;
            }

            let cooldownTime = 60;
            let cooldownInterval;
            let verificationSent = false;

            const sendButton = document.getElementById('sendVerificationCode');
            const emailInput = document.getElementById('registerEmail');
            const verificationCodeInput = document.getElementById('verificationCode');
            const statusElement = document.getElementById('verificationStatus');
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            const sendCodeUrl = @json(route('customer.register.send-code'));

            if (verificationCodeInput && verificationCodeInput.value.trim().length === 6) {
                verificationSent = true;
            }

            const goToStep = (index) => {
                steps.forEach((step, idx) => {
                    step.classList.toggle('hidden', idx !== index);
                });
                activeIndex = index;
            };

            const validateRequiredFields = (step) => {
                const fields = Array.from(step.querySelectorAll('input, select, textarea'));
                for (const field of fields) {
                    field.setCustomValidity('');
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return false;
                    }
                }

                return true;
            };

            const clearStatus = () => {
                if (!statusElement) {
                    return;
                }
                statusElement.textContent = '';
                statusElement.removeAttribute('data-state');
                statusElement.className = 'text-xs text-gray-500';
            };

            const setStatus = (message, type) => {
                if (!statusElement) {
                    return;
                }

                if (!message) {
                    clearStatus();
                    return;
                }

                statusElement.textContent = message;
                statusElement.dataset.state = type;
                statusElement.className = `text-xs ${type === 'error' ? 'text-red-500' : 'text-teal-500'}`;
            };

            const resolveMessage = (payload, fallback) => {
                if (!payload || typeof payload !== 'object') {
                    return fallback;
                }

                const errors = payload.errors;
                if (errors && errors.email && errors.email.length) {
                    return errors.email[0];
                }

                if (payload.message) {
                    return payload.message;
                }

                return fallback;
            };

            const validateStep = (index) => {
                const step = steps[index];
                if (!step) {
                    return true;
                }

                if (!validateRequiredFields(step)) {
                    return false;
                }

                if (index === 2 && !verificationSent) {
                    setStatus('Please send your verification code before continuing.', 'error');
                    return false;
                }

                return true;
            };

            document.querySelectorAll('[data-next-step]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (activeIndex < steps.length - 1 && validateStep(activeIndex)) {
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

            const startCooldown = () => {
                if (cooldownInterval) {
                    clearInterval(cooldownInterval);
                }
                cooldownTime = 60;
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
            };

            if (sendButton) {
                sendButton.addEventListener('click', async () => {
                    const email = emailInput ? emailInput.value.trim() : '';
                    if (!email) {
                        setStatus('Please enter your email address.', 'error');
                        return;
                    }

                    if (!csrfToken) {
                        setStatus('Unable to send verification code right now.', 'error');
                        return;
                    }

                    sendButton.disabled = true;
                    sendButton.textContent = 'Sending...';

                    try {
                        const response = await fetch(sendCodeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ email })
                        });

                        const contentType = response.headers.get('content-type') || '';
                        const isJson = contentType.includes('application/json');
                        const body = isJson ? await response.json() : {};

                        if (!response.ok) {
                            const message = resolveMessage(body, 'Unable to send verification code.');
                            throw new Error(message);
                        }

                        verificationSent = true;
                        const successMessage = resolveMessage(body, 'Verification code sent! Check your inbox.');
                        setStatus(successMessage, 'success');
                        startCooldown();
                    } catch (error) {
                        verificationSent = false;
                        sendButton.disabled = false;
                        sendButton.textContent = 'Send Code';
                        setStatus(error.message ?? 'Unable to send verification code.', 'error');
                    }
                });
            }

            if (emailInput) {
                emailInput.addEventListener('input', () => {
                    verificationSent = false;
                    clearStatus();
                });
            }

            registerForm.addEventListener('submit', (event) => {
                if (!registerForm.checkValidity()) {
                    event.preventDefault();
                    registerForm.reportValidity();
                }
            });

            const submitButton = document.getElementById('submitRegister');
            if (submitButton) {
                submitButton.addEventListener('click', () => {
                    if (validateStep(activeIndex)) {
                        if (registerForm.checkValidity()) {
                            if (typeof registerForm.requestSubmit === 'function') {
                                registerForm.requestSubmit();
                            } else {
                                registerForm.submit();
                            }
                        } else {
                            registerForm.reportValidity();
                        }
                    }
                });
            }

            goToStep(activeIndex);

            const errorFields = @json(array_keys($errors->toArray()));
            if (errorFields.length) {
                const fieldName = errorFields.find((name) => registerForm.elements[name]);
                if (fieldName) {
                    const field = registerForm.elements[fieldName];
                    const fieldStep = field ? field.closest('.register-step') : null;
                    const stepIndex = steps.indexOf(fieldStep);
                    if (stepIndex >= 0) {
                        goToStep(stepIndex);
                    }
                }
            }
        });
    </script>
</body>
</html>
