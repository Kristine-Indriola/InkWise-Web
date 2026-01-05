<!DOCTYPE html>
<html lang="en">
<head>
@php
    // Get current admin user with staff relationship for profile image
    $currentAdmin = Auth::user();
    $adminAbbr = '';
    if ($currentAdmin && $currentAdmin->role === 'admin') {
        $currentAdmin->load('staff');

        // Generate initials for fallback avatar
        if ($currentAdmin->staff && $currentAdmin->staff->first_name) {
            $first = $currentAdmin->staff->first_name;
            $last = $currentAdmin->staff->last_name ?? '';
            $adminAbbr = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
        } elseif (!empty($currentAdmin->name)) {
            $parts = preg_split('/\s+/', trim($currentAdmin->name));
            $first = $parts[0] ?? '';
            $second = $parts[1] ?? '';
            $adminAbbr = strtoupper(substr($first, 0, 1) . ($second ? substr($second, 0, 1) : ''));
        }
    }
@endphp

  @stack('styles')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'InkWise Dashboard')</title>
  @stack('styles')
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

    /* Hide scrollbars for sidebar and body */
    body, .sidebar {
      scrollbar-width: none;         /* Firefox */
      -ms-overflow-style: none;      /* IE and Edge */
    }
    body::-webkit-scrollbar,
    .sidebar::-webkit-scrollbar {
      display: none;                 /* Chrome, Safari, Opera */
    }

    /* Sidebar */
    .sidebar {
      width: 230px;
      background: linear-gradient(135deg, #acd9b5, #6f94d6); /* darker take on original gradient */
      border-right: 1px solid #e0e0e0;
      height: 100vh;
      padding: 20px 15px;
      transition: all 0.3s ease;
      flex-shrink: 0;
      position: fixed; /* Changed from sticky to fixed */
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

    /* Remove the old hover/active styles on .sidebar ul li */
    .sidebar ul li:hover,
    .sidebar ul li.active {
      /* removed */
    }

    /* Sidebar link base style */
    .sidebar ul li a {
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      width: 100%;
      border-radius: 8px;
      padding: 10px;
      transition: background 0.2s, color 0.2s;
      position: relative; /* allow badges to anchor correctly */
    }

    /* Hover and active effect for expanded sidebar */
    .sidebar ul li a:hover,
    .sidebar ul li.active > a,
    .sidebar ul li.active a {
      background: rgba(255,255,255,0.5);
      color: #333 !important;
    }

    .sidebar ul li.has-submenu {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 6px;
    }

    .sidebar ul li.has-submenu .submenu-trigger {
      width: 100%;
      background: transparent;
      border: none;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      border-radius: 8px;
      color: inherit;
      font-size: 14px;
      text-align: left;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }

    .sidebar ul li.has-submenu .submenu-trigger:focus-visible {
      outline: 2px solid rgba(63,213,200,0.65);
      outline-offset: 2px;
    }

    .sidebar ul li.has-submenu .submenu-trigger:hover,
    .sidebar ul li.has-submenu.expanded .submenu-trigger {
      background: rgba(255,255,255,0.5);
      color: #333 !important;
    }

    .sidebar ul li.has-submenu .submenu-caret {
      margin-left: auto;
      transition: transform 0.3s ease;
    }

    .sidebar ul li.has-submenu.expanded .submenu-caret {
      transform: rotate(180deg);
    }

    .sidebar ul li.has-submenu .submenu {
      display: none;
      flex-direction: column;
      gap: 4px;
      margin-left: 36px;
    }

    .sidebar ul li.has-submenu.expanded .submenu {
      display: flex;
    }

    .sidebar ul li.has-submenu .submenu li {
      margin: 0;
      padding: 0;
    }

    .sidebar ul li.has-submenu .submenu li a {
      padding: 8px 10px;
      font-size: 13px;
    }

    .sidebar.collapsed ul li.has-submenu {
      gap: 0;
    }

    .sidebar.collapsed ul li.has-submenu .submenu {
      display: none !important;
    }

    .sidebar.collapsed ul li.has-submenu .submenu-trigger {
      justify-content: center;
      padding: 10px 0;
    }

    .sidebar.collapsed ul li.has-submenu .submenu-trigger .label,
    .sidebar.collapsed ul li.has-submenu .submenu-caret {
      display: none;
    }

    /* Collapsed sidebar: make hover/active background fit icon only */
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

    /* Hide label in collapsed mode */
    .sidebar.collapsed ul li a span.label {
      display: none;
    }

    /* Sidebar collapse styles */
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
    .collapse-btn i.is-rotated { transform: rotate(180deg); }
    /* Hide profile name and check when collapsed, show only image */
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

    /* Topbar (improved) */
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
  z-index: 90; /* sit behind sidebar (sidebar z-index:101) */
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
      margin-left: 230px; /* visually align logo to the right of the fixed sidebar */
      transition: margin-left 0.32s ease;
    }

    /* When the sidebar collapses, shift the topbar logo to match compact width */
    .sidebar.collapsed ~ .content-wrapper .topbar {
      left: 70px; /* if you prefer the topbar to shift, keep this; otherwise topbar remains full-width */
    }
    .sidebar.collapsed ~ .content-wrapper .topbar .logo {
      margin-left: 70px;
    }

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

    .sidebar ul li .notif-badge {
      top: 6px;
      right: 12px;
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

    /* Notification badge */
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

    /* Make the nav-link container position relative so badges align */
    .topbar .icons .nav-link { position: relative; }

    /* Notif icon override to target the icon element */
    .topbar .icons .notif-btn i,
    .topbar .icons .notif-btn .fi {
      color: #94b9ff !important;
      font-size: 22px !important;
    }

    /* Layout container */
    .content-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      margin-left: 0; /* topbar sits full width behind sidebar */
    }

    /* Dashboard Content */
    .main {
      flex: 1;
      padding: 25px;
      padding-left: 230px; /* offset main content so sidebar doesn't overlap */
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
       font-family: 'Nunito', sans-serif;
    font-weight: 600;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 14px rgba(0,0,0,0.1);
    }

    .card div {
      font-size: 30px;
       font-family: 'Nunito', sans-serif;
    font-weight: 600;
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
        position: sticky; /* Revert to sticky on mobile for better UX */
        width: 200px;
      }
      .content-wrapper {
        margin-left: 0; /* Remove margin on mobile */
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

 /* 🌙 DARK MODE */
body.dark-mode {
  background: #121212;
  color: #e4e4e4;
}

/* Sidebar */
body.dark-mode .sidebar {
  background: linear-gradient(90deg, #000000, #3533cd) !important;
  border-right: 1px solid #3533cd !important;
}

body.dark-mode .sidebar ul li a:hover,
body.dark-mode .sidebar ul li.active > a,
body.dark-mode .sidebar ul li.active a {
  background: rgba(255,255,255,0.18) !important; /* white transparent */
  color: #fff !important;
}

/* Topbar */
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

/* Buttons */
body.dark-mode .btn-primary,
body.dark-mode .btn,
body.dark-mode .btn-danger,
body.dark-mode .btn-warning {
  background: linear-gradient(90deg, #000000, #3533cd) !important;
  color: #fff !important;
  border: none !important;
}

/* Custom toggle switch for dark/light mode */
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
    // Apply theme on page load (before body renders) - avoids flash and persists across pages
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
          document.addEventListener('DOMContentLoaded', function(){ if (isDark) document.body.classList.add('dark-mode'); else document.body.classList.remove('dark-mode'); });
        }
      } catch (e) { console.error('Theme init error', e); }
    })();
  </script>
  <!-- Lazy-loader for product modal assets: call window.__loadProductModalAssets() to inject CSS+JS when needed -->
  <script>
    (function(){
      if (window.__productSlideAssetsLoaded) return;
      function injectStylesheet(href){
        var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; document.head.appendChild(l);
      }
      function injectScript(src){
        return new Promise(function(res, rej){
          var s = document.createElement('script'); s.src = src; s.defer = true; s.onload = res; s.onerror = rej; document.head.appendChild(s);
        });
      }

      // Loader for product modal assets (new consolidated files)
      window.__loadProductModalAssets = function(){
        if (window.__productModalAssetsLoaded) return Promise.resolve();
        try { injectStylesheet('{{ asset("css/admin-css/product.css") }}'); } catch(e){}
        return injectScript('{{ asset("js/admin/product.js") }}').then(function(){ window.__productModalAssetsLoaded = true; }).catch(function(){ window.__productModalAssetsLoaded = true; });
      };

      // Auto-load on known products index path for convenience
      try {
        var path = window.location.pathname || '';
        if (path.match(/\/admin\/products(\/.*)?$/i)) {
          // Load lazily but don't block render
          setTimeout(function(){ window.__loadProductModalAssets(); }, 80);
        }
      } catch(e){}

      // Also listen for an event to load later
  document.addEventListener('loadProductModalAssets', function(){ window.__loadProductModalAssets(); }, { once: true });
    })();
  </script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar" style="padding-top:32px;">
    <button class="collapse-btn" id="sidebarToggle" title="Toggle Sidebar" style="margin-left:auto; margin-right:0;">
      <i class="fi fi-rr-angle-double-right" id="sidebarToggleIcon"></i>
    </button>
    <div class="profile" style="display:flex; flex-direction:column; align-items:center; justify-content:flex-start; margin-bottom:18px;">
      <img src="/adminimage/inkwise.png" alt="InkWise Logo"
           style="width:90px; height:90px; max-width:100%; max-height:100px; background:transparent; border-radius:24px; border:none; box-shadow:0 4px 16px rgba(0,0,0,0.07); object-fit:contain; margin-bottom:8px;">
    </div>

    <!-- Sidebar hover behavior: open submenu on pointer hover and support touch/keyboard -->
    <style>
      /* When pointer hovers the parent, show submenu */
      .sidebar ul li.has-submenu:hover > .submenu,
      .sidebar ul li.has-submenu.expanded > .submenu {
        display: block !important;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }

      /* Keep the submenu visually consistent with existing styles */
      .sidebar ul li.has-submenu > .submenu {
        display: none;
        transition: opacity 150ms ease, transform 150ms ease;
        opacity: 0;
        transform: translateY(-6px);
        pointer-events: none;
        z-index: 30;
      }

      /* Small hit-target to make hover feel responsive */
      .sidebar ul li.has-submenu {
        position: relative;
      }

      /* Avoid hover behavior on very small screens (touch-first) */
      @media (max-width: 768px) {
        .sidebar ul li.has-submenu:hover > .submenu {
          display: none !important;
        }
      }

      /* Rotate caret when submenu is visible */
      .sidebar ul li.has-submenu:hover .submenu-caret,
      .sidebar ul li.has-submenu.expanded .submenu-caret {
        transform: rotate(180deg);
        transition: transform 150ms ease;
      }
    </style>

    <script>
      (function () {
        // Add pointer event handlers to support touch devices and keyboard toggling
        const submenuParents = document.querySelectorAll('.sidebar .has-submenu');

        submenuParents.forEach(parent => {
          // For touch devices we toggle expanded class on first tap, follow link on second
          parent.addEventListener('touchstart', function (e) {
            // If not expanded, expand and prevent the immediate navigation
            if (!parent.classList.contains('expanded')) {
              parent.classList.add('expanded');
              const btn = parent.querySelector('.submenu-trigger');
              if (btn) btn.setAttribute('aria-expanded', 'true');
              e.preventDefault();
            }
          }, {passive: true});

          // Ensure hover (mouseenter) also sets expanded so caret & aria stay in sync
          parent.addEventListener('mouseenter', function () {
            if (!parent.classList.contains('expanded')) {
              parent.classList.add('expanded');
              const btn = parent.querySelector('.submenu-trigger');
              if (btn) btn.setAttribute('aria-expanded', 'true');
            }
          });

          // Toggle expanded on keyboard Enter/Space when focused
          const trigger = parent.querySelector('.submenu-trigger');
          if (trigger) {
            trigger.addEventListener('keydown', function (e) {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                parent.classList.toggle('expanded');
                const expanded = parent.classList.contains('expanded');
                trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
              }
            });
          }

          // Remove expanded on mouseleave to keep hover UX tidy
          parent.addEventListener('mouseleave', function () {
            if (parent.classList.contains('expanded')) {
              parent.classList.remove('expanded');
              const btn = parent.querySelector('.submenu-trigger');
              if (btn) btn.setAttribute('aria-expanded', 'false');
            }
          });
        });
      })();
    </script>
    <ul>
      <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a href="{{ route('admin.dashboard') }}"><i class="fi fi-rr-house-chimney"></i> <span class="label">Dashboard</span></a>
      </li>
      <li class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <a href="{{ route('admin.products.index') }}"><i class="fi fi-rr-boxes"></i> <span class="label">Products</span></a>
      </li>
      <li class="{{ (request()->routeIs('admin.ordersummary.*') || request()->routeIs('admin.orders.index')) ? 'active' : '' }}">
        <a href="{{ route('admin.orders.index') }}"><i class="fi fi-rr-list-check"></i> <span class="label">Order Summaries</span></a>
      </li>
      <li class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
        <a href="{{ route('admin.reviews.index') }}">
          <i class="fi fi-rr-star"></i>
          <span class="label">Reviews</span>
          @php
              $unrepliedReviewsCount = \App\Models\OrderRating::whereNull('staff_reply')->count();
          @endphp
          @if($unrepliedReviewsCount > 0)
              <span class="notif-badge">{{ $unrepliedReviewsCount }}</span>
          @endif
        </a>
      </li>
      <li class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
        <a href="{{ route('admin.payments.index') }}"><i class="fi fi-rr-credit-card"></i> <span class="label">Payment Transactions</span></a>
      </li>
      {{--<li class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
        <a href="{{ route('admin.messages.index') }}"><i class="fi fi-rr-comment-dots"></i> <span class="label">Messages</span></a>
      </li> --}}
      <li class="{{ request()->routeIs('admin.materials.*') ? 'active' : '' }}">
        <a href="{{ route('admin.materials.index') }}"><i class="fi fi-rr-blog-pencil"></i> <span class="label">Materials</span></a>
      </li>
      @php
        $reportsActive = request()->routeIs('admin.reports.*');
      @endphp
      <li class="has-submenu {{ $reportsActive ? 'expanded active' : '' }}">
        <button type="button" class="submenu-trigger" data-submenu-toggle="reports" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}">
          <i class="fi fi-rr-document"></i>
          <span class="label">Reports</span>
          <i class="fi fi-rr-angle-small-down submenu-caret" aria-hidden="true"></i>
        </button>
        <ul class="submenu" data-submenu="reports" aria-hidden="{{ $reportsActive ? 'false' : 'true' }}">
          <li class="{{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
            <a href="{{ route('admin.reports.sales') }}"><span class="label">Sales</span></a>
          </li>
          <li class="{{ request()->routeIs('admin.reports.inventory') ? 'active' : '' }}">
            <a href="{{ route('admin.reports.inventory') }}"><span class="label">Inventory</span></a>
          </li>
        </ul>
      </li>

          <li class="{{ request()->routeIs('admin.chatbot.index') ? 'active' : '' }}">
  <a href="{{ route('admin.chatbot.index') }}">
    <i class="fi fi-rr-comment-dots"></i>
    <span class="label">FAQ's</span>
  </a>
</li>

       @php
      $accountsActive = request()->routeIs('admin.customers.*')
        || (request()->routeIs('admin.users.*') && !request()->routeIs('admin.users.passwords.*'));
    @endphp
      <li class="has-submenu {{ $accountsActive ? 'expanded active' : '' }}">
        <button type="button" class="submenu-trigger" data-submenu-toggle="accounts" aria-expanded="{{ $accountsActive ? 'true' : 'false' }}">
          <i class="fi fi-rr-users"></i>
          <span class="label">Accounts</span>
          <i class="fi fi-rr-angle-small-down submenu-caret" aria-hidden="true"></i>
        </button>
        <ul class="submenu" data-submenu="accounts" aria-hidden="{{ $accountsActive ? 'false' : 'true' }}">
          <li class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
            <a href="{{ route('admin.customers.index') }}"><span class="label">Customer Accounts</span></a>
          </li>
          <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a href="{{ route('admin.users.index') }}"><span class="label">Staff Accounts</span></a>
          </li>
        </ul>
      </li>
      @php
          $settingsActive = request()->routeIs('admin.users.passwords.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.fonts.*');
      @endphp
          <li class="has-submenu {{ $settingsActive ? 'expanded active' : '' }}">
        <button type="button" class="submenu-trigger" data-submenu-toggle="settings" aria-expanded="{{ $settingsActive ? 'true' : 'false' }}">
          <i class="fi fi-rr-settings"></i>
          <span class="label">Settings</span>
          <i class="fi fi-rr-angle-small-down submenu-caret" aria-hidden="true"></i>
        </button>
        <ul class="submenu" data-submenu="settings" aria-hidden="{{ $settingsActive ? 'false' : 'true' }}">
          <li class="{{ request()->routeIs('admin.settings.site-content.*') ? 'active' : '' }}">
            <a href="{{ route('admin.settings.site-content.edit') }}"><span class="label">Site Content</span></a>
          </li>

          <li class="{{ request()->routeIs('admin.users.passwords.*') ? 'active' : '' }}">
            <a href="{{ route('admin.users.passwords.index') }}"><span class="label">Password Reset</span></a>
          </li>
        </ul>
      </li>

    </ul>
  </div>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Topbar -->
    
    <div class="topbar">
      <div class="logo">InkWise</div>
      <div class="icons" style="display: flex; align-items: center; gap: 24px; margin-left: auto; justify-content: center;">
        <!-- Notification Bell -->
    <a href="{{ route('admin.notifications') }}" class="nav-link notif-btn" title="Notifications" aria-label="Notifications" style="display:flex; align-items:center; justify-content:center;">
      <i class="fi fi-ss-bell" style="font-size:22px;"></i>
      @php
        /** @var \App\Models\User|null $adminUser */
        $adminUser = auth()->user();
        $notifCount = $adminUser?->notifications()->whereNull('read_at')->count() ?? 0;
      @endphp

      @if($notifCount > 0)
        <span class="notif-badge">{{ $notifCount }}</span>
      @endif
    </a>
        <!-- Paper plane / Messages quick-link (transparent background, colored icon) -->
        @php
          $adminUnreadMessageCount = isset($adminUnreadMessageCount)
            ? (int) $adminUnreadMessageCount
            : 0;
        @endphp
        <a href="{{ route('admin.messages.index') }}"
           class="nav-link notif-btn"
           title="Messages"
           data-messages-toggle="true"
           data-initial-unread="{{ $adminUnreadMessageCount }}"
           style="display:flex; align-items:center; justify-content:center;">
          <i class="fi fi-sr-envelope" style="font-size:20px;"></i>
          @if($adminUnreadMessageCount > 0)
            <span class="notif-badge" data-role="messages-unread-count">{{ $adminUnreadMessageCount }}</span>
          @endif
        </a>
        <!-- Day/Night Toggle Switch -->
        <div id="theme-toggle-switch" class="theme-toggle-switch" title="Toggle dark/light mode" style="margin:0;">
          <span class="theme-toggle-label" id="theme-toggle-label">DAY</span>
          <span class="theme-toggle-knob" id="theme-toggle-knob">
            <span class="theme-toggle-icon" id="theme-toggle-icon">
              <i class="fi fi-rr-brightness"></i>
            </span>
          </span>
        </div>
        <!-- Admin Profile Dropdown -->
        <div class="profile-dropdown" style="position: relative; display:flex; align-items:center; gap:6px;">
          <a href="{{ route('admin.profile.edit') }}" id="profileImageLink" style="display:flex; align-items:center; text-decoration:none; color:inherit;">
            @if($currentAdmin && $currentAdmin->staff && $currentAdmin->staff->profile_pic)
                <img src="{{ asset('storage/' . $currentAdmin->staff->profile_pic) }}"
                     alt="Admin Profile"
                     style="border-radius:50%; width:36px; height:36px; border:2px solid #6a2ebc; object-fit:cover;">
            @else
                <div style="border-radius:50%; width:36px; height:36px; border:2px solid #6a2ebc; background: linear-gradient(135deg, #acd9b5, #6f94d6); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:14px;">
                    {{ $adminAbbr ?: 'AD' }}
                </div>
            @endif
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
            <!-- My Profile link removed per request -->
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit"
                    style="width:100%; background:none; border:none; color:#f44336; font-size:16px; padding:14px 22px; text-align:left; cursor:pointer;">
              ⏻ Log Out
            </button>
          </form>
        </div>
        </div>
        <!-- End Admin Profile Dropdown -->
      </div>
    </div>

    <!-- Main Page Content -->
    <div class="main">
      @yield('content')

      <script>
  // Sidebar toggle logic
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
  if (localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    document.body.classList.add('sidebar-collapsed');
    if (sidebarToggleIcon) sidebarToggleIcon.classList.add('is-rotated');
  }
  sidebarToggle.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebar-collapsed', isCollapsed);
    if (isCollapsed) {
      document.body.classList.add('sidebar-collapsed');
      if (sidebarToggleIcon) sidebarToggleIcon.classList.add('is-rotated');
    } else {
      document.body.classList.remove('sidebar-collapsed');
      if (sidebarToggleIcon) sidebarToggleIcon.classList.remove('is-rotated');
    }
  });

  // Theme toggle logic
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

  // Sidebar submenu toggle
  const submenuParents = Array.from(document.querySelectorAll('.has-submenu'));

  function setParentState(parent, expanded) {
    const trigger = parent.querySelector('.submenu-trigger');
    const submenu = parent.querySelector('.submenu');

    if (expanded) {
      parent.classList.add('expanded', 'active');
    } else {
      parent.classList.remove('expanded');
      const hasActiveChild = parent.querySelector('.submenu li.active') !== null;
      if (!hasActiveChild) {
        parent.classList.remove('active');
      }
    }

    if (trigger) {
      trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
    if (submenu) {
      submenu.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    }
  }

  function collapseOtherSubmenus(exceptParent) {
    submenuParents.forEach(function(parent) {
      if (parent !== exceptParent) {
        setParentState(parent, false);
      }
    });
  }

  submenuParents.forEach(function(parent) {
    const trigger = parent.querySelector('.submenu-trigger');
    if (!trigger) {
      return;
    }

    setParentState(parent, parent.classList.contains('expanded'));

    trigger.addEventListener('click', function(event) {
      event.preventDefault();
      const willExpand = !parent.classList.contains('expanded');
      collapseOtherSubmenus(parent);
      setParentState(parent, willExpand);
    });
  });

  document.addEventListener('click', function(event) {
    if (!event.target.closest('.has-submenu')) {
      collapseOtherSubmenus(null);
    }
  });

  // Profile dropdown logic (arrow only)
  const profileToggle = document.getElementById('profileDropdownToggle');
  const profileMenu = document.getElementById('profileDropdownMenu');
  if (profileToggle && profileMenu) {
    profileToggle.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
    });
    // Prevent closing when clicking inside the dropdown
    profileMenu.addEventListener('click', function(e) {
      e.stopPropagation();
    });
    // Close when clicking outside
    document.addEventListener('click', function() {
      profileMenu.style.display = 'none';
    });
  }
</script>
    </div>
  </div>

  {{-- make sure scripts from views are rendered here --}}
  {{-- First render any @push("scripts") stacks (preferred), then support older @section('scripts') uses. --}}
  @stack('scripts')
  @yield('scripts')

  <script>
    // Accessibility helper: ensure hidden submenus don't expose focusable items
    (function(){
      function updateSubmenuFocus(submenu){
        var hidden = submenu.getAttribute('aria-hidden') === 'true';
        var focusables = submenu.querySelectorAll('a, button, input, [tabindex]');
        focusables.forEach(function(el){
          if (hidden) {
            if (!el.hasAttribute('data-prev-tabindex')) el.setAttribute('data-prev-tabindex', el.getAttribute('tabindex') ?? '');
            el.setAttribute('tabindex', '-1');
          } else {
            var prev = el.getAttribute('data-prev-tabindex');
            if (prev !== null) {
              if (prev === '') el.removeAttribute('tabindex'); else el.setAttribute('tabindex', prev);
              el.removeAttribute('data-prev-tabindex');
            } else {
              el.removeAttribute('tabindex');
            }
          }
        });
      }

      document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.submenu').forEach(function(sm){ updateSubmenuFocus(sm); });
        var obs = new MutationObserver(function(muts){
          muts.forEach(function(m){ if (m.type === 'attributes' && m.attributeName === 'aria-hidden') updateSubmenuFocus(m.target); });
        });
        document.querySelectorAll('.submenu').forEach(function(sm){ obs.observe(sm, { attributes: true }); });
      });
    })();
  </script>
</body>
</html>
