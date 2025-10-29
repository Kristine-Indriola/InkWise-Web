@extends('layouts.customerprofile')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
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
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Change Password</h1>
                <p class="text-green-600 text-sm">✓ Email verification completed</p>
            </div>

            <!-- Password Change Form -->
            <form method="POST" action="{{ route('customerprofile.change-password.update') }}">
                @csrf
                @method('PUT')

                <!-- Current Password -->
                <div class="mb-6">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('current_password') border-red-500 @enderror"
                               required>
                        <button type="button" onclick="togglePassword('current_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="current_password_eye" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('password') border-red-500 @enderror"
                               required minlength="8">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="password_eye" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <div id="password-strength" class="mt-2 hidden">
                        <div class="flex items-center gap-2">
                            <div id="strength-bar" class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="strength-fill" class="h-full transition-all duration-300"></div>
                            </div>
                            <span id="strength-text" class="text-xs text-gray-500"></span>
                        </div>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm New Password -->
                <div class="mb-8">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('password_confirmation') border-red-500 @enderror"
                               required minlength="8">
                        <button type="button" onclick="togglePassword('password_confirmation')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="password_confirmation_eye" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <div id="password-match" class="mt-1 text-sm hidden"></div>
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-medium transition duration-200">
                    Change Password
                </button>
            </form>

            <!-- Status Message -->
            @if(session('status'))
                <div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const strengthContainer = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-fill');
    const strengthText = document.getElementById('strength-text');
    const matchIndicator = document.getElementById('password-match');

    // Password visibility toggle function
    window.togglePassword = function(inputId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById(inputId + '_eye');

        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
            `;
        } else {
            input.type = 'password';
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            `;
        }
    };

    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];

        if (password.length >= 8) strength++;
        else feedback.push('At least 8 characters');

        if (/[a-z]/.test(password)) strength++;
        else feedback.push('Lowercase letter');

        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('Uppercase letter');

        if (/[0-9]/.test(password)) strength++;
        else feedback.push('Number');

        if (/[^A-Za-z0-9]/.test(password)) strength++;
        else feedback.push('Special character');

        return { strength, feedback };
    }

    // Update password strength display
    passwordInput.addEventListener('input', function() {
        const password = this.value;

        if (password.length > 0) {
            strengthContainer.classList.remove('hidden');
            const { strength, feedback } = checkPasswordStrength(password);

            let color, text, width;
            switch(strength) {
                case 0:
                case 1:
                    color = '#ef4444'; // red
                    text = 'Very Weak';
                    width = '20%';
                    break;
                case 2:
                    color = '#f97316'; // orange
                    text = 'Weak';
                    width = '40%';
                    break;
                case 3:
                    color = '#eab308'; // yellow
                    text = 'Fair';
                    width = '60%';
                    break;
                case 4:
                    color = '#22c55e'; // green
                    text = 'Good';
                    width = '80%';
                    break;
                case 5:
                    color = '#16a34a'; // dark green
                    text = 'Strong';
                    width = '100%';
                    break;
            }

            strengthBar.style.backgroundColor = color;
            strengthBar.style.width = width;
            strengthText.textContent = text;
            strengthText.style.color = color;
        } else {
            strengthContainer.classList.add('hidden');
        }

        checkPasswordMatch();
    });

    // Check password confirmation match
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (confirm.length > 0) {
            matchIndicator.classList.remove('hidden');
            if (password === confirm) {
                matchIndicator.textContent = '✓ Passwords match';
                matchIndicator.className = 'mt-1 text-sm text-green-600';
                confirmInput.classList.remove('border-red-500');
                confirmInput.classList.add('border-green-500');
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.className = 'mt-1 text-sm text-red-600';
                confirmInput.classList.remove('border-green-500');
                confirmInput.classList.add('border-red-500');
            }
        } else {
            matchIndicator.classList.add('hidden');
            confirmInput.classList.remove('border-red-500', 'border-green-500');
        }
    }

    confirmInput.addEventListener('input', checkPasswordMatch);

    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }
    });
});
</script>
@endsection