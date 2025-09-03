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
      font-family: "Segoe UI", Tahoma, sans-serif;
    }

    body {
      background: #f4f6f9;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 230px;
      background: #fff;
      border-right: 1px solid #e0e0e0;
      height: 100vh;
      padding: 20px 15px;
      transition: all 0.3s ease;
      flex-shrink: 0;
    }

    .sidebar .profile {
      display: flex;
      align-items: center;
      margin-bottom: 30px;
    }

    .sidebar .profile img {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      margin-right: 12px;
      border: 2px solid #6a2ebc;
    }

    .sidebar .profile strong {
      font-size: 15px;
      color: #333;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin: 15px 0;
      display: flex;
      align-items: center;
      font-size: 14px;
      padding: 10px;
      border-radius: 8px;
      transition: 0.2s;
    }

    .sidebar ul li i {
      margin-right: 12px;
    }

    .sidebar ul li:hover,
    .sidebar ul li.active {
      background: #6a2ebc;
      color: #fff;
      cursor: pointer;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      width: 100%;
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

    .topbar .icons {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-left: auto;
    }

    .topbar .icons a {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      font-size: 18px;
      text-decoration: none;
      transition: 0.3s ease;
      cursor: pointer;
    }

    .topbar .icons .notif-btn {
      background: #f1f1f1;
      color: #333;
    }

    .topbar .icons .logout-btn {
      background: #f44336;
      color: white;
      font-weight: bold;
    }

    .topbar .icons .settings-btn {
      background: #6a2ebc;
      color: white;
    }

    .topbar .icons a:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    }

    /* Layout container */
    .content-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    /* Dashboard Content */
    .main {
      flex: 1;
      padding: 25px;
    }

    /* Cards */
    .cards {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .card {
      flex: 1;
      min-width: 200px;
      background: #fff;
      border: 2px solid #3cd5c8;
      border-radius: 15px;
      text-align: center;
      padding: 25px;
      font-size: 14px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      transition: 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 14px rgba(0,0,0,0.1);
    }

    .card div {
      font-size: 30px;
    }

    .card h3 {
      margin-top: 10px;
      font-size: 18px;
      color: #444;
    }

    /* Stock Table */
    .stock {
      background: #fff;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }

    .stock h3 {
      background: linear-gradient(90deg, #6a2ebc, #3cd5c8);
      color: white;
      padding: 12px;
      border-radius: 10px;
      font-size: 16px;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    table th, table td {
      padding: 14px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
      font-size: 14px;
    }

    table th {
      background: #fafafa;
      font-weight: 600;
    }

    .status {
      padding: 5px 10px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: bold;
    }

    .low { background: #fff3cd; color: #856404; }
    .in { background: #d4edda; color: #155724; }
    .critical { background: #f8d7da; color: #721c24; }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        width: 200px;
      }
      .cards {
        flex-direction: column;
      }
      .card {
        width: 100%;
      }
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
      <li><i>👥</i> customer Accounts</li>
      <li><a href="{{ route('admin.users.index' ) }}"><i>👤</i> Staff Accounts</a></li>
      <li><a href="{{ route('admin.templates.index') }}"><i>📑</i> Templates</a></li> 
      <li><i>📦</i> Order Summaries</li>
      <li><a href="{{ route('admin.materials.index') }}"><i>📑</i> Inventory</a></li> 
      <li><i>💬</i> Messages</li>
    </ul>
  </div>

  <!-- Main Content Wrapper -->
  <div class="content-wrapper">
    <!-- Topbar -->
    <div class="topbar">
      <div class="logo">InkWise</div>
      <div class="icons">
        <a href="#" class="notif-btn">🔔</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
          @csrf
        </form>
        <a href="#" class="logout-btn"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">⏻</a>
        <a href="#" class="settings-btn">⚙</a>
      </div>
    </div>

    <!-- Dashboard Main -->
    <div class="main">
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
