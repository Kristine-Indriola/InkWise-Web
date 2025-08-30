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

    <!-- Inventory Panel -->
    <div class="panel">
      <h3>Stock Levels</h3>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by item name, category…" />
      </div>

      <div class="table-wrap">
        <table class="inventory-table">
          <thead>
            <tr>
              <th>Item Name</th>
              <th>Category</th>
              <th>Stock Quantity</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Glossy Paper 100gsm</td>
              <td>Paper</td>
              <td>1200</td>
              <td><span class="status in-stock">In stock</span></td>
            </tr>
            <tr>
              <td>Ink Cartridge Black</td>
              <td>Ink</td>
              <td>3</td>
              <td><span class="status low-stock">Low Stock</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection
