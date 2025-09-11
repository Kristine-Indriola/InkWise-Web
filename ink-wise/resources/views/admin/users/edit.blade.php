<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="{{ asset('css/edit-users.css') }}">
</head>
<body>

<div class="container">
    <div class="card">
        <h2 class="form-title">✏️ Edit User</h2>

        {{-- Display Validation Errors --}}
        @if ($errors->any())
            <div class="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>⚠️ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user->user_id) }}">
            @csrf
            @method('PUT')

            <!-- Role -->
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="owner" {{ $user->role === 'owner' ? 'selected' : '' }}>Owner</option>
                      <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Staff</option>
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
                <a href="{{ url()->previous() }}" class="btn-secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
