@extends('layouts.admin')

@section('title', 'Edit Admin Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/profile.css') }}">
@endpush

@section('content')
@php
    // Generate initials from separate name components
    $abbr = '';
    if ($admin->staff && $admin->staff->first_name) {
        $first = $admin->staff->first_name;
        $last = $admin->staff->last_name ?? '';
        $abbr = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    } elseif (!empty($admin->name)) {
        // Fallback to splitting full name
        $parts = preg_split('/\s+/', trim($admin->name));
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';
        $abbr = strtoupper(substr($first, 0, 1) . ($second ? substr($second, 0, 1) : ''));
    }
    $position = $admin->staff->role ?? __('Administrator');
    
    // Map role to display name
    if ($admin->staff && $admin->staff->role) {
        switch (strtolower($admin->staff->role)) {
            case 'admin':
                $position = 'Admin';
                break;
            case 'staff':
                $position = 'Staff';
                break;
            default:
                $position = ucfirst($admin->staff->role);
        }
    }

    // Construct display name from separate components
    $displayName = '';
    if ($admin->staff && $admin->staff->first_name) {
        $displayName = trim($admin->staff->first_name . ' ' . ($admin->staff->middle_name ? $admin->staff->middle_name . ' ' : '') . $admin->staff->last_name);
    } else {
        $displayName = $admin->name ?? 'Administrator';
    }
@endphp

<main class="materials-page admin-page-shell profile-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Admin Account</h1>
            <p class="page-subtitle">Update your personal details to keep the InkWise records accurate.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('admin.dashboard') }}" class="pill-link" aria-label="Back to dashboard"><i class="fi fi-rr-house-chimney"></i>&nbsp;Dashboard</a>
        </div>
    </header>

    <div class="profile-alert-stack" aria-live="polite">
        @if(session('success'))
            <div class="alert alert-success profile-success">✅ {{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>We found a few things to fix:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <section class="profile-form-shell" aria-label="Edit admin profile">
        <div class="profile-form-header">
            <div class="profile-avatar-frame" aria-hidden="true">
                @if($admin->staff && $admin->staff->profile_pic)
                    <img src="{{ asset('storage/' . $admin->staff->profile_pic) }}" alt="{{ $admin->name }} profile photo">
                @else
                    <span>{{ $abbr ?: 'AD' }}</span>
                @endif
            </div>
            <div class="profile-form-header__details">
                <h2>{{ $displayName }}</h2>
                <p>{{ $position }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" id="adminProfileForm" class="profile-form">
            @csrf
            @method('PUT')
            <div class="profile-form-grid">
                <div class="profile-field">
                    <label for="profileFirstName">First Name</label>
                    <input id="profileFirstName" type="text" name="first_name" value="{{ old('first_name', $admin->staff->first_name ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profileMiddleName">Middle Name</label>
                    <input id="profileMiddleName" type="text" name="middle_name" value="{{ old('middle_name', $admin->staff->middle_name ?? '') }}" class="profile-input" placeholder="Optional">
                </div>
                <div class="profile-field">
                    <label for="profileLastName">Last Name</label>
                    <input id="profileLastName" type="text" name="last_name" value="{{ old('last_name', $admin->staff->last_name ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profileEmail">Email</label>
                    <input id="profileEmail" type="email" name="email" value="{{ old('email', $admin->email ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profilePhone">Phone</label>
                    <input id="profilePhone" type="text" name="contact_number" value="{{ old('contact_number', $admin->staff->contact_number ?? '') }}" class="profile-input" placeholder="e.g. 09XX-XXX-XXXX">
                </div>
                <div class="profile-field">
                    <label for="profilePassword">New Password</label>
                    <input id="profilePassword" type="password" name="password" class="profile-input" placeholder="Leave blank to keep current">
                </div>
                <div class="profile-field">
                    <label for="profilePasswordConfirmation">Confirm Password</label>
                    <input id="profilePasswordConfirmation" type="password" name="password_confirmation" class="profile-input" placeholder="Confirm new password">
                </div>
                <div class="profile-field">
                    <label for="profilePicInput">Profile Picture</label>
                    <input id="profilePicInput" type="file" name="profile_pic" class="profile-input">
                    <p class="profile-avatar-upload-hint">PNG, JPG or GIF up to 5MB.</p>
                </div>
            </div>

                <div class="profile-field">
                    <label for="profileAddress">Address</label>
                    <input id="profileAddress" type="text" name="address" value="{{ old('address', $admin->staff->address ?? '') }}" class="profile-input" placeholder="City / Province">
                </div>

            @if($admin->staff && $admin->staff->profile_pic)
                <div class="profile-current-photo">
                    <span class="profile-current-photo__label">Current photo</span>
                    <div class="profile-current-photo__preview">
                        <img src="{{ asset('storage/' . $admin->staff->profile_pic) }}" alt="Current profile" class="profile-current-photo__img">
                        <span class="profile-current-photo__meta">Last updated {{ optional($admin->staff->updated_at)->diffForHumans() }}</span>
                    </div>
                </div>
            @endif

            <div class="profile-form-actions">
                <a href="{{ route('admin.dashboard') }}" class="profile-cancel">Cancel</a>
                <button type="submit" class="profile-btn">Save Changes</button>
            </div>
        </form>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const profilePicInput = document.getElementById('profilePicInput');
    const avatarFrame = document.querySelector('.profile-avatar-frame');
    const currentPhotoPreview = document.querySelector('.profile-current-photo__img');

    if (profilePicInput && avatarFrame) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (PNG, JPG, or GIF).');
                    profilePicInput.value = '';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5120 * 1024) {
                    alert('File size must be less than 5MB.');
                    profilePicInput.value = '';
                    return;
                }

                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update the avatar frame
                    let img = avatarFrame.querySelector('img');
                    if (!img) {
                        // Remove text content and add image
                        avatarFrame.innerHTML = '';
                        img = document.createElement('img');
                        img.alt = 'Profile preview';
                        avatarFrame.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';

                    // Update current photo preview if it exists
                    if (currentPhotoPreview) {
                        currentPhotoPreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Form validation
    const form = document.getElementById('adminProfileForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const firstNameInput = document.getElementById('profileFirstName');
            const lastNameInput = document.getElementById('profileLastName');
            const emailInput = document.getElementById('profileEmail');
            const passwordInput = document.getElementById('profilePassword');
            const passwordConfirmationInput = document.getElementById('profilePasswordConfirmation');

            if (firstNameInput && firstNameInput.value.trim() === '') {
                alert('First name is required.');
                e.preventDefault();
                firstNameInput.focus();
                return;
            }

            if (lastNameInput && lastNameInput.value.trim() === '') {
                alert('Last name is required.');
                e.preventDefault();
                lastNameInput.focus();
                return;
            }

            if (emailInput && emailInput.value.trim() === '') {
                alert('Email is required.');
                e.preventDefault();
                emailInput.focus();
                return;
            }

            // Check password confirmation if password is provided
            if (passwordInput && passwordInput.value && passwordConfirmationInput && passwordInput.value !== passwordConfirmationInput.value) {
                alert('Password confirmation does not match.');
                e.preventDefault();
                passwordConfirmationInput.focus();
                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.textContent = 'Saving...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>
@endpush
