@extends('layouts.admin')

@section('title', 'Create Staff Account')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-md mt-10">
      <link rel="stylesheet" href="{{ asset('css/admin-css/create_account.css') }}">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Staff Account</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Display Validation Errors --}}
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<form method="POST" action="{{ route('admin.users.store') }}">        @csrf

        {{-- First Name --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full px-3 py-2 border rounded-lg" required>
        </div>

        {{-- Middle Name --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Middle Name</label>
            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-3 py-2 border rounded-lg">
        </div>

        {{-- Last Name --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-3 py-2 border rounded-lg" required>
        </div>

        {{-- Contact Number --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full px-3 py-2 border rounded-lg" required>
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-2 border rounded-lg" required>
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg" required>
        </div>

        {{-- Hidden Fields --}}
        <input type="hidden" name="role" value="staff">
        <input type="hidden" name="status" value="pending"> {{-- Newly created accounts are pending --}}

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Create Account
            </button>
        </div>
    </form>
</div>
@endsection
