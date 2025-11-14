
@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/profile.css') }}">
@endpush

@section('content')
@php
    $abbr = '';
    if (!empty($user->name)) {
        $parts = preg_split('/\s+/', trim($user->name));
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';
        $abbr = strtoupper(substr($first, 0, 1) . ($second ? substr($second, 0, 1) : ''));
    }
    $position = $user->staff->role ?? __('Staff Member');
@endphp

<main class="materials-page admin-page-shell profile-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Staff Account</h1>
            <p class="page-subtitle">Update your personal details to keep the InkWise records accurate.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.dashboard') }}" class="pill-link" aria-label="Back to dashboard"><i class="fi fi-rr-house-chimney"></i>&nbsp;Dashboard</a>
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

    <section class="profile-form-shell" aria-label="Edit staff profile">
        <div class="profile-form-header">
            <div class="profile-avatar-frame" aria-hidden="true">
                @if($user->staff && $user->staff->profile_pic)
                    <img src="{{ asset('storage/' . $user->staff->profile_pic) }}" alt="{{ $user->name }} profile photo">
                @else
                    <span>{{ $abbr ?: 'ST' }}</span>
                @endif
            </div>
            <div class="profile-form-header__details">
                <h2>{{ $user->name ?? 'Staff Member' }}</h2>
                <p>{{ $position }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('staff.profile.update') }}" enctype="multipart/form-data" id="staffProfileForm" class="profile-form">
            @csrf
            <div class="profile-form-grid">
                <div class="profile-field">
                    <label for="profileName">Full Name</label>
                    <input id="profileName" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profileEmail">Email</label>
                    <input id="profileEmail" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="profile-input" required>
                </div>
                <div class="profile-field">
                    <label for="profilePhone">Phone</label>
                    <input id="profilePhone" type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="profile-input" placeholder="e.g. 09XX-XXX-XXXX">
                </div>
                <div class="profile-field">
                    <label for="profileAddress">Address</label>
                    <input id="profileAddress" type="text" name="address" value="{{ old('address', $user->staff->address ?? '') }}" class="profile-input" placeholder="City / Province">
                </div>
                <div class="profile-field">
                    <label for="profilePicInput">Profile Picture</label>
                    <input id="profilePicInput" type="file" name="profile_pic" class="profile-input">
                    <p class="profile-avatar-upload-hint">PNG, JPG or GIF up to 5MB.</p>
                </div>
            </div>

            @if($user->staff && $user->staff->profile_pic)
                <div class="profile-current-photo">
                    <span class="profile-current-photo__label">Current photo</span>
                    <div class="profile-current-photo__preview">
                        <img src="{{ asset('storage/' . $user->staff->profile_pic) }}" alt="Current profile" class="profile-current-photo__img">
                        <span class="profile-current-photo__meta">Last updated {{ optional($user->staff->updated_at)->diffForHumans() }}</span>
                    </div>
                </div>
            @endif

            <div class="profile-form-actions">
                <a href="{{ route('staff.dashboard') }}" class="profile-cancel">Cancel</a>
                <button type="submit" class="profile-btn">Save Changes</button>
            </div>
        </form>
    </section>
</main>
@endsection
