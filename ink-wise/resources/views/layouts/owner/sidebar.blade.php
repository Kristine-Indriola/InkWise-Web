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
      <button type="button" class="icon-btn" aria-label="Notifications">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 17H9a4 4 0 0 1-4-4V9a7 7 0 1 1 14 0v4a4 4 0 0 1-4 4z"/>
          <path d="M10 21a2 2 0 0 0 4 0"/>
        </svg>
        <span class="badge">2</span>
      </button>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>

  <!-- Main content goes here -->
  @yield('content')
</section>
