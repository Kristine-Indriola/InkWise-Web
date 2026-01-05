@extends('layouts.owner.app')

@section('title', 'Owner Profile')

@section('content')
@include('layouts.owner.sidebar')

@php
  $owner   = $owner ?? auth('owner')->user();
  $staff   = optional($owner?->staff);
  $address = optional($owner?->address);
  $fullName = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
  $memberSince = optional($owner?->created_at)->format('F d, Y');
  $initials = collect(explode(' ', $fullName !== '' ? $fullName : ($owner->name ?? 'Owner')))
    ->filter(fn ($segment) => strlen($segment) > 0)
    ->map(fn ($segment) => substr($segment, 0, 1))
    ->join('');
  if ($initials === '') {
    $initials = substr($owner->email ?? 'OW', 0, 2);
  }
  $initials = strtoupper(substr($initials, 0, 2));
  $avatarUrl = $staff->profile_pic ? asset('storage/' . ltrim($staff->profile_pic, '/')) : null;
@endphp

<section class="main-content">
  <link rel="stylesheet" href="{{ asset('css/owner/owner-profile.css') }}">
  <style>
    .profile-hero__avatar--fallback {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      background: linear-gradient(135deg, #6a2ebc, #3cd5c8);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 28px;
    }
  </style>

  <div class="profile-page">
    <!-- Hero -->
    <div class="profile-hero">
      <div class="profile-hero__main">
        <div class="profile-hero__avatar-wrap">
          @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="Owner Avatar" class="profile-hero__avatar" style="object-fit:cover;">
          @else
            <div class="profile-hero__avatar profile-hero__avatar--fallback">{{ $initials }}</div>
          @endif
        </div>
        <div>
          <h1 class="profile-hero__title">{{ $fullName !== '' ? $fullName : 'Owner' }}</h1>
          <p class="profile-hero__subtitle">{{ ucfirst($owner->role ?? 'owner') }}</p>

          <div class="profile-meta">
            <span class="profile-meta__chip">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16v16H4z" />
                <path d="M4 10h16" />
                <path d="M10 4v16" />
              </svg>
              {{ $owner->email }}
            </span>
            @if($memberSince)
              <span class="profile-meta__chip">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" />
                  <path d="M16 2v4" />
                  <path d="M8 2v4" />
                  <path d="M3 10h18" />
                </svg>
                Member since {{ $memberSince }}
              </span>
            @endif
          </div>
        </div>
        <div style="margin-left:18px; display:flex; align-items:center; gap:12px;">
          <a href="{{ route('owner.profile.edit') }}" class="pill-link" style="background:linear-gradient(135deg,#4f8bff,#6ec8ff); color:#fff; padding:8px 12px; border-radius:10px; font-weight:700; text-decoration:none;">Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Body Cards -->
    <div class="profile-grid">
      <section class="profile-card">
        <h3 class="profile-card__title">Account Information</h3>
        <div class="info-list">
          <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value">{{ $owner->email }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Role</span>
            <span class="info-value">{{ ucfirst($owner->role ?? 'Owner') }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Account Status</span>
            <span class="info-value info-value--badge">Active</span>
          </div>
        </div>
      </section>

      <section class="profile-card">
        <h3 class="profile-card__title">Personal Information</h3>
        @if($staff->exists())
          <div class="info-list info-list--grid">
            <div class="info-item">
              <span class="info-label">First Name</span>
              <span class="info-value">{{ $staff->first_name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Middle Name</span>
              <span class="info-value">{{ $staff->middle_name ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Last Name</span>
              <span class="info-value">{{ $staff->last_name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Contact Number</span>
              <span class="info-value">{{ $staff->contact_number ?? '—' }}</span>
            </div>
          </div>
        @else
          <div class="profile-card__empty">
            <span>⚠ We couldn’t find additional personal details for this owner.</span>
          </div>
        @endif
      </section>

      <section class="profile-card">
        <h3 class="profile-card__title">Address Information</h3>
        @if($address->exists())
          <div class="info-list info-list--grid">
            <div class="info-item">
              <span class="info-label">Street</span>
              <span class="info-value">{{ $address->street ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Barangay</span>
              <span class="info-value">{{ $address->barangay ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">City</span>
              <span class="info-value">{{ $address->city ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Province</span>
              <span class="info-value">{{ $address->province ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Postal Code</span>
              <span class="info-value">{{ $address->postal_code ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Country</span>
              <span class="info-value">{{ $address->country ?? '—' }}</span>
            </div>
          </div>
        @else
          <div class="profile-card__empty">
            <span>⚠ No address information has been added yet.</span>
          </div>
        @endif
      </section>
    </div>
  </div>
</section>
@endsection
