<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Order Workflow</title>
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

    /* Sidebar nav — same as previous pages */
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
    .navlist span.text {
      font-size: 15px; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .ico {
      width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
      background: transparent; border: 0; margin: 0; font-size: 20px; line-height: 1; justify-self: end; flex-shrink: 0;
    }

    /* Main content layout */
    .main-content { flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 21px;
      background: #fff; padding: 14px 20px; border-bottom: 1px solid #ddd;
    }

    .orders-table-container{
      background:#fff;
      margin: 20px;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    .orders-title{
      margin: 0 0 10px 0;
      font-weight: 700;
      color: #1f2937;
      font-size: 20px;
    }

    /* Search bar (centered under the title like your image) */
    .search-wrap{
      display:flex;
      justify-content:flex-start;
      margin: 4px 0 16px;
    }
    .search-input{
      width: 420px; max-width: 95%;
      padding: 10px 14px;
      border: 2px solid #cfd4dc;
      border-radius: 10px;
      outline: none;
      font-size: 14px;
      background: #fafafa;
    }
    .search-input:focus{ border-color:#98a2b3; background:#fff; }

    /* Orders table */
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fff;
    }
    .orders-table th,
    .orders-table td {
      padding: 12px 20px;
      font-size: 15px;
      text-align: center;
      border: 1px solid #e6e8ef;
    }
    .orders-table th {
      background-color: #e9edf3; /* light gray like screenshot */
      color: #4a4a4a;
      font-weight: 700;
    }

    /* Status pills */
    .status {
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      color:#fff;
      display:inline-block;
    }
    .confirmed { background-color: #22c55e; }
    .pending   { background-color: #f59e0b; }

    @media (max-width: 640px){
      .sidebar { width: 210px; }
      .orders-table-container { margin: 12px; }
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

    <!-- Orders Panel + Search -->
    <div class="orders-table-container">
      <h3 class="orders-title">Confirmed Orders &amp; Status</h3>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by order Id, Customer, Product" />
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
</body>
</html>
