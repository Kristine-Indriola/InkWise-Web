@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<div class="container">
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">
    <h1>STAFF MANAGEMENT</h1>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Add New User --}}
    <a href="{{ route('admin.users.create') }}" class="bg-blue-600">
        ➕ Add New Staff
    </a>

    {{-- Users Table --}}
    <div class="overflow-x-auto" style="margin-top:15px;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->user_id }}</td>
                        <td>
    <span class="badge {{ $user->role === 'owner' ? 'role-owner' : 'role-staff' }}">
        {{ ucfirst($user->role) }}
    </span>
</td>
                        <td>
                            {{ $user->staff->first_name ?? '' }}
                            {{ $user->staff->middle_name ?? '' }}
                            {{ $user->staff->last_name ?? '' }}
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->staff->contact_number ?? '' }}</td>
                        <td>
    <span class="badge {{ $user->status === 'active' ? 'status-active' : 'status-inactive' }}">
        {{ ucfirst($user->status) }}
    </span>
</td>
                        <td>
                            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="bg-yellow-500">✏️ Edit</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user->user_id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
