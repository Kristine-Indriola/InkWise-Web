<!DOCTYPE html>
<html lang="en">
<head>
  @stack('styles')

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'InkWise Dashboard')</title>
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

 /* 🌙 DARK MODE */
body.dark-mode {
  background: #121212;
  color: #e4e4e4;
}

/* Sidebar */
body.dark-mode .sidebar {
  background: #1e1e1e;
  border-right: 1px solid #333;
}

body.dark-mode .sidebar .profile strong {
  color: #e4e4e4;
}

body.dark-mode .sidebar ul li {
  color: #ccc;
}

body.dark-mode .sidebar ul li:hover,
body.dark-mode .sidebar ul li.active {
  background: #6a2ebc;
  color: #fff;
}

/* Topbar */
body.dark-mode .topbar {
  background: #1e1e1e;
  border-bottom: 1px solid #333;
}

body.dark-mode .topbar .logo {
  color: #e4e4e4;
}

body.dark-mode .topbar .icons .notif-btn {
  background: #2a2a2a;
  color: #ccc;
}

body.dark-mode .topbar .icons .logout-btn {
  background: #b02a2a;
}

body.dark-mode .topbar .icons .settings-btn {
  background: #8540d9;
}

/* Container / Main content */
body.dark-mode .container {
  background: #1e1e1e;
  color: #ddd;
  box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}

/* Cards */
body.dark-mode .card {
  background: #1e1e1e;
  border: 2px solid #3cd5c8;
  color: #eee;
  box-shadow: 0 4px 10px rgba(0,0,0,0.4);
}

body.dark-mode .card h3 {
  color: #ddd;
}

/* Stock section */
body.dark-mode .stock {
  background: #1e1e1e;
  box-shadow: 0 4px 10px rgba(0,0,0,0.4);
}

body.dark-mode .stock h3 {
  background: linear-gradient(90deg, #6a2ebc, #3cd5c8);
}

/* Tables */
body.dark-mode table {
  color: #e4e4e4;
}

body.dark-mode table th {
  background: #2b2b2b;
  color: #ddd;
  border-bottom: 1px solid #444;
}

body.dark-mode table td {
  border-bottom: 1px solid #333;
}

body.dark-mode tbody tr:hover {
  background: #2a2f38;
}

/* Status badges */
body.dark-mode .status {
  color: #fff;
}

/* Buttons */
body.dark-mode .btn-primary {
  background: #28a745;
  color: #fff;
}

body.dark-mode .btn-primary:hover {
  background: #218838;
}

body.dark-mode .btn-warning {
  background: #e0a800;
  color: #fff;
}

body.dark-mode .btn-danger {
  background: #bd2130;
  color: #fff;
}


  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
   <div class="profile">
        <a href="{{ route('admin.profile.show') }}" 
           style="display:flex; align-items:center; text-decoration:none; color:inherit;">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(optional(Auth::user()->staff)->first_name . ' ' . optional(Auth::user()->staff)->last_name ?? Auth::user()->email) }}&background=6a2ebc&color=fff&bold=true" 
             alt="Admin Avatar" 
             style="border-radius:50%; margin-right:10px; width:55px; height:55px; border:2px solid #6a2ebc;">
        <div>
                <strong>{{ Auth::user()->name ?? 'Admin' }}</strong> 
                <span style="color:green;">✔</span>
            </div>
        </a>
    </div>
   <ul>
  <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <a href="{{ route('admin.dashboard') }}"><i>🏠</i> Dashboard</a>
  </li>

   <li class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
    <a href="{{ route('admin.customers.index') }}"><i>👥</i> Customer Accounts</a>
</li>

  <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
    <a href="{{ route('admin.users.index') }}"><i>👤</i> Staff Accounts</a>
  </li>

  <li class="{{ request()->routeIs('admin.templates.*') ? 'active' : '' }}">
    <a href="{{ route('admin.templates.index') }}"><i>📑</i> Templates</a>
  </li>

  <li><i>📦</i> Order Summaries</li>

 <li class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
    <a href="{{ route('admin.messages.index') }}"><i>💬</i> Messages</a>
</li>

<li class="{{ request()->routeIs('admin.materials.*') ? 'active' : '' }}">
    <a href="{{ route('admin.materials.index') }}"><i>📝</i> Materials</a>
</li>

  <li><i>📊</i> Reports</li>
</ul>

  </div>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Topbar -->
    <div class="topbar">
      <div class="logo">InkWise</div>
      <div class="icons">
       <a href="{{ route('admin.admin.materials.notification') }}" class="nav-link">
    🔔
    @php
        $lowCount = \App\Models\Material::whereHas('inventory', function($q) {
            $q->whereColumn('stock_level', '<=', 'reorder_level')
              ->where('stock_level', '>', 0);
        })->count();

        $outCount = \App\Models\Material::whereHas('inventory', function($q) {
            $q->where('stock_level', '<=', 0);
        })->count();

        $notifCount = $lowCount + $outCount;
    @endphp

    @if($notifCount > 0)
        <span class="notif-badge">{{ $notifCount }}</span>
    @endif
</a>


        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
          @csrf
        </form>
        <a href="#" class="logout-btn"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">⏻</a>
        
         <a href="#" id="theme-toggle" class="notif-btn">🌙</a>

      </div>
    </div>

    <!-- Main Page Content -->
    <div class="main">
      @yield('content')

      <script>
const toggleBtn = document.getElementById('theme-toggle');

// Load saved theme
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    toggleBtn.textContent = "☀"; // sun for light mode
} else {
    toggleBtn.textContent = "🌙"; // moon for dark mode
}

toggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');

    if (document.body.classList.contains('dark-mode')) {
        toggleBtn.textContent = "☀";
        localStorage.setItem('theme', 'dark');
    } else {
        toggleBtn.textContent = "🌙";
        localStorage.setItem('theme', 'light');
    }
});
</script>
    </div>
  </div>
</body>
</html>
