@extends('layouts.owner.app')

@section('content')

@include('layouts.owner.sidebar')


  <section class="main-content">
    
  <div class="cards">
    <div class="card">
      <div class="stat-icon icon-orders" aria-hidden="true">
        <!-- shopping bag icon -->
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
          <path d="M6 2l1.5 3h9L18 2"></path>
          <path d="M3 6h18v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"></path>
          <path d="M16 10a4 4 0 0 0-8 0"></path>
        </svg>
      </div>
      <h3>New Orders</h3>
      <p>5 Orders</p>
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
        <h3>Low Stock Materials</h3>
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
      <h3>Pending Orders</h3>
      <p>8 Orders</p>
    </div>

    <!-- Revenue Growth Card -->
    <div class="card">
      <div class="stat-icon" style="background:#fff6ef;border:1px solid #fde8d6;">
        <!-- small chart icon -->
        <svg viewBox="0 0 24 24" style="stroke:#f59e0b;">
          <path d="M3 3v18h18"/>
          <path d="M6 15l4-4 3 3 5-5"/>
        </svg>
      </div>
      <h3>Revenue Growth</h3>
      <p style="color:#16a34a;">
        {{-- replace with a computed value, fallback shown below --}}
        {{ $revenueGrowth ?? '+12.4% vs last period' }}
      </p>
    </div>

    <!-- New Customer Logins Card -->
    <div class="card">
      <div class="stat-icon" style="background:#eef7ff;border:1px solid #dbeefe;">
        <svg viewBox="0 0 24 24" style="stroke:#3b82f6;">
          <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <h3>New Customer Logins</h3>
      <p>
        {{-- shows new user registrations in the last 7 days as a simple proxy --}}
        {{ \App\Models\User::where('created_at', '>=', now()->subDays(7))->count() ?? 0 }} in 7d
      </p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const barCanvas = document.getElementById('barChart');
  if (barCanvas) {
    const barCtx = barCanvas.getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Invitation - Birthday Party','Keychain','Invitation - Floral Pink'],
        datasets: [{
          label: 'Units Sold',
          data: [12, 15, 20],
          backgroundColor: ['#68b4e3ff','#4487daff','#1147dbff'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        layout: { padding: { bottom: 30 } },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#eef2f7' }
          },
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0,
              align: 'center',
              callback: function(value){
                const label = this.getLabelForValue(value);
                return label.length > 22 ? label.slice(0,22) + '…' : label;
              }
            }
          }
        }
      }
    });
  }
  const lineCanvas = document.getElementById('lineChart');
  if (lineCanvas) {
    const lineCtx = lineCanvas.getContext('2d');
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: ['Week 1','Week 2','Week 3','Week 4'],
        datasets: [
          { label: 'Incoming Stock', data: [20,40,25,35], borderColor: '#16a34a', fill:false, tension:.3 },
          { label: 'Outgoing Stock', data: [70,30,20,50], borderColor: '#ef4444', fill:false, tension:.3 }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }
});
</script>
@endsection
