@extends('layouts.owner.app')

@section('title', 'Edit Owner Profile')

@push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
        <link rel="stylesheet" href="{{ asset('css/staff-css/profile.css') }}">
        <style>
            /* Shift the header content slightly left on owner edit page */
            .owner-page-header > div:first-child {
                /* move right 288px and down 96px (moved further right) */
                transform: translate(288px, 96px);
                /* ensure translation doesn't affect layout height */
                display: inline-block;
            }
            /* Move the container card down */
            .profile-form-shell {
                margin-top: 100px;
            }
            /* Move error message to the right */
            .profile-alert-stack .alert {
                margin-left: 250px;
                margin-top: 100px;
            }
        </style>
@endpush

@section('content')
@include('layouts.owner.sidebar')
@php
    $staff = optional($owner->staff);
    $address = optional($owner->address);

    $abbr = '';
    if ($staff->first_name) {
        $first = $staff->first_name;
        $last = $staff->last_name ?? '';
        $abbr = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    } elseif (!empty($owner->name)) {
        $parts = preg_split('/\s+/', trim($owner->name));
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';
        $abbr = strtoupper(substr($first, 0, 1) . ($second ? substr($second, 0, 1) : ''));
    }

    $displayName = $staff->first_name
        ? trim($staff->first_name . ' ' . ($staff->middle_name ? $staff->middle_name . ' ' : '') . $staff->last_name)
        : ($owner->name ?? 'Owner');
@endphp

<main class="materials-page admin-page-shell profile-page" role="main">
    <header class="page-header owner-page-header">
        <div>
            <h1 class="page-title">Owner Account</h1>
            <p class="page-subtitle">Update your personal details to keep the InkWise records accurate.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('owner.home') }}" class="pill-link" aria-label="Back to dashboard"><i class="fi fi-rr-house-chimney"></i>&nbsp;Dashboard</a>
            <a href="{{ route('owner.profile.show') }}" class="pill-link" aria-label="Back to profile"><i class="fi fi-rr-arrow-left"></i>&nbsp;Back</a>
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

    <section class="profile-form-shell" aria-label="Edit owner profile">
        <div class="profile-form-header">
            <div class="profile-avatar-frame" aria-hidden="true">
                @if($staff->profile_pic)
                    <img src="{{ asset('storage/' . $staff->profile_pic) }}" alt="{{ $displayName }} profile photo">
                @else
                    <span>{{ $abbr ?: 'OW' }}</span>
                @endif
            </div>
            <div class="profile-form-header__details">
                <h2>{{ $displayName }}</h2>
                <p>Owner</p>
            </div>
        </div>

        <form method="POST" action="{{ route('owner.profile.update') }}" enctype="multipart/form-data" id="ownerProfileForm" class="profile-form">
            @csrf
            @method('PUT')
            <div class="profile-form-grid">
                <div class="profile-field">
                    <label for="profileFirstName">First Name</label>
                    <input id="profileFirstName" type="text" name="first_name" value="{{ old('first_name', $staff->first_name ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profileMiddleName">Middle Name</label>
                    <input id="profileMiddleName" type="text" name="middle_name" value="{{ old('middle_name', $staff->middle_name ?? '') }}" class="profile-input" placeholder="Optional">
                </div>
                <div class="profile-field">
                    <label for="profileLastName">Last Name</label>
                    <input id="profileLastName" type="text" name="last_name" value="{{ old('last_name', $staff->last_name ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profileEmail">Email</label>
                    <input id="profileEmail" type="email" value="owner@test.com" class="profile-input" readonly>
                </div>
                <div class="profile-field">
                    <label for="profilePhone">Phone</label>
                    <input id="profilePhone" type="text" name="contact_number" value="{{ old('contact_number', $staff->contact_number ?? '') }}" class="profile-input" placeholder="e.g. 09XX-XXX-XXXX">
                </div>
                <div class="profile-field">
                    <label for="profileAddress">Address</label>
                    <input id="profileAddress" type="text" name="address" value="{{ old('address', $staff->address ?? ($address->street ?? '')) }}" class="profile-input" placeholder="City / Province">
                </div>
                <div class="profile-field">
                    <label for="profilePicInput">Profile Picture</label>
                    <input id="profilePicInput" type="file" name="profile_pic" class="profile-input">
                    <p class="profile-avatar-upload-hint">PNG, JPG or GIF up to 5MB.</p>
                </div>
            </div>

            @if($staff->profile_pic)
                <div class="profile-current-photo">
                    <span class="profile-current-photo__label">Current photo</span>
                    <div class="profile-current-photo__preview">
                        <img src="{{ asset('storage/' . $staff->profile_pic) }}" alt="Current profile" class="profile-current-photo__img">
                        <span class="profile-current-photo__meta">Last updated {{ optional($staff->updated_at)->diffForHumans() }}</span>
                    </div>
                </div>
            @endif

            <div class="profile-form-actions">
                <a href="{{ route('owner.profile.show') }}" class="profile-cancel">Cancel</a>
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
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (PNG, JPG, or GIF).');
                    profilePicInput.value = '';
                    return;
                }

                if (file.size > 5120 * 1024) {
                    alert('File size must be less than 5MB.');
                    profilePicInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(ev) {
                    let img = avatarFrame.querySelector('img');
                    if (!img) {
                        avatarFrame.innerHTML = '';
                        img = document.createElement('img');
                        img.alt = 'Profile preview';
                        avatarFrame.appendChild(img);
                    }
                    img.src = ev.target.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';

                    if (currentPhotoPreview) {
                        currentPhotoPreview.src = ev.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Autosave functionality
    let autosaveTimeout;
    const form = document.getElementById('ownerProfileForm');
    const inputs = form.querySelectorAll('input:not([type="file"])'); // Exclude file input for autosave

    function autosave() {
        const formData = new FormData(form);
        formData.append('_method', 'PUT'); // For Laravel PUT

        fetch('{{ route("owner.profile.update") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Autosaved successfully');
                // Optionally show a small indicator
            } else {
                console.log('Autosave failed:', data.errors);
            }
        })
        .catch(error => {
            console.log('Autosave error:', error);
        });
    }

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autosaveTimeout);
            autosaveTimeout = setTimeout(autosave, 2000); // Save after 2 seconds of inactivity
        });
    });

    const formSubmit = form.addEventListener('submit', function(e) {
        const firstNameInput = document.getElementById('profileFirstName');
        const lastNameInput = document.getElementById('profileLastName');

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

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;
        }
    });
});
</script>
@endpush
