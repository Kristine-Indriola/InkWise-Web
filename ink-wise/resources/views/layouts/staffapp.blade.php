<!DOCTYPE html>
<html lang="en">
<head>
  @stack('styles')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Staff Dashboard')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="{{ asset('adminimage/inkwise.png') }}">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
    }

    body {
      background: #f4f6f9;
      display: flex;
      min-height: 100vh;
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
    }

    body, .sidebar {
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    body::-webkit-scrollbar,
    .sidebar::-webkit-scrollbar {
      display: none;
    }

    .sidebar {
      width: 230px;
      background: linear-gradient(135deg, #acd9b5, #6f94d6);
      border-right: 1px solid #e0e0e0;
      height: 100vh;
      padding: 20px 15px;
      transition: all 0.3s ease;
      flex-shrink: 0;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      z-index: 101;
      color: #f1f5f9;
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
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
    }

    .sidebar ul li i {
      margin-right: 12px;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      width: 100%;
      border-radius: 8px;
      padding: 10px;
      transition: background 0.2s, color 0.2s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li.active > a,
    .sidebar ul li.active a {
      background: rgba(255,255,255,0.5);
      color: #333 !important;
    }

    .sidebar.collapsed ul li a {
      justify-content: center;
      padding: 10px 0;
      width: 40px;
      margin: 0 auto;
      border-radius: 8px;
    }

    .sidebar.collapsed ul li a:hover,
    .sidebar.collapsed ul li.active > a,
    .sidebar.collapsed ul li.active a {
      background: rgba(255,255,255,0.5);
      color: #333 !important;
    }

    .sidebar.collapsed ul li a span.label {
      display: none;
    }

    .sidebar.collapsed {
      width: 70px;
      transition: width 0.3s;
    }
    .sidebar.collapsed .profile strong,
    .sidebar.collapsed .profile span,
    .sidebar.collapsed ul li a span.label {
      display: none;
    }
    .sidebar.collapsed ul li {
      justify-content: center;
      padding-left: 0;
      padding-right: 0;
    }
    .sidebar.collapsed ul li i {
      margin-right: 0;
      font-size: 20px;
    }
    .sidebar .collapse-btn {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      background: none;
      border: none;
      width: 100%;
      margin-bottom: 10px;
      font-size: 20px;
      cursor: pointer;
      color: #6a2ebc;
      transition: color 0.2s;
    }
    .sidebar .collapse-btn:hover {
      color: #3cd5c8;
    }
    .sidebar .collapse-btn i {
      transition: transform 0.3s;
    }
    .sidebar.collapsed .collapse-btn i {
      transform: rotate(180deg);
    }
    .sidebar.collapsed .profile div {
      display: none;
    }
    .sidebar.collapsed .profile img {
      margin-right: 0;
      display: block;
    }
    @media (max-width: 900px) {
      .sidebar {
        width: 70px;
      }
      .sidebar:not(.collapsed) {
        width: 230px;
      }
    }

    .topbar {
      background: linear-gradient(135deg, rgba(172,217,181,0.95), rgba(111,148,214,0.92));
      height: 64px;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      padding: 0 18px;
      width: 100%;
      position: sticky;
      top: 0;
      z-index: 90;
      transition: left 0.32s ease; /* animate when sidebar collapses */
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      box-shadow: 0 6px 18px rgba(16,24,40,0.06);
    }

    .topbar .logo {
      font-size: 20px;
      font-weight: 800;
      color: #16325c;
      letter-spacing: 0.6px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding-left: 6px;
      margin-left: 230px;
      transition: margin-left 0.32s ease;
    }

    /* When the sidebar collapses, shift the topbar/logo to match compact width */
    .sidebar.collapsed ~ .content-wrapper .topbar { left: 70px; }
    .sidebar.collapsed ~ .content-wrapper .topbar .logo { margin-left: 70px; }

    .topbar .icons {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-left: auto;
      position: relative;
    }

    .topbar .icons a,
    .topbar .icons button,
    .topbar .icons .nav-link {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      width: 44px;
      height: 44px;
      border-radius: 10px;
      font-size: 18px;
      text-decoration: none;
      transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
      cursor: pointer;
      background: rgba(255,255,255,0.85);
      color: #1f2937;
      border: 1px solid rgba(0,0,0,0.04);
      box-shadow: 0 2px 6px rgba(16,24,40,0.04);
    }

    .topbar .icons a:hover,
    .topbar .icons button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(16,24,40,0.08);
      background: #fff;
    }

    .topbar .icons .notif-btn {
      background: transparent !important;
      color: #94b9ff !important;
      border: none !important;
      box-shadow: none !important;
      width: auto !important;
      height: auto !important;
      padding: 6px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    .topbar .icons .logout-btn {
      background: linear-gradient(90deg,#ff6b6b,#f44336);
      color: white;
      font-weight: 700;
    }

    .topbar .icons .settings-btn {
      background: linear-gradient(90deg,#6a2ebc,#3cd5c8);
      color: white;
    }

    .notif-badge {
      position: absolute;
      top: 6px;
      right: 6px;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #ef4444;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      border-radius: 999px;
      box-shadow: 0 2px 6px rgba(16,24,40,0.12);
    }

    .topbar .icons .nav-link { position: relative; }

    .topbar .icons .notif-btn i,
    .topbar .icons .notif-btn .fi {
      color: #94b9ff !important;
      font-size: 22px !important;
    }

    .content-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      margin-left: 0;
    }

    .main {
      flex: 1;
      padding: 25px;
      padding-left: 230px;
    }

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
      font-family: 'Nunito', sans-serif;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 14px rgba(0,0,0,0.1);
    }

    .card div {
      font-size: 30px;
      font-family: 'Nunito', sans-serif;
    }

    .card h3 {
      margin-top: 10px;
      font-size: 18px;
      color: #444;
    }

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

    @media (max-width: 768px) {
      .sidebar {
        position: sticky;
        width: 200px;
      }
      .content-wrapper {
        margin-left: 0;
      }
      .main { padding-left: 0; }
      .topbar .logo { margin-left: 0; padding-left: 6px; }
      .cards {
        flex-direction: column;
      }
      .card {
        width: 100%;
      }
    }

    body.dark-mode {
      background: #121212;
      color: #e4e4e4;
    }

    body.dark-mode .sidebar {
      background: linear-gradient(90deg, #000000, #3533cd) !important;
      border-right: 1px solid #3533cd !important;
    }

    body.dark-mode .sidebar ul li a:hover,
    body.dark-mode .sidebar ul li.active > a,
    body.dark-mode .sidebar ul li.active a {
      background: rgba(255,255,255,0.18) !important;
      color: #fff !important;
    }

    body.dark-mode .topbar {
      background: linear-gradient(90deg, #000000, #3533cd) !important;
      border-bottom: 1px solid #3533cd !important;
    }

    body.dark-mode .topbar .logo {
      color: #e4e4e4;
    }

    body.dark-mode .topbar .icons .notif-btn {
      background: #23244a;
      color: #fff;
    }

    body.dark-mode .topbar .icons .logout-btn {
      background: #3533cd;
    }

    body.dark-mode .topbar .icons .settings-btn {
      background: #3533cd;
    }

    body.dark-mode .container,
    body.dark-mode .stock {
      background: #181a2a !important;
      color: #fff;
      box-shadow: 0 4px 12px rgba(53,51,205,0.15);
    }

    body.dark-mode .cards .card,
    body.dark-mode .card {
      border: 2px solid #3533cd !important;
      background: #181a2a !important;
      color: #3533cd !important;
      box-shadow: 0 4px 10px rgba(53,51,205,0.15);
    }
    body.dark-mode .cards .card h3,
    body.dark-mode .cards .card p,
    body.dark-mode .cards .card div {
      color: #3533cd !important;
    }
    body.dark-mode .cards .card:hover {
      box-shadow: 0 6px 18px rgba(53,51,205,0.25);
      background: #fafafa !important;
      border-color: #3533cd !important;
    }

    body.dark-mode .stock h3 {
      background: #3533cd !important;
      color: #fff !important;
    }

    body.dark-mode table th {
      background: #23244a !important;
      color: #fff !important;
      border-bottom: 1px solid #3533cd !important;
    }
    body.dark-mode table td {
      border-bottom: 1px solid #3533cd !important;
    }
    body.dark-mode .status {
      background: #3533cd !important;
      color: #fff !important;
    }

    body.dark-mode .btn-primary,
    body.dark-mode .btn,
    body.dark-mode .btn-danger,
    body.dark-mode .btn-warning {
      background: linear-gradient(90deg, #000000, #3533cd) !important;
      color: #fff !important;
      border: none !important;
    }

    .theme-toggle-switch {
      display: flex;
      align-items: center;
      width: 70px;
      height: 34px;
      background: linear-gradient(90deg, #ffd580, #ffb347);
      border-radius: 20px;
      position: relative;
      cursor: pointer;
      transition: background 0.4s;
      box-sizing: border-box;
      padding: 0 8px;
      margin-right: 18px;
      border: 2px solid #eee;
      user-select: none;
    }
    .theme-toggle-switch.night {
      background: linear-gradient(90deg, #6a82fb, #fc5c7d);
    }
    .theme-toggle-label {
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      color: #fff;
      font-weight: 700;
      margin-left: 8px;
      margin-right: 0;
      transition: color 0.4s;
      z-index: 2;
      letter-spacing: 1px;
    }
    .theme-toggle-switch.night .theme-toggle-label {
      color: #fff;
    }
    .theme-toggle-knob {
      position: absolute;
      top: 4px;
      left: 4px;
      width: 26px;
      height: 26px;
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      color: #ffb347;
      transition: left 0.35s cubic-bezier(.4,2.2,.2,1), color 0.4s, background 0.4s;
      z-index: 3;
    }
    .theme-toggle-switch.night .theme-toggle-knob {
      left: 40px;
      color: #6a82fb;
      background: #fff;
    }
    .theme-toggle-icon {
      pointer-events: none;
    }
  </style>
  <script>
    // Apply theme on page load (before body renders) - avoids flash and persists across pages..
    (function() {
      try {
        var isDark = localStorage.getItem('theme') === 'dark';
        if (isDark) {
          document.documentElement.classList.add('dark-mode');
        } else {
          document.documentElement.classList.remove('dark-mode');
        }
        if (document.body) {
          if (isDark) document.body.classList.add('dark-mode'); else document.body.classList.remove('dark-mode');
        } else {
          document.addEventListener('DOMContentLoaded', function() {
            if (isDark) document.body.classList.add('dark-mode'); else document.body.classList.remove('dark-mode');
          });
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>
  <div class="sidebar" id="sidebar" style="padding-top:32px;">
    <button class="collapse-btn" id="sidebarToggle" title="Toggle Sidebar" style="margin-left:auto; margin-right:0;">
      <i class="fi fi-rr-angle-double-right" id="sidebarToggleIcon"></i>
    </button>
    <div class="profile" style="display:flex; flex-direction:column; align-items:center; justify-content:flex-start; margin-bottom:18px;">
      <img src="{{ asset('images/logo.png') }}" alt="InkWise Logo"
           style="width:90px; height:90px; max-width:100%; max-height:100px; background:transparent; border-radius:24px; border:none; box-shadow:0 4px 16px rgba(0,0,0,0.07); object-fit:contain; margin-bottom:8px;">
    </div>
    <ul>
      <li class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.dashboard') }}"><i class="fi fi-sr-house-chimney"></i> <span class="label">Dashboard</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.templates.*') ? 'active' : '' }}">
        <a href="{{ route('staff.templates.uploaded') }}"><i class="fa-solid fa-images"></i> <span class="label">Templates</span></a>
      </li>

      <li class="{{ request()->routeIs('staff.messages.*') ? 'active' : '' }}">
        <a href="{{ route('staff.messages.index') }}"><i class="fa-solid fa-envelope"></i> <span class="label">Messages</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.order_list.index') ? 'active' : '' }}">
        <a href="{{ route('staff.order_list.index') }}"><i class="fa-solid fa-list"></i> <span class="label">Order List</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.customer_profile') ? 'active' : '' }}">
        <a href="{{ route('staff.customer_profile') }}"><i class="fa-solid fa-users"></i> <span class="label">Customer Profiles</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.notify.customers') ? 'active' : '' }}">
        <a href="{{ route('staff.notify.customers') }}"><i class="fa-solid fa-bell"></i> <span class="label">Notify Customers</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.inventory.*') ? 'active' : '' }}">
        <a href="{{ route('staff.inventory.index') }}"><i class="fa-solid fa-warehouse"></i> <span class="label">Inventory</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.materials.*') ? 'active' : '' }}">
        <a href="{{ route('staff.materials.index') }}"><i class="fa-solid fa-boxes-stacked"></i> <span class="label">Materials</span></a>
      </li>
    </ul>
  </div>

  <div class="content-wrapper">
    <div class="topbar">
      <div class="logo">InkWise</div>
      <div class="icons" style="display: flex; align-items: center; gap: 24px; margin-left: auto; justify-content: center;">
        <a href="{{ route('staff.materials.notification') }}" class="nav-link notif-btn" style="display:flex; align-items:center; justify-content:center;">
          <i class="fi fi-ss-bell" style="font-size:22px;"></i>
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
        <a href="{{ route('staff.messages.index') }}" class="nav-link notif-btn" title="Messages" style="display:flex; align-items:center; justify-content:center;">
          <i class="fi fi-sr-envelope" style="font-size:20px;"></i>
        </a>
        <div id="theme-toggle-switch" class="theme-toggle-switch" title="Toggle dark/light mode" style="margin:0;">
          <span class="theme-toggle-label" id="theme-toggle-label">DAY</span>
          <span class="theme-toggle-knob" id="theme-toggle-knob">
            <span class="theme-toggle-icon" id="theme-toggle-icon">
              <i class="fi fi-rr-brightness"></i>
            </span>
          </span>
        </div>
        <div class="profile-dropdown" style="position: relative; display:flex; align-items:center; gap:6px;">
          <a href="{{ route('staff.profile.edit') }}" id="profileImageLink" style="display:flex; align-items:center; text-decoration:none; color:inherit;">
            <img src="{{ asset('staffimage/FRECHY.jpg') }}" alt="Staff Profile" style="border-radius:50%; width:36px; height:36px; border:2px solid #6a2ebc; object-fit:cover;">
          </a>
          <span id="profileDropdownToggle" style="cursor:pointer; display:inline-flex; align-items:center; margin-left:6px;">
            <i class="fi fi-rr-angle-small-down" style="font-size:18px;"></i>
          </span>
          <div id="profileDropdownMenu"
               style="
                 display:none;
                 position:absolute;
                 right:0;
                 top: calc(100% + 6px);
                 background:#fff;
                 min-width:180px;
                 box-shadow:0 8px 32px rgba(0,0,0,0.18);
                 border-radius:14px;
                 z-index:999;
                 overflow:hidden;
                 padding: 8px 0;
                 border: 1px solid #eaeaea;
                 ">
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit"
                    style="width:100%; background:none; border:none; color:#f44336; font-size:16px; padding:14px 22px; text-align:left; cursor:pointer;">
              ⏻ Log Out
            </button>
          </form>
        </div>
        </div>
      </div>
    </div>

    <div class="main">
      @yield('content')

      <script>
        (function() {
          const sidebar = document.getElementById('sidebar');
          const sidebarToggle = document.getElementById('sidebarToggle');
          const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
          if (sidebar && sidebarToggle) {
            if (localStorage.getItem('staff-sidebar-collapsed') === 'true') {
              sidebar.classList.add('collapsed');
              document.body.classList.add('sidebar-collapsed');
              if (sidebarToggleIcon) sidebarToggleIcon.classList.add('is-rotated');
            }
            sidebarToggle.addEventListener('click', function() {
              sidebar.classList.toggle('collapsed');
              const isCollapsed = sidebar.classList.contains('collapsed');
              localStorage.setItem('staff-sidebar-collapsed', isCollapsed);
              if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
                if (sidebarToggleIcon) sidebarToggleIcon.classList.add('is-rotated');
              } else {
                document.body.classList.remove('sidebar-collapsed');
                if (sidebarToggleIcon) sidebarToggleIcon.classList.remove('is-rotated');
              }
            });
          }

          const themeSwitch = document.getElementById('theme-toggle-switch');
          const themeLabel = document.getElementById('theme-toggle-label');
          const themeIcon = document.getElementById('theme-toggle-icon');
          function setThemeSwitch() {
            if (localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode')) {
              themeSwitch.classList.add('night');
              themeLabel.textContent = "NIGHT";
              themeIcon.innerHTML = '<i class="fi fi-rr-moon"></i>';
            } else {
              themeSwitch.classList.remove('night');
              themeLabel.textContent = "DAY";
              themeIcon.innerHTML = '<i class="fi fi-rr-brightness"></i>';
            }
          }
          if (themeSwitch && themeLabel && themeIcon) {
            setThemeSwitch();
            themeSwitch.addEventListener('click', function() {
              document.body.classList.toggle('dark-mode');
              if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
              } else {
                localStorage.setItem('theme', 'light');
              }
              setThemeSwitch();
            });
          }

          const profileToggle = document.getElementById('profileDropdownToggle');
          const profileMenu = document.getElementById('profileDropdownMenu');
          if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
            });
            profileMenu.addEventListener('click', function(e) {
              e.stopPropagation();
            });
            document.addEventListener('click', function() {
              profileMenu.style.display = 'none';
            });
          }
        })();
      </script>
    </div>
  </div>

  @yield('scripts')
  @stack('scripts')
  </body>
</html>
