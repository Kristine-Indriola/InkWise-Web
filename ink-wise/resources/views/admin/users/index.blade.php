@extends('layouts.admin')

@section('title', 'Manage Staff')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/staff.css') }}">
@endpush

@section('content')
@php
    $totalStaff = $users->count();
    $pendingStaff = $users->filter(fn($u) => optional($u->staff)->status === 'pending')->count();
    $activeStaff = $users->filter(fn($u) => optional($u->staff)->status === 'approved')->count();
@endphp

<main class="admin-page-shell staff-page" role="main">
    @if(session('warning'))
        <div class="dashboard-alert alert-warning" role="alert" aria-live="polite">
            {{ session('warning') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Staff Management</h1>
            <p class="page-subtitle">Review admin and staff accounts, update roles, and archive inactive profiles.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="pill-link pill-link--primary" aria-label="Add new staff">
            <i class="fa-solid fa-plus"></i>
            <span>Add Staff</span>
        </a>
    </header>

    <section class="summary-grid" aria-label="Staff summary">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Staff</span>
                <span class="summary-card-chip accent">Directory</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($totalStaff ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
            </div>
            <span class="summary-card-meta">All registered accounts</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Active</span>
                <span class="summary-card-chip success">Approved</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($activeStaff ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <span class="summary-card-meta">Currently approved staff</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Pending</span>
                <span class="summary-card-chip warning">Review</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($pendingStaff ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
            </div>
            <span class="summary-card-meta">Awaiting approval</span>
        </div>
    </section>

    <section class="staff-toolbar" aria-label="Staff filters and actions">
        <form method="GET" action="{{ route('admin.users.index') }}" class="materials-toolbar__search">
            <div class="search-input">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search staff by name or email..."
                    class="form-control"
                    aria-label="Search staff"
                >
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
    </section>

    <section class="staff-table" aria-label="Staff list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Staff ID</th>
                        <th scope="col">Role</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $staffProfile = $user->staff;
                            $fullName = collect([
                                optional($staffProfile)->first_name,
                                optional($staffProfile)->middle_name,
                                optional($staffProfile)->last_name,
                            ])->filter()->implode(' ');
                            $roleClass = match($user->role) {
                                'owner' => 'badge-role badge-role--owner',
                                'admin' => 'badge-role badge-role--admin',
                                default => 'badge-role badge-role--staff',
                            };
                            $status = optional($staffProfile)->status ?? $user->status;
                            $statusClass = match($status) {
                                'approved', 'active' => 'badge-status badge-status--active',
                                'pending' => 'badge-status badge-status--pending',
                                'archived' => 'badge-status badge-status--archived',
                                default => 'badge-status badge-status--inactive',
                            };
                            $rowClass = $status === 'pending' ? 'staff-row staff-row--pending' : 'staff-row';
                        @endphp
                        <tr class="{{ $rowClass }}" onclick="window.location='{{ route('admin.users.show', $user->user_id) }}'">
                            <td>{{ $staffProfile->staff_id ?? '—' }}</td>
                            <td>
                                <span class="{{ $roleClass }}">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="fw-bold">{{ $fullName ?: '—' }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="{{ $statusClass }}">{{ ucfirst($status) }}</span>
                            </td>
                            <td class="table-actions" onclick="event.stopPropagation();">
                                @if(optional($staffProfile)->status !== 'archived')
                                    <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Archive this staff account?')" class="btn btn-danger">
                                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                            <span>Archive</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="badge-status badge-status--archived">Archived</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
