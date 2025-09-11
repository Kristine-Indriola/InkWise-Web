@extends('layouts.owner.app')
@include('layouts.owner.sidebar')

@section('title', 'Approved Staff')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">

<div class="container">
    <h1 class="text-2xl font-bold mb-4">✅ Approved Staff</h1>

    <a href="{{ route('owner.staff.pending') }}" class="btn btn-warning float-right mb-4">
        View Pending Staff
    </a>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 p-2 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 p-2 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- Approved Staff Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Full Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Contact Number</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedStaff as $staff)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $staff->user->user_id }}</td>
                        <td class="px-4 py-2">{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                        <td class="px-4 py-2">{{ $staff->user->email }}</td>
                        <td class="px-4 py-2">{{ $staff->contact_number }}</td>
                        <td class="px-4 py-2">
                            <span class="badge status-active">Approved</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No approved staff found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Floating Button for Pending --}}
    <button id="showPendingBtn" class="floating-btn">⏳ Pending Accounts</button>

    {{-- Pending Accounts Modal --}}
    <div id="pendingModal" class="modal hidden">
        <div class="modal-content">
            <span id="closeModal" class="close">&times;</span>
            <h2 class="text-xl font-bold mb-4">⏳ Pending Staff Accounts</h2>

            @if($pendingStaff->isEmpty())
                <p>No pending staff accounts.</p>
            @else
                <table class="min-w-full border">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">Full Name</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Contact Number</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingStaff as $staff)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $staff->user->user_id }}</td>
                                <td class="px-4 py-2">{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                                <td class="px-4 py-2">{{ $staff->user->email }}</td>
                                <td class="px-4 py-2">{{ $staff->contact_number }}</td>
                                <td class="px-4 py-2 flex gap-2">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('owner.staff.approve', $staff->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success">✅ Approve</button>
                                    </form>
                                    {{-- Reject --}}
                                    <form method="POST" action="{{ route('owner.staff.reject', $staff->id) }}" onsubmit="return confirm('Are you sure you want to reject this account?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">❌ Reject</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

{{-- Modal JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const showBtn = document.getElementById('showPendingBtn');
    const modal = document.getElementById('pendingModal');
    const closeBtn = document.getElementById('closeModal');

    if(showBtn && modal && closeBtn) {
        showBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Close modal on click outside content
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.add('hidden');
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape') modal.classList.add('hidden');
        });
    }
});
</script>

@endsection
