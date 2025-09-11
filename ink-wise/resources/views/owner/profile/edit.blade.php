@extends('layouts.owner.app')

@section('title', 'Edit owner Profile')

@section('content')
@include('layouts.owner.sidebar')

<section class="main-content">
<div class="topbar">
  <div class="welcome-text"><strong>Welcome, Owner!</strong></div>
  <div class="topbar-actions">
    <button type="button" class="icon-btn" aria-label="Notifications">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
        <path d="M10 21a2 2 0 0 0 4 0"/>
      </svg>
      <span class="badge">2</span> 
    </button>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="logout-btn">
        Logout
      </button>
    </form>
  </div>
</div>

<link rel="stylesheet" href="{{ asset('css/owner/owner-profile-edit.css') }}">

<div class="profile-container">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">✏ Edit Profile</h2>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="profile-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li class="profile-error">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('owner.profile.update') }}">
        @csrf
        @method('PUT')

        {{-- Email --}}
        <div class="mb-4">
            <label class="profile-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $owner->email) }}" class="profile-input">
        </div>

        {{-- First Name --}}
        <div class="mb-4">
            <label class="profile-label">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name', $owner->staff->first_name ?? '') }}" class="profile-input">
        </div>

        {{-- Middle Name --}}
        <div class="mb-4">
            <label class="profile-label">Middle Name</label>
            <input type="text" name="middle_name" value="{{ old('middle_name', $owner->staff->middle_name ?? '') }}" class="profile-input">
        </div>

        {{-- Last Name --}}
        <div class="mb-4">
            <label class="profile-label">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $owner->staff->last_name ?? '') }}" class="profile-input">
        </div>

        {{-- Contact Number --}}
        <div class="mb-4">
            <label class="profile-label">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $owner->staff->contact_number ?? '') }}" class="profile-input">
        </div>

        {{-- Address Fields --}}
        <h3 class="text-lg font-semibold text-gray-700 mb-4 mt-6">📍 Address</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="profile-label">Street</label>
                <input type="text" name="street" value="{{ old('street', $owner->address->street ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Barangay</label>
                <input type="text" name="barangay" value="{{ old('barangay', $owner->address->barangay ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">City</label>
                <input type="text" name="city" value="{{ old('city', $owner->address->city ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Province</label>
                <input type="text" name="province" value="{{ old('province', $owner->address->province ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $owner->address->postal_code ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Country</label>
                <input type="text" name="country" value="{{ old('country', $owner->address->country ?? '') }}" class="profile-input">
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-4 mt-6">
            <label class="profile-label">New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="profile-input">
        </div>

        <div class="flex justify-end mt-6">
            <a href="{{ route('owner.profile.show') }}" class="profile-cancel mr-2">✖ Cancel</a>
            <button type="submit" class="profile-btn">💾 Save Changes</button>
        </div>
    </form>
</div>
</section>
@endsection
