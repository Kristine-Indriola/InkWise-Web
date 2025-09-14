@extends('layouts.owner.app')
@section('title', 'Staff Management')

@push('styles')
<link rel="stylesheet" href="css/owner/staffapp.css">
@endpush

@section('content')
@include('layouts.owner.sidebar')

<style>
[hidden]{display:none !important;}
.btn-success, .btn-danger {
    color: #fff !important;
    padding: 6px 10px;
    min-width: 90px;
    text-align: center;
    font-size: 11px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}
.btn-success { background-color: #16a34a !important; }
.btn-danger { background-color: #dc2626 !important; }
.fade { opacity: 0; transition: opacity 0.3s ease; }
.fade.show { opacity: 1; }
#pendingBtn.pending-btn:hover {
    background-color: #15803d !important;
    color: #fff !important;
    border-color: #15803d !important;
    box-shadow: 0 2px 8px rgba(22,163,74,0.12);
    transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
}
</style>

<section class="main-content">
  <div class="topbar">
    <div class="welcome-text"><strong>Welcome, Owner!</strong></div>
    <div class="topbar-actions">
      <button type="button" class="icon-btn" aria-label="Notifications">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
          <path d="M10 21a2 2 0 0 0 4 0"/>
        </svg>
        <span class="badge">2</span>
      </button>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>

  <div class="page-inner">
    <div class="panel">
      <h2>👥 Staff Management</h2>

      <div class="search-bar" style="margin-top: 20px; margin-bottom: 20px;">
        <form action="{{ route('owner.staff.search') }}" method="GET">
          <input type="text" name="search" placeholder="Search Staff by Name or Email" class="search-input">
          <button type="submit" class="search-btn">Search</button>
        </form>
      </div>

      {{-- Toast Notifications --}}
      @if(session('success'))
      <div id="successToast" style="background:#d1fae5;color:#065f46;padding:6px 16px;border-radius:6px;
        font-size:15px;font-weight:bold;border:1px solid #a7f3d0;box-shadow:0 1px 4px rgba(0,0,0,0.04);
        text-align:center;opacity:0;transition:opacity 0.5s;margin-bottom:12px;">
        {{ session('success') }}
      </div>
      @endif
      @if(session('error'))
      <div id="errorToast" style="background:#fee2e2;color:#991b1b;padding:6px 16px;border-radius:6px;
        font-size:15px;font-weight:bold;border:1px solid #fecaca;box-shadow:0 1px 4px rgba(0,0,0,0.04);
        text-align:center;opacity:0;transition:opacity 0.5s;margin-bottom:12px;">
        {{ session('error') }}
      </div>
      @endif

      {{-- Approved Staff Section --}}
      <div id="approvedSection" class="fade show">
        @if(request()->has('search') && request('search') !== '')
        <a href="{{ route('owner.staff.index') }}" 
          style="margin-bottom:20px; background:#f9fafb; color:#1f2937; padding:5px 13px;
                  border-radius:6px; border:1px solid #d1d5db; font-weight:600; font-size:15px;
                  display:inline-flex; align-items:center; gap:6px; cursor:pointer; transition:all 0.2s ease;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" 
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </a>
        @endif

        <h3 style="font-size:16px; margin-bottom:12px;">Approved Staff Accounts</h3>
        <div class="table-wrap table-wrap--center" style="--table-w: var(--wrap);"> 
          <table class="table-auto">
            <thead>
              <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Contact</th><th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($approvedStaff as $staff)
                <tr>
                  <td>{{ $staff->user->user_id }}</td>
                  <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                  <td>{{ $staff->user->email }}</td>
                  <td>{{ $staff->contact_number }}</td>
                  <td><span class="badge status-active">Approved</span></td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center">No approved staff.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="display:flex; justify-content:flex-start; margin-top:12px;">
          <button id="pendingBtn" class="pending-btn" type="button"
            style="background-color:#16a34a;color:#fff;padding:5px 9px;border-radius:6px;
            border:2px solid #16a34a;font-weight:bold;">
            Pending Staff
          </button>
        </div>
      </div>

      {{-- Pending Staff Section --}}
      <div id="pendingSection" class="fade" hidden>
        <button type="button" onclick="backToApproved()" 
          style="margin-bottom:20px; background:#f9fafb; color:#1f2937; padding:5px 13px;
          border-radius:6px; border:1px solid #d1d5db; font-weight:600; font-size:15px;
          display:flex; align-items:center; gap:6px; cursor:pointer; transition:all 0.2s ease;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" 
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Back
        </button>

        <h3 style="font-size: 16px; margin-top:20px;">Pending Staff Accounts</h3>
        <table class="table-auto">
          <thead>
            <tr>
              <th>Name</th><th>Email</th><th>Contact</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingStaff as $staff)
            <tr>
              <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
              <td>{{ $staff->user->email }}</td>
              <td>{{ $staff->contact_number }}</td>
              <td>
                @if(session('warning') && session('pendingStaffId') == $staff->staff_id)
                <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST" style="display:inline;">
                  @csrf
                  <input type="hidden" name="confirm" value="true">
                  <button type="submit" class="btn btn-warning">⚠️ Confirm Approve</button>
                </form>
                <div class="alert alert-warning" style="margin-top:8px;">
                  {{ session('warning') }}
                </div>
                @else
                <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST" style="display:inline" onsubmit="return confirmApproval();">
                  @csrf
                  <button type="submit" class="btn btn-success">✅ Approve</button>
                </form>
                @endif

                <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST" style="display:inline" onsubmit="return confirm('Reject this account?');">
                  @csrf
                  <button type="submit" class="btn btn-danger">❌ Reject</button>
                </form>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align:center; color:#16a34a; padding:10px;">
                🧾 No pending staff accounts.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

{{-- Section Transitions --}}
<script>
(function() {
  const pendingBtn = document.getElementById('pendingBtn');
  const approvedSection = document.getElementById('approvedSection');
  const pendingSection = document.getElementById('pendingSection');

  if (!pendingBtn) return;

  pendingBtn.addEventListener('click', function() {
    approvedSection.classList.remove('show');
    setTimeout(() => {
      approvedSection.hidden = true;
      pendingSection.hidden = false;
      setTimeout(() => pendingSection.classList.add('show'), 10);
    }, 300);
  });
})();

function backToApproved() {
  const approvedSection = document.getElementById('approvedSection');
  const pendingSection = document.getElementById('pendingSection');

  pendingSection.classList.remove('show');
  setTimeout(() => {
    pendingSection.hidden = true;
    approvedSection.hidden = false;
    setTimeout(() => approvedSection.classList.add('show'), 10);
  }, 300);
}
</script>

{{-- Toast Notifications --}}
<script>
window.addEventListener('DOMContentLoaded', function() {
  var toast = document.getElementById('successToast');
  if (toast) {
    toast.style.opacity = '1';
    setTimeout(function() {
      toast.style.opacity = '0';
      setTimeout(() => toast.style.display = 'none', 500);
    }, 2000);
  }
  var errorToast = document.getElementById('errorToast');
  if (errorToast) {
    errorToast.style.opacity = '1';
    setTimeout(function() {
      errorToast.style.opacity = '0';
      setTimeout(() => errorToast.style.display = 'none', 500);
    }, 2000);
  }
});
</script>

{{-- Staff Approval Limit Check --}}
<script>
function confirmApproval() {
    const staffLimit = 3;
    const approvedCount = {{ \App\Models\Staff::where('status','approved')->count() }};

    if (approvedCount >= staffLimit) {
        return confirm("⚠️ The approved staff limit of " + staffLimit + " has been reached. Are you sure you want to approve this account?");
    }
    return true;
}
</script>

@endsection
