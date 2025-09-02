<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_account.css') }}">
</head>
<body>

<div class="container">
    <h2>Create New User</h2>

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

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <!-- Role -->
        <div class="form-row">
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
        </div>

        <!-- Name fields in a row -->
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required>
            </div>
            <div class="form-group">
                <label>Middle Name <small>(optional)</small></label>
                <input type="text" name="middle_name" value="{{ old('middle_name') }}">
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required>
            </div>
        </div>

        <!-- Contact Number -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" required>
            </div>
        </div>

        <!-- Email -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
        </div>

        <!-- Password fields -->
        <div class="form-row">
            <div class="form-group">
                <label>Password <small>(min 6 characters)</small></label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required>
            </div>
        </div>

        <!-- Buttons -->
        <div class="form-row buttons-row">
            <button type="submit">Create User</button>
            <a href="{{ url()->previous() }}" class="cancel-btn">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
