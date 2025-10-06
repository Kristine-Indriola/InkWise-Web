<aside class="sidebar" id="sidebar" style="padding-top:32px;">
  <button class="collapse-btn" id="sidebarToggle" title="Toggle Sidebar" style="margin-left:auto; margin-right:0;">
    <i class="fi fi-rr-angle-double-right" id="sidebarToggleIcon"></i>
  </button>
  <img class="sidebar-logo" src="{{ asset('images/logo.png') }}" alt="InkWise logo">

  <ul class="navlist">
    <li>
      <a href="{{ route('owner.home') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.home') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 11 9-7 9 7" />
              <path d="M5 9v10a2 2 0 0 0 2 2h3v-6h4v6h3a2 2 0 0 0 2-2V9" />
            </svg>
          </span>
          <span class="text">Dashboard</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.staff.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.staff.index') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
              <path d="M6 21v-1a4 4 0 0 1 4-4h0" />
              <path d="m15 18 2 2 4-4" />
            </svg>
          </span>
          <span class="text">Staff Approval</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.order.workflow') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.order.workflow') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <path d="m12 6 4 6h-8l4 6" />
            </svg>
          </span>
          <span class="text">Order Workflow</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.inventory-track') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.inventory-track') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="4" width="16" height="6" rx="1" />
              <rect x="4" y="14" width="16" height="6" rx="1" />
              <path d="M10 8v8" />
              <path d="M14 8v8" />
            </svg>
          </span>
          <span class="text">Track Inventory</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.products.index') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.products.*') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M7 7V5a3 3 0 0 1 6 0v2" />
              <path d="M5 7h14l-1 12H6Z" />
            </svg>
          </span>
          <span class="text">Products</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.transactions-view') }}">
        <button class="sidebar-btn {{ request()->routeIs('owner.transactions-view') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="5" width="18" height="14" rx="2" />
              <path d="M3 10h18" />
              <path d="M7 15h2" />
            </svg>
          </span>
          <span class="text">View Transactions</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.reports') }}" class="text-decoration-none">
        <button class="sidebar-btn {{ request()->routeIs('owner.reports') ? 'active' : '' }}">
          <span class="ico">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 19V9" />
              <path d="M12 19V5" />
              <path d="M19 19v-7" />
            </svg>
          </span>
          <span class="text">Reports</span>
        </button>
      </a>
    </li>
  </ul>
</aside>

  @php
    $owner = auth()->user();
    $ownerName = $owner->name ?? 'Owner';
    $ownerInitials = collect(explode(' ', $ownerName))
      ->filter(fn ($segment) => strlen($segment) > 0)
      ->map(fn ($segment) => \Illuminate\Support\Str::substr($segment, 0, 1))
      ->join('');
    if ($ownerInitials === '') {
      $ownerInitials = \Illuminate\Support\Str::substr($ownerName, 0, 1);
    }
    $ownerInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($ownerInitials, 0, 2));
    $unreadNotifications = $owner?->unreadNotifications ?? collect();
    $unreadCount = $unreadNotifications->count();
    $ownerAvatarRelativePath = 'ownerimage/KRISTINE.png';
    $ownerAvatarUrl = file_exists(public_path($ownerAvatarRelativePath)) ? asset($ownerAvatarRelativePath) : null;
  @endphp


  <div class="topbar">
    <div class="logo">InkWise</div>
    <div class="icons" style="display: flex; align-items: center; gap: 24px; margin-left: auto; justify-content: center;">
      <!-- Notification Bell -->
      <a href="#" class="nav-link notif-btn" id="ownerNotificationToggle" style="display:flex; align-items:center; justify-content:center;">
        <i class="fi fi-ss-bell" style="font-size:22px;"></i>
        @if($unreadCount > 0)
          <span class="notif-badge">{{ $unreadCount }}</span>
        @endif
      </a>
      <div id="notificationDropdown" class="notification-dropdown" role="menu" aria-hidden="true" style="display:none; position:absolute; right:0; top:54px; background:#fff; border:1px solid #ddd; border-radius:8px; width:320px; max-height:400px; overflow-y:auto; box-shadow:0 4px 8px rgba(0,0,0,0.1); z-index:100;">
        <div class="notification-dropdown__header">Notifications</div>
        <ul class="notification-dropdown__list">
          @forelse($unreadNotifications as $notification)
            <li class="notification-dropdown__item">
              <div class="notification-dropdown__message">📩 {{ $notification->data['message'] }}</div>
              @if(!empty($notification->data['email']))
                <div class="notification-dropdown__meta">Email: {{ $notification->data['email'] }}</div>
              @endif
              <div class="notification-dropdown__meta">{{ $notification->created_at->diffForHumans() }}</div>
              <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="notification-dropdown__form">
                @csrf
                @method('PATCH')
                <button type="submit" class="notification-dropdown__action">Mark as read</button>
              </form>
            </li>
          @empty
            <li class="notification-dropdown__item notification-dropdown__item--empty">No new notifications 🎉</li>
          @endforelse
        </ul>
      </div>
      <!-- Theme Toggle -->
      <div id="theme-toggle-switch" class="theme-toggle-switch" title="Toggle dark/light mode" style="margin:0;">
        <span class="theme-toggle-label" id="theme-toggle-label">DAY</span>
        <span class="theme-toggle-knob" id="theme-toggle-knob">
          <span class="theme-toggle-icon" id="theme-toggle-icon">
            <i class="fi fi-rr-brightness"></i>
          </span>
        </span>
      </div>
      <!-- Profile Dropdown -->
      <div class="profile-dropdown" style="position: relative;">
        <a href="{{ route('owner.profile.show') }}" id="profileImageLink" style="display:flex; align-items:center; text-decoration:none; color:inherit;">
          @if($ownerAvatarUrl)
            <img src="{{ $ownerAvatarUrl }}" alt="Owner Profile" style="border-radius:50%; width:36px; height:36px; border:2px solid #6a2ebc; object-fit:cover;">
          @else
            <span class="profile-avatar profile-avatar--initials" style="border-radius:50%; width:36px; height:36px; background:linear-gradient(135deg,#6a2ebc,#3cd5c8); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px;">{{ $ownerInitials }}</span>
          @endif
        </a>
        <span id="profileDropdownToggle" style="cursor:pointer; display:inline-flex; align-items:center; margin-left:6px;">
          <i class="fi fi-rr-angle-small-down" style="font-size:18px;"></i>
        </span>
        <div id="profileDropdownMenu"
             style="display:none; position:absolute; right:0; top:48px; background:#fff; min-width:180px; box-shadow:0 8px 32px rgba(0,0,0,0.18); border-radius:14px; z-index:999; overflow:hidden; padding:8px 0; border:1px solid #eaeaea;">
          <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="width:100%; background:none; border:none; color:#f44336; font-size:16px; padding:14px 22px; text-align:left; cursor:pointer;">⏻ Log Out</button>
          </form>
        </div>
      </div>
    </div>
  </div>

<!-- content sections should be provided by the including view (e.g. each owner view opens <section class="main-content">) -->

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Admin-style notification dropdown
    const notifToggle = document.getElementById('ownerNotificationToggle');
    const notifDropdown = document.getElementById('notificationDropdown');
    if (notifToggle && notifDropdown) {
      notifToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = notifDropdown.style.display === 'block';
        notifDropdown.style.display = isOpen ? 'none' : 'block';
        notifDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
      });
      notifDropdown.addEventListener('click', function (e) { e.stopPropagation(); });
      document.addEventListener('click', function () { 
        notifDropdown.style.display = 'none';
        notifDropdown.setAttribute('aria-hidden','true'); 
      });
    }

    // Admin-style profile dropdown
    const profileToggle = document.getElementById('profileDropdownToggle');
    const profileMenu = document.getElementById('profileDropdownMenu');
    if (profileToggle && profileMenu) {
      profileToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = profileMenu.style.display === 'block';
        profileMenu.style.display = isOpen ? 'none' : 'block';
        profileMenu.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        profileToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      });
      profileMenu.addEventListener('click', function (e) { e.stopPropagation(); });
      document.addEventListener('click', function () { 
        profileMenu.style.display = 'none';
        profileMenu.setAttribute('aria-hidden','true'); 
        profileToggle.setAttribute('aria-expanded','false'); 
      });
    }
  });
</script>
<script>
  // Sidebar toggle logic for owner layout (mirror admin behavior)
  (function(){
    try {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
      if (!sidebar || !sidebarToggle) return;

      // Helper: set CSS vars for sidebar width and collapsed offset so headers can align precisely
      function updateSidebarVars() {
        const root = document.documentElement;
        const rect = sidebar.getBoundingClientRect();
        const width = Math.round(rect.width);
        root.style.setProperty('--sidebar-width', width + 'px');
        // collapsed offset is small, leave a little breathing room
        root.style.setProperty('--sidebar-collapsed-offset', '4px');
        // amount to subtract from the sidebar width so header sits slightly left
        if (!getComputedStyle(root).getPropertyValue('--sidebar-offset')) {
          root.style.setProperty('--sidebar-offset', '24px');
        }
      }

      // initialize variables on load
      updateSidebarVars();

      // Explicitly adjust the page header padding to avoid overlap (immediate/direct)
      function adjustHeaderPadding() {
        try {
          const header = document.querySelector('.page-header');
          if (!header) return;
          const root = document.documentElement;
          const sidebarWidth = getComputedStyle(root).getPropertyValue('--sidebar-width')?.trim() || '230px';
          const collapsedOffset = getComputedStyle(root).getPropertyValue('--sidebar-collapsed-offset')?.trim() || '4px';
          const offsetVal = getComputedStyle(root).getPropertyValue('--sidebar-offset')?.trim() || '24px';
          const sidebarPx = parseInt(sidebarWidth, 10) || 230;
          const offsetPx = parseInt(offsetVal, 10) || 24;
          const finalPadding = Math.max(0, sidebarPx - offsetPx) + 'px';
          if (document.body.classList.contains('sidebar-collapsed')) {
            header.style.paddingLeft = collapsedOffset;
          } else {
            header.style.paddingLeft = finalPadding;
          }
        } catch (e) { console.warn('adjustHeaderPadding error', e); }
      }

      // run once to align immediately
      adjustHeaderPadding();

      if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
        if (sidebarToggleIcon) sidebarToggleIcon.classList.add('is-rotated');
      }

      // Recompute when window resizes in case the sidebar width changes
      window.addEventListener('resize', updateSidebarVars);

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
        // update variables after a small timeout to allow CSS collapse transition to run
        setTimeout(function(){ updateSidebarVars(); adjustHeaderPadding(); }, 260);
      });
      // also adjust on resize so header follows any layout changes
      window.addEventListener('resize', function(){ updateSidebarVars(); adjustHeaderPadding(); });
    } catch (err) {
      console.error('Owner sidebar toggle error', err);
    }
  })();
</script>
