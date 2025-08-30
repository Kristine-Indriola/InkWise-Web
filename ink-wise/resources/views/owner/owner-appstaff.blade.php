@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar') 

  <!-- Main -->
  <section class="main-content">
    <div class="topbar">
    <!-- Welcome Text (left-aligned) -->
    <div class="welcome-text"><strong>Welcome, Owner!</strong></div>

    <!-- Actions: Notification Icon and Logout Button (right-aligned) -->
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

    <!-- Table container for pending staff account requests -->
    <div class="table-container">
      <h3>Pending Staff Account Requests</h3>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Requested Role</th>
            <th>Date Requested</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Leanne Baribe</td>
            <td>Baribe@gmail.com</td>
            <td>Manager</td>
            <td>Active</td>
            <td>
              <button class="btn-approve">Approve</button>
              <button class="btn-reject">Reject</button>
            </td>
          </tr>
          <!-- Add more rows as necessary -->
        </tbody>
      </table>
    </div>
  </section>
@endsection
