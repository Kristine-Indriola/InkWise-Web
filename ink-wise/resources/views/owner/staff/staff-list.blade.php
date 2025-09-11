@extends('layouts.owner.app')  
@section('title', 'Staff Management')

@push('styles')
    <link rel="stylesheet" href="css/owner/staffapp.css"> 
@endpush

@section('content')
@include('layouts.owner.sidebar') 

<section class="main-content">
    <div class="topbar">
        <!-- Welcome Text -->
        <div class="welcome-text"><strong>Welcome, Owner!</strong></div>

        <!-- Notification & Logout -->
        <div class="topbar-actions">
            <button type="button" class="icon-btn" aria-label="Notifications">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
                    <path d="M10 21a2 2 0 0 0 4 0"/>
                </svg>
                <span class="badge">2</span> {{-- Notification count --}}
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

                <div style="margin-top: 20px;">
        <button onclick="window.history.back();" style="background: none; border: none; padding: 0; cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left" viewBox="0 0 24 24">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>



    <!-- Staff Table -->
    <div class="table-wrap table-wrap--center" style="--table-w: var(--wrap); margin-top: 40px;">
        <table class="table-auto">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $staffMember)
                    <tr>
                        <td>{{ $staffMember->user->user_id }}</td>
                        <td>{{ $staffMember->first_name }} {{ $staffMember->middle_name ?? '' }} {{ $staffMember->last_name }}</td>
                        <td>{{ $staffMember->user->email }}</td>
                        <td>{{ $staffMember->contact_number }}</td>
                        <td><span class="badge status-active">Approved</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No staff found matching your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
