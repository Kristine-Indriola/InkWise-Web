<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>InkWise Dashboard</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      background: #f7f7f7;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 230px;
      background: #fff;
      border-right: 1px solid #ddd;
      height: 100vh;
      padding: 20px 10px;
    }

    .sidebar .profile {
      display: flex;
      align-items: center;
      margin-bottom: 30px;
    }

    .sidebar .profile img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin: 15px 0;
      display: flex;
      align-items: center;
      cursor: pointer;
      font-size: 14px;
    }

    .sidebar ul li i {
      margin-right: 10px;
    }

    .sidebar ul li.active {
      font-weight: bold;
    }

    /* Topbar */
    .topbar {
      background: #fff;
      height: 60px;
      border-bottom: 1px solid #ddd;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      width: 100%;
    }

    .topbar .logo {
      font-size: 22px;
      font-weight: bold;
      color: #6a2ebc;
    }

    .topbar .icons i {
      margin-left: 20px;
      cursor: pointer;
    }

    /* Dashboard Content */
    .main {
      flex: 1;
      padding: 20px;
    }

    .cards {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .card {
      flex: 1;
      background: #fff;
      border: 2px solid #3cd5c8;
      border-radius: 12px;
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }

    .card h3 {
      margin-top: 10px;
    }

    /* Stock Table */
    .stock {
      background: #fff;
      border: 2px solid #7e57c2;
      border-radius: 12px;
      padding: 15px;
    }

    .stock h3 {
      background: #00acc1;
      color: white;
      padding: 10px;
      border-radius: 8px 8px 0 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    table th, table td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
      font-size: 14px;
    }

    .status {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
    }

    .low { background: #fff3cd; color: #856404; }
    .in { background: #d4edda; color: #155724; }
    .critical { background: #f8d7da; color: #721c24; }

    /* make the power icon red */
.logout-btn i {
    color: red;
    font-style: normal; /* prevents italic look */
    font-size: 22px;    /* adjust size */
    font-weight: bold;
}

/* optional hover effect */
.logout-btn:hover i {
    color: darkred;
}

  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="profile">
      <img src="https://via.placeholder.com/50" alt="Admin">
      <div>
        <strong>Admin Profile</strong> <span style="color:green;">✔</span>
      </div>
    </div>
    <ul>
      <li class="active"><i>🏠</i> Dashboard</li>
      <li><i>👥</i> Customer Accounts</li>
<li>
    <a href="{{ route('admin.users.index' ) }}">
        <i>👤</i> Staff Accounts
    </a>
</li>

      <li>
    <a href="{{ route('admin.templates.index') }}">
        <i>📑</i> Templates
    </a>
</li> 
      <li><i>📦</i> Order Summaries</li>
        <li>
    <a href="{{ route('admin.materials.index') }}">
        <i>📑</i> Inventory
    </a>
</li> 
    </ul>
  </div>

  <!-- Main Content -->
  <div style="flex:1;">
    <!-- Topbar -->
    <div class="topbar">
      <div class="logo">InkWise</div>
      <div class="icons">
        <i>🔔</i>
       <a href="{{ route('admin.logout') }}" class="logout-btn"><i>⏻</i></a>
        <i>⚙</i>
      </div>
    </div>

    <div class="main">
      <!-- Cards -->
      <div class="cards">
        <div class="card">
          <div>🛒</div>
          <h3>Orders</h3>
          <p>20</p>
        </div>
        <div class="card">
          <div>⏳</div>
          <h3>Pending</h3>
          <p>35</p>
        </div>
        <div class="card">
          <div>⭐</div>
          <h3>Rating</h3>
          <p>4.0</p>
        </div>
      </div>

      <!-- Stock Table -->
      <div class="stock">
        <h3>Stock Level</h3>
        <table>
          <thead>
            <tr>
              <th>Task</th>
              <th>Asset</th>
              <th>Size/Type</th>
              <th>Quantity</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Matte Paper</td>
              <td><img src="https://via.placeholder.com/40" alt=""></td>
              <td>A4 / 120gsm</td>
              <td>15 packs</td>
              <td><span class="status low">Low Stock</span></td>
            </tr>
            <tr>
              <td>Glossy Paper</td>
              <td><img src="https://via.placeholder.com/40" alt=""></td>
              <td>A4 / 150gsm</td>
              <td>60 packs</td>
              <td><span class="status in">In Stock</span></td>
            </tr>
            <tr>
              <td>Kraft Paper</td>
              <td><img src="https://via.placeholder.com/40" alt=""></td>
              <td>A5 / 100gsm</td>
              <td>5 packs</td>
              <td><span class="status critical">Critical</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>
</html>
