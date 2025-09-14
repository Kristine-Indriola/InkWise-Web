@extends('layouts.owner.app')
@section('title', 'Pending Staff Accounts')
@include('layouts.owner.sidebar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/owner/staffapp.css') }}?v=sm-2">
@endpush

@section('content')
<div class="staff-page" style="--wrap: 1200px;"> 

  {{-- Body --}}
  <div class="page-inner">
    <div class="panel">
      <div class="panel-header">
        <h3>🧾 Pending Staff Accounts</h3>
      </div>

      @if(session('success'))
        <div class="bg-green-100">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="bg-red-100">{{ session('error') }}</div>
      @endif

      @if($pendingStaff->isEmpty())
        <p>No pending staff accounts.</p>
      @else
        {{-- Table Wrapper --}}
        <div class="table-wrap table-wrap--center" style="--table-w: var(--wrap);"> <!-- Ensuring the same width -->
          <table class="table-auto">
            <thead class="bg-gray-200">
              <tr>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Contact</th>
                <th class="px-4 py-2" style="width:180px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pendingStaff as $staff)
                <tr class="border-t">
                  <td class="px-4 py-2">{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                  <td class="px-4 py-2">{{ $staff->user->email }}</td>
                  <td class="px-4 py-2">{{ $staff->contact_number }}</td>
                  <td class="actions">
                    <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST">@csrf
                      <button type="submit" class="btn btn-success">✅ Approve</button>
                    </form>
                    <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to reject this account?');">@csrf
                      <button type="submit" class="btn btn-danger">❌ Reject</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
