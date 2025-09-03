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

    <div class="panel">
      <h2>Payment Summaries</h2>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by Transaction Id" />
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Transaction ID</th>
              <th>Order ID</th>
              <th>customer</th>
              <th>Payment Method</th>
              <th>Date</th>
              <th>Amount (PHP)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>TXN10001</td>
              <td>#1001</td>
              <td>Frechy</td>
              <td>GCash</td>
              <td>2025-04-28</td>
              <td>12,000.00</td>
              <td><span class="pill pill-paid">Paid</span></td>
            </tr>
            <tr>
              <td>TXN10002</td>
              <td>#1002</td>
              <td>Kristine</td>
              <td>COD</td>
              <td>2025-04-28</td>
              <td>7,500.00</td>
              <td><span class="pill pill-pending">Pending</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection
