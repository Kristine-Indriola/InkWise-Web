
@extends('layouts.staffapp')

@section('content')
@php
    // Get abbreviation from name (first letters of first and last name)
    $abbr = '';
    if (!empty($user->name)) {
        $parts = explode(' ', $user->name);
        $abbr = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
@endphp
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded shadow">
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 rounded-full bg-purple-500 flex items-center justify-center text-white text-2xl font-bold">
            {{ $abbr }}
        </div>
    </div>
    <h2 class="text-2xl font-bold mb-6 text-center">Edit Profile</h2>
    @if(session('success'))
        <div class="mb-4 text-green-600">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('staff.profile.update') }}">
        @csrf

         <div class="mb-4">
            <label class="block mb-1 font-semibold">Profile Picture</label>
            <input type="file" name="profile_pic" class="w-full border rounded px-3 py-2">
            @if($user->profile_pic)
                <img src="@imageUrl($user->profile_pic)" alt="Profile Picture" class="mt-2 w-24 h-24 rounded-full object-cover">
            @endif
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-semibold">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Position</label>
            <input type="text" name="position" value="{{ old('position', $user->position ?? '') }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Address</label>
            <input type="text" name="address" value="{{ old('address', $user->address ?? '') }}" class="w-full border rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded">Update Profile</button>
    </form>
</div>
@endsection