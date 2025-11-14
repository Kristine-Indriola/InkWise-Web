@extends('layouts.customerprofile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <button onclick="history.back()" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 transition duration-200 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="text-sm font-medium">Back</span>
            </button>
            <h1 class="text-3xl font-bold text-gray-800">Change Password</h1>
            <p class="text-gray-600 mt-2">Update your password to keep your account secure</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-8">
                <!-- Success/Error Messages -->
                @if(session('status'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 flex-shrink-0 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-red-700 mb-2">Please correct the following errors:</p>
                                <ul class="list-disc list-inside text-red-600 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Password Change Form -->
                <form method="POST" action="{{ route('customerprofile.change-password.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Current Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password"
                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition @error('current_password') border-red-500 @enderror"
                                   placeholder="Enter your current password"
                                   required>
                            <button type="button" 
                                    onclick="togglePassword('current_password')" 
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-700">
                                <svg id="current_password_eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            New Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition @error('password') border-red-500 @enderror"
                                   placeholder="Enter your new password"
                                   required 
                                   minlength="8">
                            <button type="button" 
                                    onclick="togglePassword('password')" 
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-700">
                                <svg id="password_eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div id="password-strength" class="mt-3 hidden">
                            <div class="flex items-center gap-2">
                                <div id="strength-bar" class="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div id="strength-fill" class="h-full transition-all duration-300 ease-out"></div>
                                </div>
                                <span id="strength-text" class="text-xs font-semibold min-w-[80px] text-right"></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Password strength: <span id="strength-description">Enter a password</span></p>
                        </div>

                        <!-- Password Requirements -->
                        <div class="mt-3 bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Password must contain:</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <li id="req-length" class="flex items-center gap-2">
                                    <span class="text-gray-400">○</span> At least 8 characters
                                </li>
                                <li id="req-uppercase" class="flex items-center gap-2">
                                    <span class="text-gray-400">○</span> One uppercase letter (A-Z)
                                </li>
                                <li id="req-lowercase" class="flex items-center gap-2">
                                    <span class="text-gray-400">○</span> One lowercase letter (a-z)
                                </li>
                                <li id="req-number" class="flex items-center gap-2">
                                    <span class="text-gray-400">○</span> One number (0-9)
                                </li>
                                <li id="req-special" class="flex items-center gap-2">
                                    <span class="text-gray-400">○</span> One special character (@$!%*?&)
                                </li>
                            </ul>
                        </div>

                        @error('password')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                            Confirm New Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation"
                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition @error('password_confirmation') border-red-500 @enderror"
                                   placeholder="Re-enter your new password"
                                   required 
                                   minlength="8">
                            <button type="button" 
                                    onclick="togglePassword('password_confirmation')" 
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-700">
                                <svg id="password_confirmation_eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div id="password-match" class="mt-2 text-sm hidden"></div>
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 pt-6 border-t border-gray-200">
                        <button type="submit" 
                                class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Password
                        </button>
                        <button type="button" 
                                onclick="history.back()" 
                                class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition duration-200">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Tips Card -->
        <div class="mt-8 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Security Tips</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Never share your password with anyone</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Use a unique password that you don't use on other websites</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Change your password regularly to keep your account secure</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Avoid using personal information like birthdays or names</span>
                        </li>
                    </ul>
                </div>
            </div>
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
    const strengthDescription = document.getElementById('strength-description');
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

    // Password requirements checking
    function updateRequirements(password) {
        const requirements = {
            'req-length': password.length >= 8,
            'req-uppercase': /[A-Z]/.test(password),
            'req-lowercase': /[a-z]/.test(password),
            'req-number': /[0-9]/.test(password),
            'req-special': /[@$!%*?&]/.test(password)
        };

        Object.keys(requirements).forEach(reqId => {
            const element = document.getElementById(reqId);
            const span = element.querySelector('span');
            
            if (requirements[reqId]) {
                span.textContent = '✓';
                span.className = 'text-green-500 font-bold';
                element.classList.add('text-green-600');
            } else {
                span.textContent = '○';
                span.className = 'text-gray-400';
                element.classList.remove('text-green-600');
            }
        });

        return Object.values(requirements).filter(Boolean).length;
    }

    // Password strength checker
    function checkPasswordStrength(password) {
        const metRequirements = updateRequirements(password);
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[@$!%*?&]/.test(password)) strength++;

        return { strength, metRequirements };
    }

    // Update password strength display
    passwordInput.addEventListener('input', function() {
        const password = this.value;

        if (password.length > 0) {
            strengthContainer.classList.remove('hidden');
            const { strength, metRequirements } = checkPasswordStrength(password);

            let color, text, width, description;
            switch(strength) {
                case 0:
                case 1:
                    color = '#ef4444';
                    text = 'Very Weak';
                    width = '20%';
                    description = 'Add more characters and variety';
                    break;
                case 2:
                    color = '#f97316';
                    text = 'Weak';
                    width = '40%';
                    description = 'Try adding uppercase and numbers';
                    break;
                case 3:
                    color = '#eab308';
                    text = 'Fair';
                    width = '60%';
                    description = 'Almost there! Add a special character';
                    break;
                case 4:
                    color = '#22c55e';
                    text = 'Good';
                    width = '80%';
                    description = 'Strong password!';
                    break;
                case 5:
                    color = '#16a34a';
                    text = 'Excellent';
                    width = '100%';
                    description = 'Very secure password!';
                    break;
            }

            strengthBar.style.backgroundColor = color;
            strengthBar.style.width = width;
            strengthText.textContent = text;
            strengthText.style.color = color;
            strengthDescription.textContent = description;
        } else {
            strengthContainer.classList.add('hidden');
            // Reset requirements
            updateRequirements('');
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
                matchIndicator.className = 'mt-2 text-sm text-green-600 font-medium flex items-center gap-1';
                confirmInput.classList.remove('border-red-500');
                confirmInput.classList.add('border-green-500');
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.className = 'mt-2 text-sm text-red-600 font-medium flex items-center gap-1';
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
            alert('Passwords do not match. Please make sure both password fields contain the same value.');
            confirmInput.focus();
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            passwordInput.focus();
            return false;
        }

        // Check all requirements
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[@$!%*?&]/.test(password);

        if (!hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
            e.preventDefault();
            alert('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
            passwordInput.focus();
            return false;
        }
    });
});
</script>
@endsection
