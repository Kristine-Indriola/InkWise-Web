@extends('layouts.owner.app')
@section('title', 'Pending Staff Accounts')
@include('layouts.owner.sidebar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/owner/staffapp.css') }}?v=sm-2">
@endpush

@section('content')
<div class="staff-page" style="--wrap: 1200px;"> 

  {{-- Topbar --}}
  <div class="topbar">
    <div class="welcome-text">
      <strong>Welcome, {{ auth('owner')->user()->first_name ?? 'Owner' }}!</strong>
    </div>
    <div class="topbar-actions">
      <button type="button" class="icon-btn" aria-label="Notifications">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
          <path d="M10 21a2 2 0 0 0 4 0"/>
        </svg>
        <span class="badge">2</span>
      </button>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>

  {{-- Body --}}
  <div class="page-inner">
    <div class="panel">
      <div class="panel-header">
        <h3>🧾 Pending Staff Accounts</h3>
      </div>

      @if(session('success'))
        <div class="bg-green-100 p-2 mb-2">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="bg-red-100 p-2 mb-2">{{ session('error') }}</div>
      @endif

      @if($pendingStaff->isEmpty())
        <p>No pending staff accounts.</p>
      @else
        <div class="table-wrap table-wrap--center" style="--table-w: var(--wrap);">
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
    <form method="POST" action="{{ route('owner.staff.approve', $staff->staff_id) }}" onsubmit="return confirmApproval()">
        @csrf
        @if(session('warning') && session('pendingStaffId') == $staff->staff_id)
            <input type="hidden" name="confirm" value="true">
            <button type="submit" class="btn btn-warning">⚠️ Confirm Approve</button>
            <div class="alert alert-warning" style="margin-top:8px;">
                {{ session('warning') }}
            </div>
        @else
            <button type="submit" class="btn btn-success">✅ Approve</button>
        @endif
    </form>
    <form method="POST" action="{{ route('owner.staff.reject', $staff->staff_id) }}" style="display:inline;">
        @csrf
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

@push('scripts')
<script>
function confirmApproval() {
    const staffLimit = 3;
    // Get current approved staff count dynamically from Blade
    const approvedCount = {{ \App\Models\Staff::where('status','approved')->count() }};

    if(approvedCount >= staffLimit) {
        return confirm("⚠️ The approved staff limit of " + staffLimit + " has been reached. Are you sure you want to approve this account?");
    }
    return true; // allow submission if under limit
}
</script>
@endpush

@endsection
