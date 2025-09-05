@extends('layouts.owner.app')
@include('layouts.owner.sidebar')
@section('title', 'Staff Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">

<div class="container">
    <h1>👥 Staff Management</h1>

    {{-- Success/Error messages --}}
    @if(session('success'))
        <div class="bg-green-100 p-2 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 p-2 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- Approved Staff Table --}}
    <div class="overflow-x-auto">
        <table class="table-auto w-full border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Contact</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedStaff as $staff)
                    <tr>
                        <td class="border px-4 py-2">{{ $staff->user->user_id }}</td>
                        <td class="border px-4 py-2">{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                        <td class="border px-4 py-2">{{ $staff->user->email }}</td>
                        <td class="border px-4 py-2">{{ $staff->contact_number }}</td>
                        <td class="border px-4 py-2">
                            <span class="badge status-active">Approved</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border px-4 py-2 text-center">No approved staff yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

{{-- Floating button --}}
<button id="pendingBtn" class="fixed bottom-8 right-8 bg-indigo-600 text-white px-6 py-3 rounded-full shadow-lg hover:bg-indigo-700 z-50">
    Pending Staff
</button>

{{-- Modal --}}
<div id="pendingModal" class="fixed inset-0 flex justify-center items-end opacity-0 pointer-events-none bg-black bg-opacity-50 transition-opacity duration-300 z-50">
    <div id="pendingContent"
         class="bg-white w-11/12 md:w-2/3 lg:w-1/2 rounded-t-lg p-6 relative transform translate-y-full transition-transform duration-300">
        <h2 class="text-xl font-bold mb-4">Pending Staff Accounts</h2>
        <button id="closeModal" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-2xl">&times;</button>

        <table class="table-auto w-full border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Contact</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingStaff as $staff)
                    <tr>
                        <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                        <td>{{ $staff->user->email }}</td>
                        <td>{{ $staff->contact_number }}</td>
                        <td class="flex gap-2">
                            <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">Approve</button>
                            </form>
                            <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Reject</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-2">No pending staff.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingBtn = document.getElementById('pendingBtn');
    const pendingModal = document.getElementById('pendingModal');
    const pendingContent = document.getElementById('pendingContent');
    const closeModal = document.getElementById('closeModal');

    function openPanel() {
        pendingModal.classList.remove('opacity-0', 'pointer-events-none');
        pendingContent.classList.remove('translate-y-full');
    }

    function closePanel() {
        pendingContent.classList.add('translate-y-full');
        pendingModal.classList.add('opacity-0', 'pointer-events-none');
    }

    pendingBtn.addEventListener('click', openPanel);
    closeModal.addEventListener('click', closePanel);

    // Close if clicked outside content
    pendingModal.addEventListener('click', (e) => {
        if (e.target === pendingModal) {
            closePanel();
        }
    });
});
</script>


@endsection
