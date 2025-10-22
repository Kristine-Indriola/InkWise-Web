@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp
<link rel="stylesheet" href="{{ asset('css/admin-profile.css') }}">

<div class="profile-container">
    {{-- Avatar Section --}}
    @php
        $staff = optional($admin)->staff;
        $profileImage = $staff && $staff->profile_pic
            ? Storage::disk('public')->url($staff->profile_pic)
            : asset('images/no-image.png');
    @endphp
    <div class="text-center">
        <img src="{{ $profileImage }}"
             alt="Admin Avatar"
             class="profile-avatar">

        <h2>
            {{ optional($admin->staff)->first_name ?? '' }} {{ optional($admin->staff)->last_name ?? '' }}
        </h2>
        <p class="role">{{ $admin->role }}</p>
    </div>

    <div class="profile-divider"></div>

    {{-- User Info --}}
    <div class="profile-row">
        <div class="profile-col">
            <label class="profile-label">Email</label>
            <p class="profile-info">{{ $admin->email }}</p>
        </div>
        <div class="profile-col">
            <label class="profile-label">Status</label>
            <p class="profile-info">{{ $admin->status }}</p>
        </div>
    </div>

    {{-- Staff Info --}}
    @if($admin->staff)
    <h3 class="mb-4">Staff Details</h3>
    <div class="profile-row">
        <div class="profile-col">
            <label class="profile-label">First Name</label>
            <p class="profile-info">{{ $admin->staff->first_name }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Middle Name</label>
            <p class="profile-info">{{ $admin->staff->middle_name ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Last Name</label>
            <p class="profile-info">{{ $admin->staff->last_name }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Contact Number</label>
            <p class="profile-info">{{ $admin->staff->contact_number }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Role</label>
            <p class="profile-info">{{ $admin->staff->role }}</p>
        </div>
    </div>
    @else
        <p class="text-red-600 font-medium">⚠ No staff profile found for this admin.</p>
    @endif

    {{-- Address Info --}}
    @if($admin->address)
    <h3 class="mt-6 mb-4">Address</h3>
    <div class="profile-row">
        <div class="profile-col">
            <label class="profile-label">Street</label>
            <p class="profile-info">{{ $admin->address->street ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Barangay</label>
            <p class="profile-info">{{ $admin->address->barangay ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">City</label>
            <p class="profile-info">{{ $admin->address->city ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Province</label>
            <p class="profile-info">{{ $admin->address->province ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Postal Code</label>
            <p class="profile-info">{{ $admin->address->postal_code ?? '-' }}</p>
        </div>

        <div class="profile-col">
            <label class="profile-label">Country</label>
            <p class="profile-info">{{ $admin->address->country ?? '-' }}</p>
        </div>
    </div>
    @else
        <p class="text-red-600 font-medium mt-4">⚠ No address found for this admin.</p>
    @endif

    {{-- Action Buttons --}}
    <div class="flex justify-end mt-8 gap-4">
        <a href="{{ route('admin.profile.edit') }}" class="profile-btn">✏ Edit Profile</a>
        <a href="{{ route('admin.dashboard') }}" class="profile-cancel">⏎ Back</a>
    </div>
</div>
@endsection
