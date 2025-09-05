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
    <li>
      <a href="{{ route('owner.home') }}">
        <button class="sidebar-btn">
          <span class="text">Dashboard</span><span class="ico">🏠</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.staff.index') }}">
        <button class="sidebar-btn">
          <span class="text">Approve Staff Account</span><span class="ico">✅</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.order.workflow') }}">
        <button class="sidebar-btn">
          <span class="text">Monitor Order Workflow</span><span class="ico">🧭</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.inventory-track') }}">
        <button class="sidebar-btn">
          <span class="text">Track Inventory</span><span class="ico">📦</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.transactions-view') }}">
        <button class="sidebar-btn">
          <span class="text">View Transactions</span><span class="ico">💳</span>
        </button>
      </a>
    </li>
    <li>
      <a href="{{ route('owner.reports') }}" class="text-decoration-none">
        <button class="sidebar-btn">
          <span class="text">Reports</span><span class="ico">📊</span>
        </button>
      </a>
    </li>
  </ul>
</aside>