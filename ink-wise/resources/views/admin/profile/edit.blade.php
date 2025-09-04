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

        {{-- Password --}}
        <div class="mb-4">
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
