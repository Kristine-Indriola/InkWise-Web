<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System — View Transactions</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f3f4f6;
      display: flex;
    }

    /* Sidebar (same design as previous pages) */
    .sidebar {
      width: 250px;
      background: #fff;
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
    .profile { display:flex; align-items:center; gap:10px; padding:10px 20px 16px; border-bottom:1px solid #eee; }
    .avatar  { width:38px; height:38px; border-radius:50%; background:#eef2ff; display:grid; place-items:center; font-weight:800; color:#475569; }

    .navlist { list-style:none; padding:6px 6px; margin:0; }
    .navlist li { margin: 2px 6px; }
    .navlist a { text-decoration:none; color:inherit; display:block; }

    /* Grid layout so icons align in a fixed right column */
    .sidebar-btn{
      width:100%;
      display:grid;
      grid-template-columns: 1fr 28px; /* text | icon */
      align-items:center;
      gap:12px;
      padding:14px 14px;
      padding-right:12px;
      background:transparent;
      border:none;
      border-radius:12px;
      cursor:pointer;
      text-align:left;
      font-size:15px;
      color:#1f2937;
      white-space:nowrap;
    }
    .sidebar-btn:hover{ background:#f7faff; }
    .sidebar-btn:active{ background:#eef4ff; }
    .navlist .text{ font-size:15px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .ico{
      width:28px; height:28px;
      display:flex; align-items:center; justify-content:center;
      background:transparent; border:0; margin:0;
      font-size:20px; line-height:1; justify-self:end; flex-shrink:0;
    }
    .navlist .active .sidebar-btn{ background: linear-gradient(#f7f8fb, #eceff6); }

    /* Main content */
    .main-content { flex:1; display:flex; flex-direction:column; min-height:100vh; }

    /* Topbar (Welcome + Logout) */
    .topbar{
      display:flex; justify-content:space-between; align-items:center;
      font-size: 21px;
      background:#fff; padding:14px 20px; border-bottom:1px solid #ddd;
    }

    .logout-btn{
      padding:8px 14px; border:1px solid #e5e7eb; background:#fff;
      border-radius:8px; cursor:pointer; font-size:14px;
    }
    .logout-btn:hover{ background:#f7f7f7; }

    /* Panel */
    .panel {
      background:#fff;
      margin: 24px;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    .panel h2{ margin: 4px 0 14px; font-size: 20px; color:#1f2937; }

    /* Search input */
    .search-wrap{ display:flex; justify-content:flex-start; margin-bottom:16px; }
    .search-input{
      width: 420px; max-width: 90%; padding:10px 14px;
      border:2px solid #cfd4dc; border-radius:10px; outline:none;
      font-size:14px; background:#fafafa;
    }
    .search-input:focus{ border-color:#98a2b3; background:#fff; }

    /* Table */
    .table-wrap{ overflow-x:auto; border-radius: 10px; font-size: 15px }
    table{ width:100%; border-collapse:collapse; background:#fff; }
    th, td{ padding: 14px 16px; text-align:center; border:1px solid #dfe3ea; }
    th{ background:#e9edf3; color:#374151; font-weight:700; }
    td{ color:#4b5563; }

    /* Status pills */
    .pill{ display:inline-block; padding:8px 14px; border-radius:999px; font-weight:700; font-size:13px; }
    .pill-paid{ background:#22c55e; color:#fff; }
    .pill-pending{ background:#f59e0b; color:#fff; }

    @media (max-width: 640px){
      .sidebar { width: 210px; }
      .panel { margin: 16px; }
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
      <li class="active"><a href="{{ route('owner.transactions-view') }}"><button class="sidebar-btn"><span class="text">View Transactions</span><span class="ico">💳</span></button></a></li>
    </ul>
  </aside>

  <!-- Main -->
  <section class="main-content">
    <!-- NEW top bar -->
    <div class="topbar">
      <div><strong>Welcome, Owner!</strong></div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
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
              <th>Customer</th>
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
</body>
</html>
