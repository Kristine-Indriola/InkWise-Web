<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Owner Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background-color: #f8f9fa;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background-color: #fff;
      border-right: 1px solid #ddd;
      height: 100vh;
      padding: 20px 0;
      position: sticky;
      top: 0;
    }
    .sidebar h2 {
      font-size: 20px;
      margin: 0 20px 16px;
      color: #6c5ce7;
    }
    .profile {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 20px 16px; border-bottom: 1px solid #eee;
    }
    .avatar {
      width: 38px; height: 38px; border-radius: 50%;
      background:#eef2ff; display:grid; place-items:center; font-weight:800; color:#475569;
    }

    /* Sidebar nav (text left, icon right) */
    .navlist { list-style:none; padding:8px 0; margin:0; }
    .navlist li {
      display:flex; justify-content:space-between; align-items:center;
      margin:8px 12px; padding:10px 12px; border-radius:10px;
      cursor:pointer; transition:background .15s;
    }
    .navlist li:hover { background:#f1f5ff; }
    .navlist span.text { font-size:15px; }
    .ico {
      width:30px; height:30px; border-radius:50%; display:grid; place-items:center;
      background:#f3f4f6; border:1px solid #e5e7eb; font-size:16px; margin-left:10px;
    }

    /* Main content layout */
    .main-content { flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center;
      background: #fff; padding: 14px 20px; border-bottom: 1px solid #ddd;
    }

    /* Stat cards + icons */
    .cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin: 20px;
    }
    .card {
      background: #fff;
      padding: 18px;
      border-radius: 12px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      text-align: center;
      border: 1px solid #e6e8ef;
    }
    .stat-icon {
      width: 44px; height: 44px; margin: 0 auto 10px;
      border-radius: 12px; display: grid; place-items: center;
      background: #f1f5ff; border: 1px solid #e5e7eb;
    }
    .stat-icon svg {
      width: 24px; height: 24px; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    }
    .icon-sales   svg { stroke: #6366f1; }
    .icon-stock   svg { stroke: #10b981; }
    .icon-pending svg { stroke: #f59e0b; }
    .icon-bell    svg { stroke: #ef4444; }
    .card h3 { margin: 6px 0 6px; font-size: 16px; }
    .card p  { margin: 0; color: #64748b; font-size: 14px; }

    /* Charts area — larger containers */
    .charts {
      display: flex;
      gap: 20px;
      margin: 0 20px 24px;
    }
    .chart-container {
      flex: 1;
      background: #fff;
      padding: 18px;
      border-radius: 12px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      border: 1px solid #e6e8ef;
      height: 450px; /* Increased height */
    }
    .chart-container h3 { margin: 6px 0 12px; font-size: 16px; }
    canvas { width: 100% !important; height: 100% !important; }

    /* Responsive */
    @media (max-width: 1100px){
      .cards { grid-template-columns: repeat(2, 1fr); }
      .charts { flex-direction: column; }
      .chart-container { height: 380px; }
    }
    @media (max-width: 640px){
      .cards { grid-template-columns: 1fr; }
      .sidebar { width: 210px; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
  <h2>InkWise</h2>

  <div class="profile">
    <div class="avatar">👤</div>
    <div>
      <div style="font-weight:700;">Owner Profile</div>
      <div style="color:#64748b;font-size:12px;">{{ auth('owner')->user()->email ?? 'owner@example.com' }}</div>
    </div>
  </div>

  <ul class="navlist">
    <li>
      <a href="{{ route('owner.home') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">Dashboard</span><span class="ico">🏠</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.approve-staff') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">Approve Staff Account</span><span class="ico">✅</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.order.workflow') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">Monitor Order Workflow</span><span class="ico">🧭</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.inventory-track') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">Track Inventory</span><span class="ico">📦</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.transactions-view') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">View Transactions</span><span class="ico">💳</span>
        </button>
      </a>
    </li>
  </ul>
</aside>

  <!-- Main -->
  <section class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div><strong>Welcome, Owner!</strong></div>
      <form method="POST" action="{{ route('owner.logout') }}">
        @csrf
        <button type="submit" style="padding:8px 14px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;cursor:pointer">
          Logout
        </button>
      </form>
    </div>

    <!-- Stat cards with inline SVG icons -->
    <div class="cards">
      <div class="card">
        <div class="stat-icon icon-sales" aria-hidden="true">
          <!-- chart-line -->
          <svg viewBox="0 0 24 24">
            <path d="M3 3v18h18"/>
            <path d="M7 15l4-4 3 3 5-5"/>
          </svg>
        </div>
        <h3>Total Sales</h3>
        <p>$12,340</p>
      </div>

      <div class="card">
        <div class="stat-icon icon-stock" aria-hidden="true">
          <!-- box-open (simplified) -->
          <svg viewBox="0 0 24 24">
            <path d="M3 7l9 4 9-4-9-4-9 4z"/>
            <path d="M3 7v6l9 4 9-4V7"/>
            <path d="M12 11v6"/>
          </svg>
        </div>
        <h3>Low Stock</h3>
        <p>3 Items</p>
      </div>

      <div class="card">
        <div class="stat-icon icon-pending" aria-hidden="true">
          <!-- hourglass -->
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

      <div class="card">
        <div class="stat-icon icon-bell" aria-hidden="true">
          <!-- bell -->
          <svg viewBox="0 0 24 24">
            <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
            <path d="M10 21a2 2 0 0 0 4 0"/>
          </svg>
        </div>
        <h3>Notifications</h3>
        <p>2 New</p>
      </div>
    </div>

    <!-- Charts (enlarged) -->
    <div class="charts">
      <div class="chart-container">
        <h3>Top-Selling Products</h3>
        <canvas id="barChart"></canvas>
      </div>
      <div class="chart-container">
        <h3>Inventory Movement Overview</h3>
        <canvas id="lineChart"></canvas>
      </div>
    </div>
  </section>

  <script>
    // Bar Chart (Top-Selling Products) — improved label layout
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: [
          'Invitation - Birthday Party',
          'Keychain',
          'Invitation - Floral Pink'
        ],
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
        layout: { padding: { bottom: 40 } }, // more room for labels
        scales: {
          y: { beginAtZero: true, grid: { color: '#eef2f7' } },
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: false,   // show all labels
              maxRotation: 0,   // keep horizontal
              minRotation: 0,
              align: 'center',  // center align the labels
              padding: 10,
              font: { size: 12 }
            }
          }
        }
      }
    });

    // Line Chart (Inventory Movement)
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: ['Week 1','Week 2','Week 3','Week 4'],
        datasets: 
          [
            { label: 'Incoming Stock', data: [20,40,25,35], borderColor: '#16a34a', fill:false, tension:.3 },
            { label: 'Outgoing Stock', data: [70,30,20,50], borderColor: '#ef4444', fill:false, tension:.3 }
          ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, grid: { color: '#eef2f7' } },
          x: { grid: { display: false } }
        }
      }
    });
  </script>
</body>
</html>

