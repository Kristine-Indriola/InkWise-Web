@extends('layouts.owner.app')

@section('title', 'Owner Profile')

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

<link rel="stylesheet" href="{{ asset('css/owner/owner-profile.css') }}">

<div class="profile-container">
    <div class="flex flex-col items-center text-center">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(optional($owner->staff)->first_name . ' ' . optional($owner->staff)->last_name ?? $owner->email) }}&background=4F46E5&color=fff&bold=true" 
             alt="Owner Avatar" 
             class="profile-avatar">

        <h2 class="mt-4 text-2xl font-bold text-gray-800">
            {{ optional($owner->staff)->first_name ?? '' }} {{ optional($owner->staff)->last_name ?? '' }}
        </h2>
        <p class="text-gray-500 capitalize">{{ $owner->role }}</p>
    </div>

    <hr class="my-8 border-gray-300">

    <div class="space-y-10">
        <div>
            <h3 class="section-title">Account Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="profile-label">Email</label>
                    <p class="profile-info">{{ $owner->email }}</p>
                </div>
            </div>
        </div>

        {{-- Staff Info --}}
        <div>
            <h3 class="section-title">Personal Information</h3>
            @if($owner->staff)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="profile-label">First Name</label>
                        <p class="profile-info">{{ $owner->staff->first_name }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Middle Name</label>
                        <p class="profile-info">{{ $owner->staff->middle_name ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Last Name</label>
                        <p class="profile-info">{{ $owner->staff->last_name }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Contact Number</label>
                        <p class="profile-info">{{ $owner->staff->contact_number }}</p>
                    </div>
                </div>
            @else
                <p class="text-red-600 font-medium">⚠ No staff profile found for this owner.</p>
            @endif
        </div>

        {{-- Address Info --}}
        <div>
            <h3 class="section-title">📍Address Information</h3>
            @if($owner->address)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="profile-label">Street</label>
                        <p class="profile-info">{{ $owner->address->street ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Barangay</label>
                        <p class="profile-info">{{ $owner->address->barangay ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">City</label>
                        <p class="profile-info">{{ $owner->address->city ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Province</label>
                        <p class="profile-info">{{ $owner->address->province ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Postal Code</label>
                        <p class="profile-info">{{ $owner->address->postal_code ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="profile-label">Country</label>
                        <p class="profile-info">{{ $owner->address->country ?? '-' }}</p>
                    </div>
                </div>
            @else
                <p class="text-red-600 font-medium">⚠ No address found for this owner.</p>
            @endif
        </div>
    </div>

    {{-- Edit Button --}}
    <div class="flex justify-end mt-10">
        <a href="{{ route('owner.profile.edit') }}" class="profile-btn">
            ✏ Edit Profile
        </a>
    </div>
</div>
</section>
@endsection
