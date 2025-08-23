<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Approve Staff Accounts</title>
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

    /* Sidebar nav */
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

    /* Table container */
    .table-container {
      margin: 20px;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    .table-container h3 {
      margin-bottom: 16px;
      font-size: 20px;
      font-weight: 700;
    }

    /* Table style */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    table, th, td {
      border: 1px solid #e5e7eb;
    }
    th, td {
      padding: 12px;
      text-align: center;
    }
    th {
      background-color: #f1f5ff;
      color: #475569;
    }
    td {
      color: #64748b;
    }
    td button {
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      border: none;
      font-weight: bold;
    }
    .btn-approve {
      background-color: #10b981;
      color: white;
    }
    .btn-reject {
      background-color: #ef4444;
      color: white;
    }
    .btn-approve:hover, .btn-reject:hover {
      opacity: 0.8;
    }

    /* Responsive */
    @media (max-width: 640px){
      .sidebar { width: 200px; }
      .table-container { margin: 10px; }
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
      <li><span class="text">Dashboard</span><span class="ico">🏠</span></li>
      <li><span class="text">Approve Staff Account</span><span class="ico">✅</span></li>
      <li><span class="text">Monitor Order Workflow</span><span class="ico">🧭</span></li>
      <li><span class="text">Track Inventory</span><span class="ico">📦</span></li>
      <li><span class="text">View Transactions</span><span class="ico">💳</span></li>
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

    <!-- Table container for pending staff account requests -->
    <div class="table-container">
      <h3>Pending Staff Account Requests</h3>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Requested Role</th>
            <th>Date Requested</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Leanne Baribe</td>
            <td>Baribe@gmail.com</td>
            <td>Manager</td>
            <td>Active</td>
            <td>
              <button class="btn-approve">Approve</button>
              <button class="btn-reject">Reject</button>
            </td>
          </tr>
          <!-- Add more rows as necessary -->
        </tbody>
      </table>
    </div>
  </section>
</body>
</html>
