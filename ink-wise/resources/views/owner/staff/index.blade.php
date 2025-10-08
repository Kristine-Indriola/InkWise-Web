@extends('layouts.owner.app')
@section('title', 'Staff Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/owner/staffapp.css') }}">
@endpush


@section('content')
@include('layouts.owner.sidebar')

@php
$highlightStaffId = request()->query('highlight');
@endphp


<style>
  .owner-dashboard-shell {
    padding: 20px 24px 32px;
    padding-left: clamp(24px, 3vw, 48px);
  }

  .owner-dashboard-main {
    max-width: 1440px;
    margin: 0 auto;
    padding: 28px 28px 36px;
    width: 100%;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }

  .owner-dashboard-inner {
    max-width: none;
    margin: 0;
    width: 100%;
    padding: 0;
  }

  .staff-section-title {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 700;
    color: #0f172a;
  }

  .owner-dashboard-inner .panel {
    width: 100%;
    max-width: 100%;
    margin: 0;
  }

  .staff-table-wrapper {
    margin-top: 8px;
    border-radius: 14px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    background: #f8fbff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 16px 32px rgba(15, 23, 42, 0.08);
  }

  .staff-table-wrapper .table thead th {
    background: rgba(148, 185, 255, 0.16);
    padding: 14px 20px;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    font-weight: 700;
  }

  .staff-table-wrapper .table tbody td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(148, 185, 255, 0.12);
    vertical-align: middle;
  }

  .staff-table-wrapper .table tbody tr:last-child td {
    border-bottom: none;
  }

  .staff-table-wrapper .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.08);
  }

  @media (max-width: 900px) {
    .staff-table-wrapper .table { min-width: 720px; }
  }

  .dark-mode .staff-table-wrapper {
    background: #1f2937;
    border-color: rgba(148, 185, 255, 0.32);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
  }

  .dark-mode .staff-table-wrapper .table {
    color: #f9fafb;
  }

  .dark-mode .staff-table-wrapper .table thead th {
    background: rgba(148, 185, 255, 0.22);
    color: #0f172a;
  }

  .dark-mode .staff-table-wrapper .table tbody td {
    border-color: rgba(148, 185, 255, 0.18);
  }

  .dark-mode .staff-table-wrapper .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.12);
  }
</style>



<section class="main-content owner-dashboard-shell">
  <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Staff Management</h1>
        <p class="page-subtitle">Manage approved and pending staff</p>
      </div>
    </header>

  <div class="page-inner owner-dashboard-inner">
      <div class="panel">
        <h2 class="page-title" style="display:flex;align-items:center;gap:8px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="vertical-align:middle;"><circle cx="8" cy="8" r="4" stroke="var(--accent-strong, #5a8de0)" stroke-width="1.6"/><circle cx="16" cy="8" r="4" stroke="var(--accent-strong, #5a8de0)" stroke-width="1.6"/><path d="M2 20c0-3.3137 2.6863-6 6-6s6 2.6863 6 6" stroke="var(--accent-strong, #5a8de0)" stroke-width="1.6" stroke-linecap="round"/><path d="M10 20c0-3.3137 2.6863-6 6-6s6 2.6863 6 6" stroke="var(--accent-strong, #5a8de0)" stroke-width="1.6" stroke-linecap="round"/></svg>
          Staff Management
        </h2>




      <div class="materials-toolbar" style="margin-bottom:18px;">
        <div class="materials-toolbar__search">
          <form action="{{ route('owner.staff.search') }}" method="GET" style="display:flex;gap:8px;align-items:center;">
            <div class="search-input">
              <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
              <input type="text" name="search" placeholder="Search Staff by Name or Email" class="form-control" style="border:0;outline:0;width:180px;min-width:120px;">
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
          </form>
        </div>
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

  <h3 class="staff-section-title">Approved Staff Accounts</h3>
        <div class="table-wrapper staff-table-wrapper">
          <table class="table">
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
              @forelse($approvedStaff as $staff)
        <tr>
                  <td class="fw-bold">{{ $staff->user->user_id }}</td>
                  <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                  <td>{{ $staff->user->email }}</td>
                  <td>{{ $staff->contact_number }}</td>
                  <td>
                    <span class="badge stock-ok">Approved</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center" style="padding:18px; color:#64748b;">No approved staff.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="display:flex; justify-content:flex-start; gap:8px; margin-top:12px;">
          <button id="pendingBtn" class="btn btn-secondary" type="button" aria-controls="pendingSection" title="Show pending staff">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v6l4 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Pending Staff</span>
          </button>
        </div>
      </div>

      {{-- Pending Staff Section --}}
      <div id="pendingSection" class="fade" hidden>
        <button type="button" onclick="backToApproved()" class="btn btn-secondary" style="margin-bottom:12px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span>Back</span>
        </button>

  <h3 class="staff-section-title" style="margin-top:20px;">Pending Staff Accounts</h3>
  <div class="table-wrapper staff-table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($pendingStaff as $staff)
              <tr>
                <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                <td>{{ $staff->user->email }}</td>
                <td>{{ $staff->contact_number }}</td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;justify-content:center;">
                    @if(session('warning') && session('pendingStaffId') == $staff->staff_id)
                      <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="confirm" value="true">
                        <button type="submit" class="btn btn-warning" title="Confirm Approve">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                      </form>
                      <div class="alert" style="margin-top:8px;">{{ session('warning') }}</div>
                    @else
                      <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST" style="display:inline" onsubmit="return confirmApproval();">
                        @csrf
                        <button type="submit" class="btn btn-success" title="Approve">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                      </form>
                    @endif

                    <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST" style="display:inline" onsubmit="return confirm('Reject this account?');">
                      @csrf
                      <button type="submit" class="btn btn-danger" title="Reject">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center" style="padding:18px; color:#64748b;">No pending staff accounts.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

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

{{-- Open pending tab if requested via query param --}}
<script>
  (function(){
    try {
      const params = new URLSearchParams(window.location.search);
      if (params.get('tab') === 'pending') {
        const pendingBtn = document.getElementById('pendingBtn');
        if (pendingBtn) pendingBtn.click();
      }
    } catch (e) { /* ignore */ }
  })();
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

  const highlightedRow = document.getElementById('highlighted-staff');
  if (highlightedRow) {
    highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    highlightedRow.setAttribute('tabindex', '-1');
    highlightedRow.focus({ preventScroll: true });
    setTimeout(() => highlightedRow.removeAttribute('tabindex'), 2000);
  }
});
</script>

<script>
function confirmApproval() {
  const staffLimit = 3;
  const approvedCount = {{ \App\Models\Staff::where('status','approved')->count() }};
  if (approvedCount >= staffLimit) {
    return confirm("The approved staff limit of " + staffLimit + " has been reached. Are you sure you want to approve this account?");
  }
  return true;
}
</script>

  </div> <!-- .page-inner -->
  </main>
</section>
@endsection