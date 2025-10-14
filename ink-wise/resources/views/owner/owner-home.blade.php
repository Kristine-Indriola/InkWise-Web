@extends('layouts.owner.app')

@section('content')

@include('layouts.owner.sidebar')

<!-- Use admin materials stylesheet to align layout and spacing with owner.products.index -->
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
<!-- keep flaticon icons (if used elsewhere) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">

<style>
  .welcome-message {
    background: rgba(16, 185, 129, 0.1); /* Transparent green background */
    color: #065f46; /* Dark green text */
    padding: 12px 16px;
    border-radius: 8px;
    margin: 16px 0;
    font-weight: 600;
    text-align: left; /* Align text to the left */
    opacity: 0;
    animation: fadeInOut 4s ease-in-out;
  }
  @keyframes fadeInOut {
    0% { opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { opacity: 0; }
  }

  /* Dark mode for welcome message */
  .dark-mode .welcome-message { background: rgba(16, 185, 129, 0.2); color: #a7f3d0; }

  /* Dark mode for summary cards */
  .dark-mode .summary-card { background:#374151; color:#f9fafb; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.4); }
  .dark-mode .summary-card::after { background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75)); }
  .dark-mode .summary-card .summary-card-label { color:#d1d5db; }
  .dark-mode .summary-card .summary-card-value { color:#f9fafb; }
  .dark-mode .summary-card .summary-card-meta { color:#9ca3af; }
  .dark-mode .summary-card-chip { background: rgba(148, 185, 255, 0.28); color: #bcd3ff; }

  /* Dark mode for body */
  .dark-mode body { background:#111827; }

  /* Owner dashboard layout balancing */
  .owner-dashboard-shell {
    padding-right: 0;
    padding-bottom: 0;
    padding-left: 0;
  }

  .owner-dashboard-main {
    max-width: var(--owner-content-shell-max, 1440px);
    margin: 0;
    padding: 0 28px 36px 12px;
    width: 100%;
  }

  .owner-dashboard-inner {
    max-width: var(--owner-content-shell-max, 1390px);
    margin: 0;
    width: 100%;
    padding: 0;
  }

  .owner-dashboard-inner .summary-grid {
    margin: 0;
    width: 100%;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
  }

  .owner-dashboard-inner .charts {
    margin: 32px 0 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }
  .summary-grid, .summary-card, .charts {
    /* placeholder for overrides if needed */
  }

  .summary-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px 24px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 14px;
    height: 3px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(148, 185, 255, 0.45), rgba(111, 150, 227, 0.55));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
  }

  .summary-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .summary-card-label { font-size: 0.88rem; color: #475569; }
  .summary-card-value { display: block; font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-top: 4px; }
  .summary-card-meta { color: #6b7280; font-size: 0.8rem; }

  .summary-card-chip {
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(148, 185, 255, 0.18);
    color: #5a8de0;
    font-weight: 600;
    font-size: 0.75rem;
  }
</style>

  <section class="main-content owner-dashboard-shell">
    <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
      <header class="page-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Overview and quick stats</p>
        </div>
      </header>

      @if(session('success'))
        <div id="welcome-message" class="welcome-message">
          {{ session('success') }}
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const welcomeMessage = document.getElementById('welcome-message');
            if (!welcomeMessage) return;

            const removeMessage = () => {
              if (welcomeMessage && welcomeMessage.parentNode) {
                welcomeMessage.parentNode.removeChild(welcomeMessage);
              }
            };

            welcomeMessage.addEventListener('animationend', removeMessage, { once: true });

            // Fallback in case animationEnd doesn't fire (e.g., reduced motion settings)
            setTimeout(removeMessage, 4500);
          });
        </script>
      @endif

  <div class="page-inner owner-dashboard-inner">
  <section class="summary-grid" aria-label="Dashboard summary">
      <div class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">New Orders</span>
          <span class="summary-card-chip">Orders</span>
        </div>
        <span class="summary-card-value">5</span>
        <span class="summary-card-meta">Recent new orders</span>
      </div>

      <a href="{{ route('owner.inventory-track', ['status' => 'low']) }}" class="summary-card" style="text-decoration:none; color:inherit;">
        <div class="summary-card-header">
          <span class="summary-card-label">Low Stock Materials</span>
          <span class="summary-card-chip">Inventory</span>
        </div>
        <span class="summary-card-value">{{ \App\Models\Material::whereHas('inventory', function($q) {
              $q->whereColumn('stock_level', '<=', 'reorder_level')
                ->where('stock_level', '>', 0);
          })->count() }}</span>
        <span class="summary-card-meta">Items approaching reorder level</span>
      </a>

      <div class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Pending Orders</span>
          <span class="summary-card-chip">Orders</span>
        </div>
        <span class="summary-card-value">8</span>
        <span class="summary-card-meta">Awaiting processing</span>
      </div>

      <div class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Revenue Growth</span>
          <span class="summary-card-chip">Finance</span>
        </div>
        <span class="summary-card-value" style="color:#0f172a;">{{ $revenueGrowth ?? '+12.4%' }}</span>
        <span class="summary-card-meta">vs last period</span>
      </div>
    </section>

  <!-- Charts Section -->
  <div class="charts">
    <!-- Top-Selling Products Chart -->
    <div class="chart-container">
      <h3>Top-Selling Products</h3>
      <canvas id="barChart"></canvas>
    </div>

    <!-- Inventory Movement Overview Chart -->
    <div class="chart-container">
      <h3>Inventory Movement Overview</h3>
      <canvas id="lineChart"></canvas>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const barCanvas = document.getElementById('barChart');
  if (barCanvas) {
    const barCtx = barCanvas.getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Invitation - Birthday Party','Keychain','Invitation - Floral Pink'],
        datasets: [{
          label: 'Units Sold',
          data: [12, 15, 20],
          backgroundColor: ['rgba(59,130,246,0.12)','rgba(59,130,246,0.18)','rgba(59,130,246,0.28)'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        layout: { padding: { bottom: 30 } },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#eef2f7' }
          },
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0,
              align: 'center',
              callback: function(value){
                const label = this.getLabelForValue(value);
                return label.length > 22 ? label.slice(0,22) + '…' : label;
              }
            }
          }
        }
      }
    });
  }
  const lineCanvas = document.getElementById('lineChart');
  if (lineCanvas) {
    const lineCtx = lineCanvas.getContext('2d');
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: ['Week 1','Week 2','Week 3','Week 4'],
        datasets: [
          { label: 'Incoming Stock', data: [20,40,25,35], borderColor: 'rgba(16,185,129,0.9)', fill:false, tension:.3 },
          { label: 'Outgoing Stock', data: [70,30,20,50], borderColor: 'rgba(14,165,233,0.9)', fill:false, tension:.3 }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }
});
</script>

<!-- Admin materials JS: floating add button, filter menu, and pagination behaviors -->
<script>
  // Floating add button behaviour (copied from admin materials)
  (function(){
    const addBtn = document.getElementById('addMaterialBtn');
    const floating = document.getElementById('floatingOptions');

    if (addBtn && floating) {
      const showFloating = () => {
        floating.style.display = 'block';
        floating.setAttribute('aria-hidden', 'false');
        addBtn.setAttribute('aria-expanded', 'true');
      };

      const hideFloating = () => {
        floating.style.display = 'none';
        floating.setAttribute('aria-hidden', 'true');
        addBtn.setAttribute('aria-expanded', 'false');
      };

      addBtn.addEventListener('mouseenter', showFloating);
      addBtn.addEventListener('mouseleave', () => {
        setTimeout(() => {
          if (!floating.matches(':hover')) {
            hideFloating();
          }
        }, 100);
      });

      floating.addEventListener('mouseenter', showFloating);
      floating.addEventListener('mouseleave', hideFloating);
    }
  })();
</script>

<script>
  // Filter icon menu behavior (copied from admin materials)
  (function(){
    const filterToggle = document.getElementById('filterToggle');
    const filterMenu = document.getElementById('filterMenu');
    const occasionInput = document.getElementById('occasionInput');
    const searchForm = filterToggle ? filterToggle.closest('form') : null;

    if (!filterToggle || !filterMenu) return;

    const openMenu = () => {
      filterMenu.style.display = 'block';
      filterMenu.setAttribute('aria-hidden', 'false');
      filterToggle.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = () => {
      filterMenu.style.display = 'none';
      filterMenu.setAttribute('aria-hidden', 'true');
      filterToggle.setAttribute('aria-expanded', 'false');
    };

    filterToggle.addEventListener('click', function(e){
      e.stopPropagation();
      const isOpen = filterMenu.style.display === 'block';
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    document.querySelectorAll('#filterMenu .filter-option-btn').forEach(btn => {
      btn.addEventListener('click', function(){
        const val = this.getAttribute('data-value');
        if (occasionInput) occasionInput.value = val;
        closeMenu();
        if (searchForm) searchForm.submit();
      });
    });

    // close when clicking outside
    document.addEventListener('click', function(e){
      if (!filterMenu.contains(e.target) && e.target !== filterToggle) {
        closeMenu();
      }
    });
  })();
</script>

<script>
  // Simple client-side pagination for tables (copied from admin materials)
  (function(){
    document.addEventListener('DOMContentLoaded', function () {
      const rows = Array.from(document.querySelectorAll('.materials-table-body tr[data-entry-index]'));
      const infoEl = document.querySelector('[data-entry-info]');
      const prevBtn = document.getElementById('entriesPrev');
      const nextBtn = document.getElementById('entriesNext');
      const totalEntries = rows.length;
      const pageSize = 10;
      let currentPage = 1;

      if (!rows.length) {
        if (infoEl) {
          infoEl.textContent = 'Showing 0 to 0 of 0 entries';
        }
        return;
      }

      const updateView = () => {
        const totalPages = Math.max(1, Math.ceil(totalEntries / pageSize));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);
        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalEntries);

        rows.forEach((row, idx) => {
          row.style.display = (idx >= startIndex && idx < endIndex) ? 'table-row' : 'none';
        });

        if (infoEl) {
          const entryLabel = (totalEntries === 1) ? 'entry' : 'entries';
          infoEl.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalEntries} ${entryLabel}`;
        }

        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
      };

      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          if (currentPage > 1) {
            currentPage--;
            updateView();
          }
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          const totalPages = Math.max(1, Math.ceil(totalEntries / pageSize));
          if (currentPage < totalPages) {
            currentPage++;
            updateView();
          }
        });
      }

      updateView();
    });
  })();
</script>
  </div> <!-- .page-inner -->
  </main>
  </section>
@endsection
