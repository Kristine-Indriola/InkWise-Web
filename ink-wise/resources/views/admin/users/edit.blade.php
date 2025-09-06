<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_account.css') }}">
</head>
<body>

<div class="container">
    <h2>Edit User</h2>

    {{-- Display Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-100">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->user_id) }}">
        @csrf
        @method('PUT')

        <!-- Role -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Role</label>
                <select name="role" required>
                    <option value="owner" {{ $user->role === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
        </div>

        <!-- Name fields in a row -->
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

        <!-- Contact Number -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $user->staff->contact_number ?? '') }}" required>
            </div>
        </div>

        <!-- Email -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>

        <!-- Address Section -->
        <h3 class="mt-6 mb-2">📍 Address</h3>
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
                <input type="text" name="country" value="{{ old('country', $user->staff->address->country ?? '') }}">
            </div>
        </div>

        <!-- Status -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Buttons -->
        <div class="form-row buttons-row">
            <button type="submit">Update User</button>
            <a href="{{ url()->previous() }}" class="cancel-btn">Cancel</a>
        </div>

    </form>
</div>

</body>
</html>
