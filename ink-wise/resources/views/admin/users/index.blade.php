@extends('layouts.admin')

@section('title', 'Manage Staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">
@endpush

@section('content')
<div class="container">
    <h1>👥 Staff Management</h1>
    @if(session('warning'))
    <div class="alert warning">
        {{ session('warning') }}
    </div>
@endif

    {{-- Search bar --}}
   <form method="GET" action="{{ route('admin.users.index') }}" class="search-box">
    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="🔍 Search staff...">
    <button type="submit">Search</button>
</form>

     <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            ➕ Add New Staff
     </a>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Staff ID</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
           <tbody>
    @forelse($users as $user)
        <tr class="staff-row {{ optional($user->staff)->status === 'pending' ? 'pending-row' : '' }}"
            onclick="window.location='{{ route('admin.users.show', $user->user_id) }}'">
            <td>{{ $user->staff->staff_id ?? 'N/A' }}</td>
            <td>
                <span class="badge 
                    {{ $user->role === 'owner' ? 'role-owner' : ($user->role === 'admin' ? 'role-admin' : 'role-staff') }}">
                    {{ ucfirst($user->role) }}
                </span>
            </td>
            <td>
                {{ optional($user->staff)->first_name }} {{ optional($user->staff)->middle_name }} {{ optional($user->staff)->last_name }}
            </td>
            <td>{{ $user->email }}</td>
            <td>
                <span class="badge 
                    {{ optional($user->staff)->status === 'approved' ? 'status-active' : (optional($user->staff)->status === 'pending' ? 'status-pending' : 'status-inactive') }}">
                    {{ ucfirst(optional($user->staff)->status ?? $user->status) }}
                </span>
            </td>
         <td class="actions" onclick="event.stopPropagation();">
    @if(($user->staff)->status !== 'archived')
        <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning">✏ Edit</a>
        <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="inline">
            @csrf @method('DELETE')
            <button type="submit" onclick="return confirm('Archive this staff account?')" class="btn btn-danger">📦 ARCHIVE</button>
        </form>
    @else
        <span class="badge bg-secondary">📦 Archived</span>
    @endif
</td>

        </tr>
    @empty
        <tr>
            <td colspan="6" class="table-empty">No staff found</td>
        </tr>
    @endforelse
</tbody>

        </table>
    </div>
</div>
@endsection
