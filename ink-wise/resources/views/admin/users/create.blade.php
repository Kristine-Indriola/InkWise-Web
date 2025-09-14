@extends('layouts.admin')

@section('title', 'Create Staff Account')

@section('content')
<div class="container">
    <div class="card">
        <link rel="stylesheet" href="{{ asset('css/edit-users.css') }}">
        <h2 class="form-title">➕ Create Staff Account</h2>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">🚫 {{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert warning">⚠️ {{ session('warning') }}</div>
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

        <form method="POST" action="{{ route('admin.users.store') }}" onsubmit="return confirmStaffLimit()">
            @csrf

            <!-- Role -->
            <div class="form-row">
                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="">-- Select Role --</option>
                        <option value="owner" {{ $ownerCount >= 1 ? 'disabled' : '' }}>
                            Owner {{ $ownerCount >= 1 ? '(Already Exists)' : '' }}
                        </option>
                        <option value="admin" {{ $adminCount >= 1 ? 'disabled' : '' }}>
                            Admin {{ $adminCount >= 1 ? '(Already Exists)' : '' }}
                        </option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
            </div>

            <!-- Personal Information -->
            <h3 class="section-title">👤 Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>

            <!-- Contact Number + Email -->
            <div class="form-row">
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                     <span class="field-error">{{ $message }}</span>
                        @enderror
                </div>
            </div>

            <!-- Password + Confirm Password -->
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>

            <!-- Address -->
            <h3 class="section-title">📍 Address</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Street</label>
                    <input type="text" name="street" value="{{ old('street') }}">
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <input type="text" name="barangay" value="{{ old('barangay') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city') }}">
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <input type="text" name="province" value="{{ old('province') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="{{ old('country', 'Philippines') }}">
                </div>
            </div>

            <!-- Hidden Status Field -->
            <input type="hidden" name="status" value="pending">

            <!-- Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">💼 Create Account</button>
                <a href="{{ url()->previous() }}" class="btn-secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

{{-- JavaScript Confirmation for Staff Limit --}}
<script>
function confirmStaffLimit() {
    const role = document.getElementById('role').value;
    const staffCount = @json($staffCount);

    if(role === 'staff' && staffCount >= 3) {
        return confirm("⚠️ Staff account limit of 3 has been reached. Do you still want to create another staff account?");
    }
    return true;
}
</script>
@endsection
