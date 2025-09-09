@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

@php $materials = $materials ?? collect(); @endphp


<!-- Main Section with Adjusted Layout -->
<section class="main-content">
  <!-- Topbar -->
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

  <!-- Stat Cards Section -->
  <div class="cards">
    <!-- Total Sales Card -->
    <div class="card">
      <div class="stat-icon icon-sales">
        <svg viewBox="0 0 24 24">
          <path d="M3 3v18h18"/>
          <path d="M7 15l4-4 3 3 5-5"/>
        </svg>
      </div>
      <h3>Total Sales</h3>
      <p>$12,340</p>
    </div>

    <!-- Low Stock Card -->
<a href="{{ route('owner.inventory-track', ['status' => 'low']) }}" style="text-decoration:none; color:inherit;">
  <div class="card">
    <div class="stat-icon icon-stock">
      <svg viewBox="0 0 24 24">
        <path d="M3 7l9 4 9-4-9-4-9 4z"/>
        <path d="M3 7v6l9 4 9-4V7"/>
        <path d="M12 11v6"/>
      </svg>
    </div>
    <h3>Low Stock</h3>
    <p>
  {{ \App\Models\Material::whereHas('inventory', function($q) {
        $q->whereColumn('stock_level', '<=', 'reorder_level')
          ->where('stock_level', '>', 0);
    })->count() }} Items
</p>
  </div>
</a>

    <!-- Pending Orders Card -->
    <div class="card">
      <div class="stat-icon icon-pending">
        <svg viewBox="0 0 24 24">
          <path d="M6 3h12"/>
          <path d="M6 21h12"/>
          <path d="M8 3c0 4 8 4 8 8s-8 4-8 8"/>
          <path d="M16 3c0 4-8 4-8 8s8 4 8 8"/>
        </svg>
      </div>
      <h3>Pending</h3>
      <p>8 Orders</p>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="charts">
    <!-- Top-Selling Products Chart -->
    <div class="chart-container">
      <h3>Top-Selling Products</h3>
      <canvas id="barChart"></canvas>
    </div>

    <!-- Inventory Movement Overview Chart -->
    <div class="chart-container">
      <h3>Inventory Movement Overview</h3>
      <canvas id="lineChart"></canvas>
    </div>
  </div>
</section>

@endsection
