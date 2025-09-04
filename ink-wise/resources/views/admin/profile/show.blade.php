@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-profile.css') }}">

<div class="profile-container">
    {{-- Profile Picture --}}
    <div class="flex flex-col items-center">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(optional($admin->staff)->first_name . ' ' . optional($admin->staff)->last_name ?? $admin->email) }}&background=4F46E5&color=fff&bold=true" 
             alt="Admin Avatar" 
             class="profile-avatar">

        <h2 class="mt-4 text-2xl font-bold text-gray-800">
            {{ optional($admin->staff)->first_name ?? '' }} {{ optional($admin->staff)->last_name ?? '' }}
        </h2>
        <p class="text-gray-500 capitalize">{{ $admin->role }}</p>
    </div>

    <hr class="my-8 border-gray-300">

    <div class="space-y-6">
        {{-- User Info --}}
        <div>
            <label class="profile-label">Email</label>
            <p class="profile-info">{{ $admin->email }}</p>
        </div>

        {{-- Staff Info --}}
        @if($admin->staff)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="profile-label">First Name</label>
                    <p class="profile-info">{{ $admin->staff->first_name }}</p>
                </div>

                <div>
                    <label class="profile-label">Middle Name</label>
                    <p class="profile-info">{{ $admin->staff->middle_name ?? '-' }}</p>
                </div>

                <div>
                    <label class="profile-label">Last Name</label>
                    <p class="profile-info">{{ $admin->staff->last_name }}</p>
                </div>

                <div>
                    <label class="profile-label">Contact Number</label>
                    <p class="profile-info">{{ $admin->staff->contact_number }}</p>
                </div>
            </div>
        @else
            <p class="text-red-600 font-medium">⚠ No staff profile found for this admin.</p>
        @endif
    </div>

    <div class="flex justify-end mt-8">
        <a href="{{ route('admin.profile.edit') }}" class="profile-btn">
            ✏ Edit Profile
        </a>
    </div>
</div>
@endsection
