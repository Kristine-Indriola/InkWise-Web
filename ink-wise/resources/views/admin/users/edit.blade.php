@extends('layouts.admin')

@section('title', 'Edit User')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/staff-form.css') }}">
@endpush

@section('content')
<main class="admin-page-shell staff-form-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Edit Staff Account</h1>
            <p class="page-subtitle">Update profile details, access, and credentials for {{ $user->staff->first_name ?? 'this staff member' }}.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="pill-link" aria-label="Back to staff list">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Staff</span>
        </a>
    </header>

    @foreach (['success' => 'form-alert--success', 'error' => 'form-alert--error', 'warning' => 'form-alert--warning'] as $msg => $class)
        @if(session($msg))
            <div class="form-alert {{ $class }}" role="alert" aria-live="polite">
                <i class="fa-solid {{ $msg === 'success' ? 'fa-circle-check' : ($msg === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info') }}" aria-hidden="true"></i>
                <span>{{ session($msg) }}</span>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="form-alert form-alert--error" role="alert" aria-live="assertive">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
                <p class="form-alert__title">Please fix the following:</p>
                <ul class="form-alert__list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->user_id) }}" class="staff-form-card" onsubmit="return confirmStaffLimit()">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-id-badge" aria-hidden="true"></i>
                <span>Role & Access</span>
            </h2>
            <div class="form-grid form-grid--single">
                <div class="form-field">
                    <label for="role">Role</label>
                    <select name="role" id="role" required>
                        <option value="" disabled {{ old('role', $user->role) ? '' : 'selected' }}>Select role</option>
                        <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }} {{ $ownerCount - ($user->role === 'owner' ? 1 : 0) >= 1 ? 'disabled' : '' }}>Owner {{ $ownerCount - ($user->role === 'owner' ? 1 : 0) >= 1 ? '(already assigned)' : '' }}</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }} {{ $adminCount - ($user->role === 'admin' ? 1 : 0) >= 1 ? 'disabled' : '' }}>Admin {{ $adminCount - ($user->role === 'admin' ? 1 : 0) >= 1 ? '(already assigned)' : '' }}</option>
                        <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                    @error('role')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span>Personal Information</span>
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->staff->first_name ?? '') }}" required>
                    @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="middle_name">Middle Name <span class="optional">(optional)</span></label>
                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->staff->middle_name ?? '') }}">
                    @error('middle_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->staff->last_name ?? '') }}" required>
                    @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number', $user->staff->contact_number ?? '') }}" required>
                    @error('contact_number') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <span>Security</span>
            </h2>
            <div class="form-grid form-grid--single">
                <div class="form-field">
                    <label for="current_password">Current Password <span class="optional">(required to confirm changes)</span></label>
                    <input type="password" id="current_password" name="current_password" required>
                    @error('current_password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="password">New Password <span class="optional">(leave blank to keep current)</span></label>
                    <input type="password" id="password" name="password">
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                <span>Address</span>
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="street">Street</label>
                    <input type="text" id="street" name="street" value="{{ old('street', $user->staff->address->street ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay', $user->staff->address->barangay ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $user->staff->address->city ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" value="{{ old('province', $user->staff->address->province ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->staff->address->postal_code ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $user->staff->address->country ?? 'Philippines') }}">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">
                <i class="fa-solid fa-toggle-on" aria-hidden="true"></i>
                <span>Status</span>
            </h2>
            <div class="form-grid form-grid--single">
                <div class="form-field">
                    <label for="status">Account Status</label>
                    <select name="status" id="status" required>
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <footer class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span>Save Changes</span>
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Cancel</span>
            </a>
        </footer>
    </form>

    <script>
        function confirmStaffLimit() {
            const role = document.getElementById('role').value;
            const currentRole = '{{ $user->role }}';
            @if($staffCount >= 3)
                if (role === 'staff' && currentRole !== 'staff') {
                    return confirm('Staff account limit has been reached. Do you still want to assign this role?');
                }
            @endif
            return true;
        }
    </script>
</main>
@endsection
