@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4 text-center">Edit User</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="block font-semibold">First Name</label>
            <input type="text" name="first_name" value="{{ $user->first_name }}" class="w-full p-2 border rounded" required>
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Middle Name</label>
            <input type="text" name="middle_name" value="{{ $user->middle_name }}" class="w-full p-2 border rounded">
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Last Name</label>
            <input type="text" name="last_name" value="{{ $user->last_name }}" class="w-full p-2 border rounded" required>
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Email</label>
            <input type="email" name="email" value="{{ $user->email }}" class="w-full p-2 border rounded" required>
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Role</label>
            <select name="role" class="w-full p-2 border rounded" required>
                <option value="owner" {{ $user->role == 'owner' ? 'selected' : '' }}>Owner</option>
                <option value="staff" {{ $user->role == 'staff' ? 'selected' : '' }}>Staff</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="block font-semibold">Status</label>
            <select name="status" class="w-full p-2 border rounded" required>
                <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Update User
        </button>
    </form>
</div>
@endsection
