@extends('layouts.admin')

@section('title', 'Edit Admin Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-profile.css') }}">

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

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')

        {{-- Email --}}
        <div class="mb-4">
            <label class="profile-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="profile-input">
        </div>

        {{-- First Name --}}
        <div class="mb-4">
            <label class="profile-label">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name', $admin->staff->first_name ?? '') }}" class="profile-input">
        </div>

        {{-- Middle Name --}}
        <div class="mb-4">
            <label class="profile-label">Middle Name</label>
            <input type="text" name="middle_name" value="{{ old('middle_name', $admin->staff->middle_name ?? '') }}" class="profile-input">
        </div>

        {{-- Last Name --}}
        <div class="mb-4">
            <label class="profile-label">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $admin->staff->last_name ?? '') }}" class="profile-input">
        </div>

        {{-- Contact Number --}}
        <div class="mb-4">
            <label class="profile-label">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $admin->staff->contact_number ?? '') }}" class="profile-input">
        </div>

        {{-- Address Fields --}}
        <h3 class="text-lg font-semibold text-gray-700 mb-4 mt-6">📍 Address</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="profile-label">Street</label>
                <input type="text" name="street" value="{{ old('street', $admin->address->street ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Barangay</label>
                <input type="text" name="barangay" value="{{ old('barangay', $admin->address->barangay ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">City</label>
                <input type="text" name="city" value="{{ old('city', $admin->address->city ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Province</label>
                <input type="text" name="province" value="{{ old('province', $admin->address->province ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $admin->address->postal_code ?? '') }}" class="profile-input">
            </div>

            <div>
                <label class="profile-label">Country</label>
                <input type="text" name="country" value="{{ old('country', $admin->address->country ?? '') }}" class="profile-input">
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-4 mt-6">
            <label class="profile-label">New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="profile-input">
        </div>

        <div class="flex justify-end mt-6">
            <a href="{{ route('admin.profile.show') }}" class="profile-cancel mr-2">✖ Cancel</a>
            <button type="submit" class="profile-btn">💾 Save Changes</button>
        </div>
    </form>
</div>
@endsection
