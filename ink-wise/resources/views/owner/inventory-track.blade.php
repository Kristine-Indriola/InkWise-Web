<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Inventory Track</title>
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
    .sidebar h2 { font-size: 20px; margin: 0 20px 16px; color: #6c5ce7; }
    .profile { display: flex; align-items: center; gap: 10px; padding: 10px 20px 16px; border-bottom: 1px solid #eee; }
    .avatar  { width: 38px; height: 38px; border-radius: 50%; background:#eef2ff; display:grid; place-items:center; font-weight:800; color:#475569; }

    /* Sidebar nav (matches other pages) */
    .navlist { list-style:none; padding:6px 6px; margin:0; }
    .navlist li { margin: 2px 6px; }
    .navlist a { text-decoration: none; color: inherit; display: block; }

    .sidebar-btn {
      width: 100%;
      display: grid;
      grid-template-columns: 1fr 28px; /* text | icon */
      align-items: center;
      gap: 12px;
      padding: 14px 14px;
      padding-right: 12px;
      background: transparent;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      text-align: left;
      font-size: 15px;
      color: #1f2937;
      white-space: nowrap;
    }
    .sidebar-btn:hover { background: #f7faff; }
    .sidebar-btn:active { background:#eef4ff; }
    .navlist .text { font-size: 15px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .ico {
      width: 28px; height: 28px;
      display: flex; align-items: center; justify-content: center;
      background: transparent; border: 0; margin: 0;
      font-size: 20px; line-height: 1; justify-self: end; flex-shrink: 0;
    }

    /* Main content */
    .main-content { flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 21px;
      background: #fff; padding: 14px 20px; border-bottom: 1px solid #ddd;
    }

    /* Panel (same card look as other pages) */
    .panel {
      background:#fff;
      margin: 20px;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    .panel h3 {
      margin: 0 0 12px 0;
      font-weight: 700;
      color: #1f2937;
      font-size: 20px;
    }

    /* Search bar — LEFT aligned */
    .search-wrap {
      display: flex;
      justify-content: flex-start;  /* left */
      margin-bottom: 16px;
    }
    .search-input {
      width: 420px; max-width: 95%;
      padding: 10px 14px;
      border: 2px solid #cfd4dc;
      border-radius: 10px;
      outline: none;
      font-size: 14px;
      background: #fafafa;
    }
    .search-input:focus { border-color:#98a2b3; background:#fff; }

    /* Table */
    .table-wrap { overflow-x: auto; border-radius: 10px; }
    .inventory-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fff;
    }
    .inventory-table th,
    .inventory-table td {
      padding: 12px 20px;
      font-size: 15px;
      text-align: center;
      border: 1px solid #e6e8ef;
    }
    .inventory-table th {
      background-color: #e9edf3;
      color: #4a4a4a;
      font-weight: 700;
    }

    /* Status pills */
    .status { padding: 6px 12px; border-radius: 999px; color:#fff; font-weight:700; display:inline-block; font-size: 13px; }
    .in-stock { background-color: #22c55e; }
    .low-stock { background-color: #f59e0b; }

    @media (max-width: 640px){
      .sidebar { width: 210px; }
      .panel { margin: 12px; }
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
      <li><a href="{{ route('owner.home') }}"><button class="sidebar-btn"><span class="text">Dashboard</span><span class="ico">🏠</span></button></a></li>
      <li><a href="{{ route('owner.approve-staff') }}"><button class="sidebar-btn"><span class="text">Approve Staff Account</span><span class="ico">✅</span></button></a></li>
      <li><a href="{{ route('owner.order.workflow') }}"><button class="sidebar-btn"><span class="text">Monitor Order Workflow</span><span class="ico">🧭</span></button></a></li>
      <li><a href="{{ route('owner.inventory-track') }}"><button class="sidebar-btn"><span class="text">Track Inventory</span><span class="ico">📦</span></button></a></li>
      <li><a href="{{ route('owner.transactions-view') }}"><button class="sidebar-btn"><span class="text">View Transactions</span><span class="ico">💳</span></button></a></li>
    </ul>
  </aside>

  <!-- Main -->
  <section class="main-content">
    <div class="topbar">
      <div><strong>Welcome, Owner!</strong></div>
      <form method="POST" action="{{ route('owner.logout') }}">
        @csrf
        <button type="submit" style="padding:8px 14px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;cursor:pointer">
          Logout
        </button>
      </form>
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
</body>
</html>
