@extends('layouts.admin')

@section('title', 'Admin Profile')

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
            <h1 class="page-title">Admin Profile</h1>
            <p class="page-subtitle">View and manage your administrator account details.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('admin.dashboard') }}" class="pill-link" aria-label="Back to dashboard"><i class="fi fi-rr-house-chimney"></i>&nbsp;Dashboard</a>
        </div>
    </header>

    <div class="profile-alert-stack" aria-live="polite">
        @if(session('success'))
            <div class="alert alert-success profile-success">✅ {{ session('success') }}</div>
        @endif
    </div>

    <section class="profile-form-shell" aria-label="Admin profile information">
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

        <div class="profile-info-grid">
            {{-- Account Information --}}
            <div class="profile-info-section">
                <h3 class="profile-section-title">👤 Account Information</h3>
                <div class="profile-info-grid">
                    <div class="profile-info-item">
                        <label class="profile-info-label">Email</label>
                        <p class="profile-info-value">{{ $admin->email }}</p>
                    </div>
                    <div class="profile-info-item">
                        <label class="profile-info-label">Status</label>
                        <p class="profile-info-value">{{ ucfirst($admin->status) }}</p>
                    </div>
                    <div class="profile-info-item">
                        <label class="profile-info-label">Role</label>
                        <p class="profile-info-value">{{ ucfirst($admin->role) }}</p>
                    </div>
                </div>
            </div>

            {{-- Personal Information --}}
            @if($admin->staff)
            <div class="profile-info-section">
                <h3 class="profile-section-title">📋 Personal Information</h3>
                <div class="profile-info-grid">
                    <div class="profile-info-item">
                        <label class="profile-info-label">First Name</label>
                        <p class="profile-info-value">{{ $admin->staff->first_name }}</p>
                    </div>
                    <div class="profile-info-item">
                        <label class="profile-info-label">Middle Name</label>
                        <p class="profile-info-value">{{ $admin->staff->middle_name ?? '-' }}</p>
                    </div>
                    <div class="profile-info-item">
                        <label class="profile-info-label">Last Name</label>
                        <p class="profile-info-value">{{ $admin->staff->last_name }}</p>
                    </div>
                    <div class="profile-info-item">
                        <label class="profile-info-label">Contact Number</label>
                        <p class="profile-info-value">{{ $admin->staff->contact_number }}</p>
                    </div>
                </div>
            </div>
            @else
                <div class="profile-info-section">
                    <div class="alert alert-warning">
                        <strong>⚠ Warning:</strong> No staff profile found for this admin account.
                    </div>
                </div>
            @endif

            {{-- Address Information --}}
            @if($admin->staff && $admin->staff->address)
            <div class="profile-info-section">
                <h3 class="profile-section-title">📍 Address Information</h3>
                <div class="profile-info-grid">
                    <div class="profile-info-item">
                        <label class="profile-info-label">Address</label>
                        <p class="profile-info-value">{{ $admin->staff->address }}</p>
                    </div>
                </div>
            </div>
            @else
                <div class="profile-info-section">
                    <div class="alert alert-info">
                        <strong>ℹ Info:</strong> No address information found for this admin account.
                    </div>
                </div>
            @endif
        </div>

        <div class="profile-form-actions">
            <a href="{{ route('admin.profile.edit') }}" class="profile-btn">Edit Profile</a>
        </div>
    </section>
</main>
@endsection
