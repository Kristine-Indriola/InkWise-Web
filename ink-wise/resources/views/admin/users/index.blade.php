@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">

<div class="container">
    <h1>👥 Staff Management</h1>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Add New User --}}
    <div class="actions-bar">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            ➕ Add New Staff
        </a>
    </div>

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
                    <th class="text-center">Actions</th>
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
                        <td>{{ $user->staff->contact_number ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $user->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="actions">
                            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning">✏ Edit</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user->user_id) }}" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No staff accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
