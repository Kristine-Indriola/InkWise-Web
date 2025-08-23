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

    /* Orders table */
    .inventory-table-container {
      margin-top: 20px;
      padding: 0 20px;
    }
    .inventory-table-container h3 {
      text-align: center;
      font-weight: 700;
      color: #4a4a4a;
    }
    .inventory-table {
      width: 90%;
      margin: 20px auto;
      border-collapse: collapse;
      background-color: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    .inventory-table th,
    .inventory-table td {
      padding: 10px 20px;
      text-align: center;
      border: 1px solid #e6e8ef;
    }
    .inventory-table th {
      background-color: #f1f5ff;
      color: #4a4a4a;
    }
    .status {
      padding: 5px 10px;
      border-radius: 12px;
    }
    .in-stock { background-color: #4caf50; color: #fff; }
    .low-stock { background-color: #ff9800; color: #fff; }
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
        <a href="{{ route('owner.order-workflow') }}" class="text-decoration-none">
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

    <!-- Inventory Table -->
    <div class="inventory-table-container">
      <h3>Stock Levels</h3>
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
  </section>
</body>
</html>
