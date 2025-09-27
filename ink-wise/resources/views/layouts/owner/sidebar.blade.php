@extends('layouts.owner.app')

<aside class="sidebar">
  <h2>InkWise</h2>

  <div class="profile">
    <div class="avatar">👤</div>
    <div>
      <div style="font-weight:700;">
        <a href="{{ route('owner.profile.show') }}">Owner Profile</a>
      </div>
      <div style="color:#64748b;font-size:12px;">
        {{ auth('owner')->user()->email ?? 'owner@example.com' }}
      </div>
    </div>
  </div>

  <ul class="navlist">
    <li>
      <a href="{{ route('owner.home') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.home') ? 'active' : '' }}">
          <span class="text">Dashboard</span><span class="ico">🏠</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.staff.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.staff.index') ? 'active' : '' }}">
          <span class="text">Approve Staff Account</span><span class="ico">✅</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.order.workflow') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.order.workflow') ? 'active' : '' }}">
          <span class="text">Monitor Order Workflow</span><span class="ico">🧭</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.inventory-track') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.inventory-track') ? 'active' : '' }}">
          <span class="text">Track Inventory</span><span class="ico">📦</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.products.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.products') ? 'active' : '' }}">
          <span class="text">Products</span><span class="ico">🛍️</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.transactions-view') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.transactions-view') ? 'active' : '' }}">
          <span class="text">View Transactions</span><span class="ico">💳</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.reports') }}" class="text-decoration-none">
        <button class="sidebar-btn {{ request()->routeIs('owner.reports') ? 'active' : '' }}">
          <span class="text">Reports</span><span class="ico">📊</span>
        </button>
      </a>
    </li>
  </ul>
</aside>

<section class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="welcome-text"><strong>Welcome, Owner!</strong></div>
    <div class="topbar-actions">
      
      <!-- Notifications Button with Dropdown -->
      <div class="notification-wrapper" style="position: relative;">
        <button type="button" class="icon-btn" aria-label="Notifications" onclick="toggleNotifications()" style="position: relative;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
            <path d="M10 21a2 2 0 0 0 4 0"/>
          </svg>
          @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="badge">{{ auth()->user()->unreadNotifications->count() }}</span>
          @endif
        </button>

        <!-- Dropdown -->
        <div id="notificationDropdown" class="notification-dropdown" 
             style="display: none; position: absolute; right: 0; top: 40px; background: #fff; border: 1px solid #ddd; border-radius: 8px; width: 320px; max-height: 400px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 100;">
          
          <h4 style="padding: 10px; border-bottom: 1px solid #ddd; margin:0;">Notifications</h4>
          <ul style="list-style: none; margin: 0; padding: 10px;">
            @forelse(auth()->user()->unreadNotifications as $notification)
              <li style="margin-bottom: 12px; font-size: 14px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px;">
                📩 {{ $notification->data['message'] }}<br>
                <small>Email: {{ $notification->data['email'] }}</small><br>
                <small>{{ $notification->created_at->diffForHumans() }}</small><br>

                <!-- Mark as Read -->
                <form action="{{ route('notifications.read', $notification->id) }}" method="POST" style="display:inline;">
                  @csrf
                  @method('PATCH')
                  <button type="submit" style="background:none; border:none; color:blue; cursor:pointer; font-size:12px;">
                    Mark as read
                  </button>
                </form>
              </li>
            @empty
              <li>No new notifications 🎉</li>
            @endforelse
          </ul>
        </div>
      </div>

      <!-- Logout Button -->
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>

  <!-- Main content goes here -->
  @yield('content')
</section>

<!-- JS for toggle -->
<script>
  function toggleNotifications() {
    let dropdown = document.getElementById("notificationDropdown");
    dropdown.style.display = dropdown.style.display === "none" ? "block" : "none";
  }

  // Close dropdown when clicking outside
  window.addEventListener("click", function(e) {
    let dropdown = document.getElementById("notificationDropdown");
    if (!e.target.closest(".notification-wrapper")) {
      dropdown.style.display = "none";
    }
  });
</script>
