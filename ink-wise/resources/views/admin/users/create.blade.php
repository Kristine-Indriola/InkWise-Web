@extends('layouts.admin')

@section('title', 'Create User')

@section('content')

<body>
<div class="create-user-container">
    <div class="form-card">
        <h2 class="form-title">Create New User</h2>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="form-space">
            @csrf

            {{-- Name Row --}}
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            {{-- Password Row --}}
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

            {{-- Role --}}
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="owner">Owner</option>
                    <option value="staff" selected>Staff</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">
                Create User
            </button>
        </form>
    </div>
</div>
</body>
@endsection
