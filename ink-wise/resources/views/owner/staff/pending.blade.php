@extends('layouts.owner.app')
@section('title', 'Pending Staff Accounts')
@include('layouts.owner.sidebar')



@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
<main class="materials-page admin-page-shell materials-container" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Pending Staff</h1>
      <p class="page-subtitle">Review and manage pending accounts</p>
    </div>
  </header>

  <div class="page-inner staff-page" style="--wrap: 1200px;">
    {{-- Body --}}
    <div class="panel">
      <div class="panel-header">
        <h3>🧾 Pending Staff Accounts</h3>
      </div>




      @if(session('success'))
        <div class="alert" style="background:rgba(35,178,109,0.12);color:#16a34a;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert" style="background:rgba(239,68,68,0.12);color:#b91c1c;">{{ session('error') }}</div>
      @endif

      @if($pendingStaff->isEmpty())
        <p style="color:#64748b; padding:18px; text-align:center;">No pending staff accounts.</p>
      @else
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th style="width:180px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pendingStaff as $staff)
                <tr>
                  <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                  <td>{{ $staff->user->email }}</td>
                  <td>{{ $staff->contact_number }}</td>
                  <td class="actions">
                    <div style="display:flex; gap:8px;">
                      <form method="POST" action="{{ route('owner.staff.approve', $staff->staff_id) }}" onsubmit="return confirmApproval()" style="display:inline;">
                        @csrf
                        @if(session('warning') && session('pendingStaffId') == $staff->staff_id)
                          <input type="hidden" name="confirm" value="true">
                          <button type="submit" class="btn btn-warning" title="Confirm Approve">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          </button>
                        @else
                          <button type="submit" class="btn btn-success" title="Approve">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          </button>
                        @endif
                      </form>
                      <form method="POST" action="{{ route('owner.staff.reject', $staff->staff_id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger" title="Reject">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                      </form>
                    </div>
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

    </div> <!-- .page-inner -->
  </main>
