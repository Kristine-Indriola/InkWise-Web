@extends('layouts.admin')

@section('title', 'Create Staff Account')

@section('content')
<div class="container">
    <div class="card">
          <link rel="stylesheet" href="{{ asset('css/edit-users.css') }}">
        <h2 class="form-title">➕ Create Staff Account</h2>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="alert success">
                {{ session('success') }}
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

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <!-- Name Section -->
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

            <!-- Contact -->
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" required>
            </div>

            <!-- Email + Password -->
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
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
                    <input type="text" name="country" value="{{ old('country') }}">
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="role" value="staff">
            <input type="hidden" name="status" value="pending">

            <!-- Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">💼 Create Account</button>
                <a href="{{ url()->previous() }}" class="btn-secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
