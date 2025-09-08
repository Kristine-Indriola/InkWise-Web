@extends('layouts.owner.app')
@section('title', 'Staff Management')


@push('styles')
  <link rel="stylesheet" href="css/owner/staffapp.css">
@endpush

@section('content')
@include('layouts.owner.sidebar')
  {{-- <div class="staff-page">  --}}

  <style>[hidden]{display:none !important;}</style>
  <section class="main-content">
    <div class="topbar">
      <!-- Welcome Text (left-aligned) -->
      <div class="welcome-text"><strong>Welcome, Owner!</strong></div>

      <div class="topbar-actions">
        <!-- Notification Icon -->
        <button type="button" class="icon-btn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
            <path d="M10 21a2 2 0 0 0 4 0"/>
          </svg>
          <span class="badge">2</span> {{-- Notification count --}}
        </button>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="logout-btn">
            Logout
          </button>
        </form>
      </div>
    </div>
   
  <div class="page-inner">
    <div class="panel">
      <h2>
        👥 Staff Management
      </h2>

      @if(session('success'))
        <div id="successToast" style="
          margin: 16px auto 24px auto;
          background: #d1fae5;
          color: #065f46;
          padding: 6px 16px;
          border-radius: 6px;
          font-size: 15px;
          font-weight: bold;
          border: 1px solid #a7f3d0;
          box-shadow: 0 1px 4px rgba(0,0,0,0.04);
          min-width: 90px;
          max-width: 280px;
          text-align: center;
          opacity: 0;
          transition: opacity 0.5s;
          position: relative;
          z-index: 10;
          display: block;
        ">
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div id="errorToast" style="
          margin: 16px auto 24px auto;
          background: #fee2e2;
          color: #991b1b;
          padding: 6px 16px;
          border-radius: 6px;
          font-size: 15px;
          font-weight: bold;
          border: 1px solid #fecaca;
          box-shadow: 0 1px 4px rgba(0,0,0,0.04);
          min-width: 90px;
          max-width: 280px;
          text-align: center;
          opacity: 0;
          transition: opacity 0.5s;
          position: relative;
          z-index: 10;
          display: block;
        ">
          {{ session('error') }}
        </div>
      @endif

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
              <tr><td colspan="5" class="text-center">No approved staff yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:12px;">
        <button id="pendingBtn" class="pending-btn" type="button"
          aria-haspopup="dialog" aria-controls="pendingSheet"
          style="background-color:#16a34a; color:#ffffff; padding:5px 9px; border-radius:6px; border:2px solid #16a34a; font-weight: bold;">
          Pending Staff
        </button>
        <style>
          #pendingBtn.pending-btn:hover {
            background-color: #15803d !important;
            color: #fff !important;
            border-color: #15803d !important;
            box-shadow: 0 2px 8px rgba(22,163,74,0.12);
            transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
          }
        </style>
      </div>
    </div>
  </div>

  {{-- Bottom sheet (hidden by default) --}}
  <div id="pendingSheet" class="page-inner" role="dialog" aria-modal="true" aria-labelledby="pendingTitle"
     hidden aria-hidden="true">
    <div class="panel"> <!-- Set the same width here -->
        <div class="sheet-header">
            <h3 id="pendingTitle">Pending Staff Accounts</h3>
        </div>

        @if($pendingStaff->isEmpty())
            <div> <!-- Set the same width here -->
                <table class="">
                    <tbody>
                        <tr>
                          <td colspan="4" class="text-center py-8" style="font-size:16px; color: #16a34a;">
                            🧾 No pending staff accounts.
                          </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div> <!-- Set the same width here -->
                <table class="">
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
                                <td class="px-4 py-2 flex gap-1">
                                    {{-- Approve --}}
                                    <div style="display: flex; gap: 5px;">
                                    <form action="{{ route('owner.staff.approve', $staff->staff_id) }}" method="POST">@csrf
                                        <button type="submit" class="btn btn-success">✅ Approve</button>
                                    </form>
                                    {{-- Reject --}}
                                    <form action="{{ route('owner.staff.reject', $staff->staff_id) }}" method="POST"
                                          onsubmit="return confirm('Reject this account?');">@csrf
                                        <button type="submit" class="btn btn-danger">❌ Reject</button>
                                    </form>
                                    </div>
                                     <style>
                                    .btn-success,
                                    .btn-danger {
                                      color: #fff !important;       /* text always white */
                                      padding: 6px 10px;            /* uniform size */
                                      min-width: 90px;             /* same width */
                                      text-align: center;
                                      font-size: 11px; 
                                      border-radius: 6px;
                                      border: none;
                                      cursor: pointer;
                                    }

                                    .btn-success {
                                      background-color: #16a34a !important; /* green */
                                    }

                                    .btn-danger {
                                      background-color: #dc2626 !important; /* red */
                                    }
                                  </style>
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
</section>

<script>
(function() {
  const sheet = document.getElementById('pendingSheet');
  const openBtn = document.getElementById('pendingBtn');

  if (!sheet || !openBtn) return;

  // Toggle sheet open/close
  function toggleSheet() {
    if (sheet.hidden || sheet.getAttribute('aria-hidden') === 'true') {
      sheet.hidden = false;
      sheet.setAttribute('aria-hidden', 'false');
      sheet.classList.add('is-open');
      openBtn.setAttribute('aria-expanded', 'true');
    } else {
      sheet.classList.remove('is-open');
      sheet.hidden = true;
      sheet.setAttribute('aria-hidden', 'true');
      openBtn.setAttribute('aria-expanded', 'false');
    }
  }

  openBtn.addEventListener('click', toggleSheet);

  // Close the sheet if clicking outside the panel (on backdrop)
  sheet.addEventListener('click', (e) => {
    if (e.target === sheet) toggleSheet();
  });

  // Close the sheet if the Escape key is pressed
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sheet.classList.contains('is-open')) toggleSheet();
  });
})();
</script>

<script>
  window.addEventListener('DOMContentLoaded', function() {
    var toast = document.getElementById('successToast');
    if (toast) {
      toast.style.opacity = '1';
      setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() {
          toast.style.display = 'none';
        }, 500); // Wait for the transition to finish
      }, 2000);
    }
    var errorToast = document.getElementById('errorToast');
    if (errorToast) {
      errorToast.style.opacity = '1';
      setTimeout(function() {
        errorToast.style.opacity = '0';
        setTimeout(function() {
          errorToast.style.display = 'none';
        }, 500);
      }, 2000);
    }
  });
</script>

<script>
  window.addEventListener('DOMContentLoaded', function() {
    const sheet = document.getElementById('pendingSheet');
    // Check if URL contains ?pending=open
    if (window.location.search.includes('pending=open')) {
      if (sheet) {
        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        sheet.classList.add('is-open');
      }
    }
  });
</script>
@endsection
