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

    <!-- Orders Panel + Search -->
    <div class="orders-table-container">
      <h3 class="orders-title">Confirmed Orders &amp; Status</h3>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by order Id, customer, Product" />
      </div>

      <table class="orders-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Date Ordered</th>
            <th>Order Details</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#1001</td>
            <td>Leanne Mae</td>
            <td>2025-04-25</td>
            <td>Wedding Invitations - 100 pcs</td>
            <td><span class="status confirmed">Confirmed</span></td>
          </tr>
          <tr>
            <td>#1002</td>
            <td>Kristine Mae</td>
            <td>2025-04-26</td>
            <td>Keychains - 20 pcs</td>
            <td><span class="status pending">Pending</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
