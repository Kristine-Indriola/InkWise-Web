@extends('layouts.admin')

@section('title', 'Manage Staff')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">

<div class="container">
    <h1>👥 Staff Management</h1>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search Bar + Add Staff --}}
    <div class="actions-bar mb-4 flex justify-between items-center">
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex gap-2">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="🔍 Search by Staff ID or Name" 
                class="border p-2 rounded w-64"
            >
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            ➕ Add New Staff
        </a>
    </div>

    {{-- Staff Table --}}
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Staff ID</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="{{ optional($user->staff)->status === 'pending' ? 'pending-row' : '' }}">
                        <td>{{ optional($user->staff)->staff_id ?? '—' }}</td>
                        <td>
                            <span class="badge 
                                {{ $user->role === 'owner' ? 'role-owner' : ($user->role === 'admin' ? 'role-admin' : 'role-staff') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            {{ optional($user->staff)->first_name ?? '' }}
                            {{ optional($user->staff)->middle_name ?? '' }}
                            {{ optional($user->staff)->last_name ?? '' }}
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ optional($user->staff)->contact_number ?? '-' }}</td>
                        <td>
                            @if(optional($user->staff)->address)
                                {{ $user->staff->address->street ?? '' }},
                                {{ $user->staff->address->city ?? '' }},
                                {{ $user->staff->address->province ?? '' }}
                            @else
                                <span class="text-gray-400 italic">No address</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge 
                                {{ optional($user->staff)->status === 'approved' ? 'status-active' : (optional($user->staff)->status === 'pending' ? 'status-pending' : 'status-inactive') }}">
                                {{ ucfirst(optional($user->staff)->status ?? $user->status) }}
                            </span>
                        </td>
                        <td class="actions flex gap-1">
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
                        <td colspan="8" class="text-center">No staff accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
