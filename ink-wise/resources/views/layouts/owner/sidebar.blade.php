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
          <span class="text">Dashboard</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 11 9-7 9 7" />
              <path d="M5 9v10a2 2 0 0 0 2 2h3v-6h4v6h3a2 2 0 0 0 2-2V9" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.staff.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.staff.index') ? 'active' : '' }}">
          <span class="text">Approve Staff Account</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
              <path d="M6 21v-1a4 4 0 0 1 4-4h0" />
              <path d="m15 18 2 2 4-4" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.order.workflow') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.order.workflow') ? 'active' : '' }}">
          <span class="text">Order Workflow</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <path d="m12 6 4 6h-8l4 6" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.inventory-track') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.inventory-track') ? 'active' : '' }}">
          <span class="text">Track Inventory</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="4" width="16" height="6" rx="1" />
              <rect x="4" y="14" width="16" height="6" rx="1" />
              <path d="M10 8v8" />
              <path d="M14 8v8" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.products.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.products') ? 'active' : '' }}">
          <span class="text">Products</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M7 7V5a3 3 0 0 1 6 0v2" />
              <path d="M5 7h14l-1 12H6Z" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.transactions-view') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.transactions-view') ? 'active' : '' }}">
          <span class="text">View Transactions</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="5" width="18" height="14" rx="2" />
              <path d="M3 10h18" />
              <path d="M7 15h2" />
            </svg>
          </span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.reports') }}" class="text-decoration-none">
        <button class="sidebar-btn {{ request()->routeIs('owner.reports') ? 'active' : '' }}">
          <span class="text">Reports</span>
          <span class="ico" style="color:#2563eb;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 19V9" />
              <path d="M12 19V5" />
              <path d="M19 19v-7" />
            </svg>
          </span>
        </button>
      </a>
    </li>
  </ul>
</aside>

<section class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="welcome-text"><strong>Welcome, Owner!</strong></div>
    </div>
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
