@extends('layouts.owner.app')
@include('layouts.owner.sidebar')

@section('title', 'Pending Staff Accounts')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg mt-10">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Pending Staff Accounts</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @if($pendingStaff->isEmpty())
        <p class="text-gray-500">No pending staff accounts.</p>
    @else
        <table class="w-full border-collapse border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 border">Name</th>
                    <th class="p-3 border">Email</th>
                    <th class="p-3 border">Contact</th>
                    <th class="p-3 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingStaff as $staff)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 border">
                            {{ $staff->first_name }} {{ $staff->last_name }}
                        </td>
                        <td class="p-3 border">{{ $staff->user->email }}</td>
                        <td class="p-3 border">{{ $staff->contact_number }}</td>
                        <td class="p-3 border flex gap-2">
                            {{-- Approve --}}
                            <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                    ✅ Approve
                                </button>
                            </form>

                            {{-- Reject --}}
                            <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this account?');">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                    ❌ Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection