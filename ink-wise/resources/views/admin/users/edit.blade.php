@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="container">
    <div class="card">
        <link rel="stylesheet" href="{{ asset('css/edit-users.css') }}">
        <h2 class="form-title">✏️ Edit User</h2>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert error">
                🚫 {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert warning">
                ⚠️ {{ session('warning') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>⚠️ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user->user_id) }}" onsubmit="return confirmStaffLimit()">
            @csrf
            @method('PUT')

            <!-- Role -->
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="">-- Select Role --</option>

                    {{-- Owner --}}
                    <option value="owner"
                        {{ $user->role === 'owner' ? 'selected' : '' }}
                        {{ $ownerCount - ($user->role === 'owner' ? 1 : 0) >= 1 ? 'disabled' : '' }}>
                        Owner {{ $ownerCount - ($user->role === 'owner' ? 1 : 0) >= 1 ? '(Already Exists)' : '' }}
                    </option>

                    {{-- Admin --}}
                    <option value="admin"
                        {{ $user->role === 'admin' ? 'selected' : '' }}
                        {{ $adminCount - ($user->role === 'admin' ? 1 : 0) >= 1 ? 'disabled' : '' }}>
                        Admin {{ $adminCount - ($user->role === 'admin' ? 1 : 0) >= 1 ? '(Already Exists)' : '' }}
                    </option>

                    {{-- Staff --}}
                    <option value="staff"
                        {{ $user->role === 'staff' ? 'selected' : '' }}>
                        Staff
                    </option>
                </select>
            </div>

            <!-- Name fields -->
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->staff->first_name ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label>Middle Name <small>(optional)</small></label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $user->staff->middle_name ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->staff->last_name ?? '') }}" required>
                </div>
            </div>

            <!-- Contact -->
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $user->staff->contact_number ?? '') }}" required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <!-- Address -->
            <h3 class="section-title">📍 Address</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Street</label>
                    <input type="text" name="street" value="{{ old('street', $user->staff->address->street ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <input type="text" name="barangay" value="{{ old('barangay', $user->staff->address->barangay ?? '') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city', $user->staff->address->city ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <input type="text" name="province" value="{{ old('province', $user->staff->address->province ?? '') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $user->staff->address->postal_code ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="{{ old('country', $user->staff->address->country ?? 'Philippines') }}">
                </div>
            </div>

            <!-- Status -->
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">💾 Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

{{-- JavaScript Confirmation for Staff Limit --}}
<script>
function confirmStaffLimit() {
    const role = document.getElementById('role').value;
    @if($staffCount >= 3)
        if(role === 'staff' && {{ $user->role === 'staff' ? 'false' : 'true' }}) {
            return confirm("⚠️ Staff account limit reached. Do you still want to assign this role?");
        }
    @endif
    return true;
}
</script>

@endsection
