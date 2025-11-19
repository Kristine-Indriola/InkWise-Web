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
    padding: 16px 22px;
    border-radius: 8px;
    margin: 16px 0;
    font-weight: 600;
    text-align: left; /* Align text to the left */
    opacity: 0;
    animation: fadeInOut 4s ease-in-out;
    width: 100%;
    box-sizing: border-box;
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
  .dark-mode .summary-card-value__suffix { color: #d1d5db; }
  .dark-mode .summary-card-rating-star { color: #facc15; }
  .dark-mode .summary-card-rating-star--empty { color: #475569; }

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
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 20px;
  }

  .owner-dashboard-inner .charts {
    margin: 32px 0 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 12px;
  }

  .owner-dashboard-main .page-header > div {
    width: 100%;
  }
  .summary-grid, .summary-card, .charts {
    /* placeholder for overrides if needed */
  }

  .summary-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px 26px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .summary-card.summary-card--link {
    cursor: pointer;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 16px;
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

  .summary-card-heading {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .summary-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    flex-shrink: 0;
    background: rgba(59, 130, 246, 0.18);
    color: #2563eb;
    transition: color 0.18s ease, background 0.18s ease;
  }

  .summary-card-icon svg {
    width: 18px;
    height: 18px;
  }

  .summary-card-icon--orders { background: rgba(59, 130, 246, 0.18); color: #2563eb; }
  .summary-card-icon--inventory { background: rgba(16, 185, 129, 0.20); color: #0f766e; }
  .summary-card-icon--pending { background: rgba(245, 158, 11, 0.20); color: #b45309; }
  .summary-card-icon--rating { background: rgba(250, 204, 21, 0.20); color: #ca8a04; }
  .summary-card-icon--finance { background: rgba(129, 140, 248, 0.18); color: #4f46e5; }

  .dark-mode .summary-card-icon--orders { background: rgba(59, 130, 246, 0.32); color: #93c5fd; }
  .dark-mode .summary-card-icon--inventory { background: rgba(16, 185, 129, 0.32); color: #6ee7b7; }
  .dark-mode .summary-card-icon--pending { background: rgba(245, 158, 11, 0.32); color: #fbbf24; }
  .dark-mode .summary-card-icon--rating { background: rgba(250, 204, 21, 0.32); color: #facc15; }
  .dark-mode .summary-card-icon--finance { background: rgba(129, 140, 248, 0.34); color: #c7d2fe; }

  .summary-card-label { font-size: 0.92rem; color: #475569; }
  .summary-card-value { display: block; font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-top: 5px; }
  .summary-card-meta { color: #6b7280; font-size: 0.85rem; }

  .summary-card-chip {
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(148, 185, 255, 0.18);
    color: #5a8de0;
    font-weight: 600;
    font-size: 0.75rem;
  }

  .summary-card-value--rating {
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-size: 1.45rem;
  }

  .summary-card-rating-star {
    color: #f59e0b;
    font-size: 1.2rem;
    line-height: 1;
  }

  .summary-card-rating-star--empty {
    color: #cbd5f5;
  }

  .summary-card-value__suffix {
    font-size: 0.82rem;
    color: #6b7280;
    font-weight: 600;
  }

  .charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 18px;
  }

  .chart-container {
    position: relative;
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 24px 32px;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    min-height: 320px;
    display: flex;
    flex-direction: column;
  }

  .chart-container h3 {
    margin: 0 0 18px;
    font-size: 1.06rem;
    font-weight: 700;
    color: #0f172a;
  }

  .chart-container canvas {
    width: 100%;
    flex: 1;
  }

  .chart-empty-message {
    margin: auto;
    text-align: center;
    color: #6b7280;
    font-size: 0.94rem;
    padding: 12px;
  }

  .dark-mode .chart-container {
    background: #1f2937;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.48);
  }

  .dark-mode .chart-container h3 {
    color: #f9fafb;
  }

  .dark-mode .chart-empty-message {
    color: #cbd5f5;
  }
</style>

  <section class="main-content owner-dashboard-shell">
    <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
      <header class="page-header">
        <div>
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
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Overview and quick stats</p>
        </div>
      </header>

  <div class="page-inner owner-dashboard-inner">
  <section class="summary-grid" aria-label="Dashboard summary">
      <a href="{{ route('owner.order.workflow') }}" class="summary-card summary-card--link" style="text-decoration:none; color:inherit;">
        <div class="summary-card-header">
          <div class="summary-card-heading">
            <span class="summary-card-icon summary-card-icon--orders" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
            <span class="summary-card-label">New Orders Today</span>
          </div>
          <span class="summary-card-chip">Orders</span>
        </div>
        <span class="summary-card-value">{{ number_format($newOrdersCount ?? 0) }}</span>
        <span class="summary-card-meta">Placed in the last 7 days</span>
      </a>

      <a href="{{ route('owner.inventory-track', ['status' => 'low']) }}" class="summary-card summary-card--link" style="text-decoration:none; color:inherit;">
        <div class="summary-card-header">
          <div class="summary-card-heading">
            <span class="summary-card-icon summary-card-icon--inventory" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 7l9-4 9 4-9 4-9-4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M3 7v10l9 4 9-4V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12 11v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
            <span class="summary-card-label">Low Stock Materials</span>
          </div>
          <span class="summary-card-chip">Inventory</span>
        </div>
        <span class="summary-card-value">{{ number_format($lowStockCount ?? 0) }}</span>
        <span class="summary-card-meta">Items approaching reorder level</span>
      </a>

      <a href="{{ route('owner.order.workflow', ['status' => 'pending']) }}" class="summary-card summary-card--link" style="text-decoration:none; color:inherit;">
        <div class="summary-card-header">
          <div class="summary-card-heading">
            <span class="summary-card-icon summary-card-icon--pending" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
            <span class="summary-card-label">Pending Orders</span>
          </div>
          <span class="summary-card-chip">Orders</span>
        </div>
        <span class="summary-card-value">{{ number_format($pendingOrdersCount ?? 0) }}</span>
        <span class="summary-card-meta">Awaiting processing</span>
      </a>

      <a href="{{ route('owner.ratings.index') }}" class="summary-card summary-card--link" style="text-decoration:none; color:inherit;">
        <div class="summary-card-header">
          <div class="summary-card-heading">
            <span class="summary-card-icon summary-card-icon--rating" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 4.8l2.12 4.3 4.75.69-3.44 3.36.81 4.73L12 15.9l-4.24 2.28.81-4.73-3.44-3.36 4.75-.69L12 4.8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
              </svg>
            </span>
            <span class="summary-card-label">Average Rating</span>
          </div>
          <span class="summary-card-chip">Feedback</span>
        </div>
        <span class="summary-card-value summary-card-value--rating">
          <span>{{ $averageRating !== null ? number_format($averageRating, 2) : '—' }}</span>
          @if($averageRating !== null)
            <span class="summary-card-rating-star" aria-hidden="true">★</span>
          @else
            <span class="summary-card-rating-star summary-card-rating-star--empty" aria-hidden="true">☆</span>
          @endif
          <span class="summary-card-value__suffix">/ 5</span>
        </span>
        <span class="summary-card-meta">
          @if(($totalRatings ?? 0) === 1)
            1 review recorded
          @else
            {{ number_format($totalRatings ?? 0) }} reviews recorded
          @endif
        </span>
      </a>

      <div class="summary-card">
        <div class="summary-card-header">
          <div class="summary-card-heading">
            <span class="summary-card-icon summary-card-icon--finance" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M7 13l4-4 3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M15 7h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
            <span class="summary-card-label">Revenue Growth</span>
          </div>
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
      <p id="barChartEmpty" class="chart-empty-message" hidden>No product sales recorded yet.</p>
    </div>

    <!-- Inventory Movement Overview Chart -->
    <div class="chart-container">
      <h3>Inventory Movement Overview</h3>
      <canvas id="lineChart"></canvas>
      <p id="lineChartEmpty" class="chart-empty-message" hidden>No inventory movements recorded yet.</p>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const topSellingProducts = @json($topSellingProducts ?? ['labels' => [], 'data' => []]);
  const inventoryMovement = @json($inventoryMovement ?? ['labels' => [], 'incoming' => [], 'outgoing' => []]);
  const barCanvas = document.getElementById('barChart');
  const barEmpty = document.getElementById('barChartEmpty');
  if (barCanvas) {
    const hasData = Array.isArray(topSellingProducts.data) && topSellingProducts.data.length > 0;
    if (!hasData) {
      barCanvas.style.display = 'none';
      if (barEmpty) {
        barEmpty.hidden = false;
      }
    } else {
      if (barEmpty) {
        barEmpty.hidden = true;
      }
      barCanvas.style.display = '';
      const barCtx = barCanvas.getContext('2d');
      const palette = [
        'rgba(59,130,246,0.28)',
        'rgba(59,130,246,0.24)',
        'rgba(59,130,246,0.20)',
        'rgba(59,130,246,0.16)',
        'rgba(59,130,246,0.12)'
      ];
      const backgroundColor = topSellingProducts.data.map((_, index) => palette[index % palette.length]);
      const maxValue = Math.max(...topSellingProducts.data);
      new Chart(barCtx, {
        type: 'bar',
        data: {
          labels: topSellingProducts.labels,
          datasets: [{
            label: 'Units Sold',
            data: topSellingProducts.data,
            backgroundColor,
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
              suggestedMax: maxValue < 5 ? 5 : maxValue + Math.ceil(maxValue * 0.1),
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
                  if (!label) {
                    return '';
                  }
                  return label.length > 22 ? label.slice(0,22) + '...' : label;
                }
              }
            }
          }
        }
      });
    }
  }
  const lineCanvas = document.getElementById('lineChart');
  const lineEmpty = document.getElementById('lineChartEmpty');
  if (lineCanvas) {
    const labels = Array.isArray(inventoryMovement.labels) ? inventoryMovement.labels : [];
    const incomingSeries = Array.isArray(inventoryMovement.incoming) ? inventoryMovement.incoming.map(Number) : [];
    const outgoingSeries = Array.isArray(inventoryMovement.outgoing) ? inventoryMovement.outgoing.map(Number) : [];

    const hasMovementData = labels.length > 0 && (
      incomingSeries.some(value => value > 0) || outgoingSeries.some(value => value > 0)
    );

    if (!hasMovementData) {
      lineCanvas.style.display = 'none';
      if (lineEmpty) {
        lineEmpty.hidden = false;
      }
    } else {
      if (lineEmpty) {
        lineEmpty.hidden = true;
      }
      lineCanvas.style.display = '';

      const lineCtx = lineCanvas.getContext('2d');
      const combinedValues = [...incomingSeries, ...outgoingSeries];
      const maxValue = combinedValues.length ? Math.max(...combinedValues) : 0;

      new Chart(lineCtx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Incoming Stock',
              data: incomingSeries,
              borderColor: 'rgba(16,185,129,0.85)',
              backgroundColor: 'rgba(16,185,129,0.18)',
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 5
            },
            {
              label: 'Outgoing Stock',
              data: outgoingSeries,
              borderColor: 'rgba(14,165,233,0.85)',
              backgroundColor: 'rgba(14,165,233,0.18)',
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 5
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const parsed = context.parsed || {};
                  const value = typeof parsed.y === 'number' ? parsed.y : 0;
                  return `${context.dataset.label}: ${value}`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              suggestedMax: maxValue < 10 ? 10 : maxValue + Math.ceil(maxValue * 0.1),
              grid: { color: '#eef2f7' }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }
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
