@extends('layouts.admin')

@section('title', 'Create Staff Account')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/staff-form.css') }}">
@endpush

@section('content')
<main class="admin-page-shell staff-form-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Create Staff Account</h1>
            <p class="page-subtitle">Set a role, contact information, and access credentials for a new staff member.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="pill-link" aria-label="Back to staff list">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Staff</span>
        </a>
    </header>

    @if(session('success'))
        <div class="form-alert form-alert--success" role="alert" aria-live="polite">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="form-alert form-alert--error" role="alert" aria-live="assertive">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="form-alert form-alert--warning" role="alert" aria-live="polite">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="form-alert form-alert--error" role="alert" aria-live="assertive">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
                <p class="form-alert__title">Please fix the following:</p>
                <ul class="form-alert__list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="staff-form-card" onsubmit="return confirmStaffLimit()">
        @csrf

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-id-badge" aria-hidden="true"></i>
                <span>Role & Access</span>
            </h2>
            <div class="form-grid form-grid--single">
                <div class="form-field">
                    <label for="role">Role</label>
                    <select name="role" id="role" required>
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select role</option>
                        <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }} {{ $ownerCount >= 1 ? 'disabled' : '' }}>Owner {{ $ownerCount >= 1 ? '(already assigned)' : '' }}</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }} {{ $adminCount >= 1 ? 'disabled' : '' }}>Admin {{ $adminCount >= 1 ? '(already assigned)' : '' }}</option>
                        <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span>Personal Information</span>
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="form-field">
                    <label for="middle_name">Middle Name <span class="optional">(optional)</span></label>
                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                </div>
                <div class="form-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
                </div>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <span>Credentials</span>
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                <span>Address</span>
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="street">Street</label>
                    <input type="text" id="street" name="street" value="{{ old('street') }}">
                </div>
                <div class="form-field">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}">
                </div>
                <div class="form-field">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city') }}">
                </div>
                <div class="form-field">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" value="{{ old('province') }}">
                </div>
                <div class="form-field">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
                </div>
                <div class="form-field">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="{{ old('country', 'Philippines') }}">
                </div>
            </div>
        </section>

        <input type="hidden" name="status" value="pending">

        <footer class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                <span>Create Account</span>
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Cancel</span>
            </a>
        </footer>
    </form>

    <script>
        function confirmStaffLimit() {
            const role = document.getElementById('role').value;
            const staffCount = @json($staffCount);
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;

            if (password !== confirmPassword) {
                alert('Password and confirmation do not match.');
                return false;
            }

            if (role === 'staff' && staffCount >= 3) {
                return confirm('Staff account limit of 3 has been reached. Do you still want to create another staff account?');
            }

            return true;
        }
    </script>
</main>
@endsection
