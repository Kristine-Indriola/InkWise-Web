@extends('layouts.admin')

@section('title', 'Staff Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff_profiles.css') }}">
@endpush

@section('content')
<div class="profile-wrapper">

    {{-- Back Button --}}
    <a href="{{ route('admin.users.index') }}" class="btn-back">← Back to Staff List</a>

    <div class="profile-card">
        {{-- Header --}}
        <div class="profile-header">
            <div class="avatar">
                {{ strtoupper(substr($user->staff->first_name, 0, 1)) }}
            </div>
            <div class="profile-header-info">
                <h2>{{ $user->staff->first_name }} {{ $user->staff->middle_name }} {{ $user->staff->last_name }}</h2>
                <p class="role-badge {{ $user->role }}">{{ ucfirst($user->role) }}</p>
                <p class="status-badge {{ $user->staff->status }}">{{ ucfirst($user->staff->status ?? '-') }}</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'general')">General Info</button>
            <button class="tab-btn" onclick="openTab(event, 'contact')">Contact & Address</button>
            <button class="tab-btn" onclick="openTab(event, 'actions')">Actions</button>
        </div>

        {{-- Tab Content: General Info --}}
        <div id="general" class="tab-content active">
            <div class="profile-info">
                <div><span>Staff ID</span> <p>{{ $user->staff->staff_id }}</p></div>
                <div><span>Email</span> <p>{{ $user->email }}</p></div>
                <div class="password-section">
                    <span>Password</span>
                    <div class="password-group">
                        <input type="password" id="passwordField" 
                               value="{{ $user->plain_password ?? '********' }}" readonly>
                        <button type="button" onclick="togglePassword()" id="toggleBtn">👁</button>
                    </div>
                </div>
                <div><span>Role</span> <p>{{ ucfirst($user->role) }}</p></div>
                <div><span>Status</span> <p>{{ ucfirst($user->staff->status ?? '-') }}</p></div>
            </div>
        </div>

        {{-- Tab Content: Contact --}}
        <div id="contact" class="tab-content">
            <div class="profile-info">
                <div><span>Contact</span> <p>{{ $user->staff->contact_number ?? '-' }}</p></div>
                <div><span>Address</span>
                    <p>
    @if($user->staff && $user->staff->address)
        {{ $user->staff->address->street }},
        {{ $user->staff->address->barangay }},
        {{ $user->staff->address->city }},
        {{ $user->staff->address->province }}
    @else
        <span class="text-gray-400 italic">No address</span>
    @endif
</p>
                </div>
            </div>
        </div>

        {{-- Tab Content: Actions --}}
        <div id="actions" class="tab-content">
    <div class="profile-actions">
        @if(($user->staff)->status !== 'archived')
            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-edit">✏ Edit</a>
            <form method="POST" action="{{ route('admin.users.destroy', $user->user_id) }}" 
                  onsubmit="return confirm('Are you sure you want to archive this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-delete">📦 Archive</button>
            </form>
        @else
            <span class="badge bg-secondary">📦 Archived Account</span>
        @endif
    </div>
</div>
    </div>
</div>

<script>
function togglePassword() {
    let field = document.getElementById("passwordField");
    let btn = document.getElementById("toggleBtn");
    if (field.type === "password") {
        field.type = "text";
        btn.textContent = "🙈";
    } else {
        field.type = "password";
        btn.textContent = "👁";
    }
}

function openTab(evt, tabId) {
    let tabContent = document.querySelectorAll(".tab-content");
    let tabButtons = document.querySelectorAll(".tab-btn");

    tabContent.forEach(tc => tc.classList.remove("active"));
    tabButtons.forEach(tb => tb.classList.remove("active"));

    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>
@endsection
