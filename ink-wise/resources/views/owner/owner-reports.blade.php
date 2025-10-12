@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Page-scoped styles aligned with owner dashboard layout -->
<style>
  .owner-dashboard-shell {
    padding: 20px 24px 32px;
    padding-left: clamp(24px, 3vw, 48px);
  }

  .owner-dashboard-main {
    max-width: 1440px;
    margin: 0 auto;
    padding: 28px 28px 36px;
    width: 100%;
  }

  .owner-dashboard-inner {
    max-width: 1390px;
    margin: 0 auto;
    width: 100%;
    padding: 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }

  .page-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px;
  }

  .page-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 0.98rem;
  }

  .summary-grid {
    margin: 0 0 20px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
  }

  .summary-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px 24px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
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
    margin-bottom: 10px;
  }

  .summary-card-label {
    font-size: 0.92rem;
    font-weight: 600;
    color: #475569;
  }

  .summary-card-value {
    display: block;
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    margin-top: 6px;
  }

  .summary-card-meta {
    color: #6b7280;
    font-size: 0.84rem;
  }

  .summary-card-chip {
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(148, 185, 255, 0.18);
    color: #5a8de0;
    font-weight: 600;
    font-size: 0.78rem;
  }

  .summary-card-chip.accent {
    background: rgba(148, 185, 255, 0.22);
  }

  .reports-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 18px;
    margin-bottom: 20px;
  }

  .chart-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 24px 24px;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    min-height: 240px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .chart-card h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
  }

  .chart-card canvas {
    width: 100%;
    flex: 1;
  }

  .materials-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
  }

  .materials-toolbar__search {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .materials-toolbar__search label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
  }

  .materials-toolbar__search select {
    min-width: 160px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid rgba(148, 185, 255, 0.28);
    background: #fff;
    font-size: 0.92rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .materials-toolbar__search select:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.32);
    box-shadow: 0 12px 28px rgba(59, 130, 246, 0.08);
  }

  .materials-toolbar__actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
  }

  .owner-dashboard-inner .table-wrapper {
    margin-top: 18px;
    border-radius: 14px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    background: #f8fbff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 16px 32px rgba(15, 23, 42, 0.08);
    overflow-x: auto;
  }

  .owner-dashboard-inner .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
    color: #0f172a;
    min-width: 880px;
  }

  .owner-dashboard-inner .table thead th {
    background: rgba(148, 185, 255, 0.16);
    padding: 14px 20px;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    font-weight: 700;
  }

  .owner-dashboard-inner .table tbody td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(148, 185, 255, 0.12);
    vertical-align: middle;
  }

  .owner-dashboard-inner .table tbody tr:last-child td {
    border-bottom: none;
  }

  .owner-dashboard-inner .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.08);
  }

  .table-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
  }

  .table-section-header h3 {
    margin: 0;
    font-size: 1.12rem;
    font-weight: 700;
    color: #0f172a;
  }

  .table-pagination {
    margin-top: 16px;
  }

  @media (max-width: 900px) {
    .owner-dashboard-shell { padding: 16px; }
    .owner-dashboard-main { padding: 24px 20px 32px; }
    .owner-dashboard-inner { padding: 0 4px; }
    .owner-dashboard-inner .table { min-width: 720px; }
    .reports-charts { grid-template-columns: 1fr; }
    .materials-toolbar { flex-direction: column; align-items: stretch; }
    .materials-toolbar__search { width: 100%; justify-content: space-between; }
    .materials-toolbar__actions { width: 100%; justify-content: flex-start; }
  }

  .dark-mode body { background: #111827; }
  .dark-mode .summary-card { background: #374151; color: #f9fafb; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.4); }
  .dark-mode .summary-card::after { background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75)); }
  .dark-mode .summary-card-label { color: #d1d5db; }
  .dark-mode .summary-card-value { color: #f9fafb; }
  .dark-mode .summary-card-meta { color: #9ca3af; }
  .dark-mode .summary-card-chip { background: rgba(148, 185, 255, 0.28); color: #cbd9ff; }
  .dark-mode .summary-card-chip.accent { background: rgba(148, 185, 255, 0.32); }
  .dark-mode .chart-card { background: #1f2937; box-shadow: 0 16px 32px rgba(15, 23, 42, 0.5); }
  .dark-mode .chart-card h4 { color: #f9fafb; }
  .dark-mode .materials-toolbar__search select { background: #374151; border-color: #4b5563; color: #f9fafb; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35); }
  .dark-mode .owner-dashboard-inner .table-wrapper { background: #1f2937; border-color: rgba(148, 185, 255, 0.32); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04); }
  .dark-mode .owner-dashboard-inner .table { color: #f9fafb; }
  .dark-mode .owner-dashboard-inner .table thead th { background: rgba(148, 185, 255, 0.22); color: #0f172a; }
  .dark-mode .owner-dashboard-inner .table tbody td { border-color: rgba(148, 185, 255, 0.18); }
  .dark-mode .owner-dashboard-inner .table tbody tr:hover { background: rgba(148, 185, 255, 0.12); }
</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Exportable reports &amp; analytics</p>
      </div>
    </header>

    <div class="page-inner owner-dashboard-inner">
    <!-- Summary cards (placeholders; keep numbers as examples) -->
    <section class="summary-grid" aria-label="Reports summary">
      <a href="{{ url()->current() }}" class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Total Sales</span>
          <span class="summary-card-chip accent">Revenue</span>
        </div>
        <span class="summary-card-value">₱120,500</span>
        <span class="summary-card-meta">This period</span>
      </a>

      <a href="{{ url()->current() }}" class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Total Inventory</span>
          <span class="summary-card-chip accent">Items</span>
        </div>
        <span class="summary-card-value">850</span>
        <span class="summary-card-meta">Materials tracked</span>
      </a>

      <a href="{{ url()->current() }}" class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Orders</span>
          <span class="summary-card-chip accent">Count</span>
        </div>
        <span class="summary-card-value">320</span>
        <span class="summary-card-meta">Placed</span>
      </a>

      <a href="{{ url()->current() }}" class="summary-card">
        <div class="summary-card-header">
          <span class="summary-card-label">Low Stock Alerts</span>
          <span class="summary-card-chip accent">Alert</span>
        </div>
        <span class="summary-card-value">12</span>
        <span class="summary-card-meta">Needs reorder</span>
      </a>
    </section>

    <section class="materials-toolbar" aria-label="Report filters and actions">
      <div class="materials-toolbar__search">
        <label for="reportFilterSelect">Range</label>
        <select id="reportFilterSelect" name="range" aria-label="Filter reports by range">
          <option value="all">All time</option>
          <option value="daily">Today</option>
          <option value="weekly">Last 7 days</option>
          <option value="monthly">Last 30 days</option>
          <option value="yearly">This year</option>
        </select>
      </div>
      <div class="materials-toolbar__actions">
        <button type="button" class="btn btn-primary" id="openGenerateModalBtn" title="Generate new report">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span style="margin-left:8px;">Generate</span>
        </button>
        <button type="button" class="btn btn-secondary" onclick="exportCSV()" title="Export visible rows to CSV">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M8 11l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span style="margin-left:8px;">Export CSV</span>
        </button>
        <button type="button" class="btn btn-outline" onclick="exportPDF()" title="Export visible rows to PDF">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h9l5 5v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 3v6h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span style="margin-left:8px;">Export PDF</span>
        </button>
      </div>
    </section>

    <!-- Charts -->
    <div class="reports-charts">
      <div class="chart-card">
        <h4>Sales Overview</h4>
        <canvas id="salesChart"></canvas>
      </div>
      <div class="chart-card">
        <h4>Inventory Levels</h4>
        <canvas id="inventoryChart"></canvas>
      </div>
    </div>

  <!-- Generate Report Modal (reworked professional layout) -->
    <div id="generateModal" class="generate-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="generateModalTitle">
      <div class="generate-modal-dialog" role="document">
        <header class="generate-modal-header">
          <h2 id="generateModalTitle">Generate Report</h2>
          <button id="generateModalClose" class="generate-modal-close" aria-label="Close dialog">✕</button>
        </header>

        <div class="generate-modal-body">
          <form id="generateForm" class="generate-form" onsubmit="return false;">
            <div class="form-row">
              <label for="genReportType" class="form-label">Report Type</label>
              <select id="genReportType" name="reportType" class="form-control">
                <option value="all">All Reports</option>
                <option value="Sales Report">Sales Report</option>
                <option value="Inventory Report">Inventory Report</option>
              </select>
            </div>

            <fieldset class="form-row date-range-fieldset">
              <legend class="form-label">Date Range</legend>
              <div class="radio-row">
                <label><input type="radio" name="dateRange" value="daily"> Today</label>
                <label><input type="radio" name="dateRange" value="weekly"> Last 7 Days</label>
                <label><input type="radio" name="dateRange" value="monthly" checked> Monthly</label>
                <label><input type="radio" name="dateRange" value="yearly"> Yearly</label>
              </div>
            </fieldset>

            <div class="form-row">
              <label for="genDescription" class="form-label">Description</label>
              <textarea id="genDescription" name="description" rows="4" class="form-control" placeholder="Description for this generated report"></textarea>
            </div>

            <footer class="generate-modal-footer">
              <button type="button" id="genCancel" class="btn btn-secondary">Cancel</button>
              <button type="button" id="genSubmit" class="btn btn-primary">Generate</button>
            </footer>
          </form>
        </div>
      </div>
    </div>

    <div class="table-section-header">
      <h3>Reports</h3>
    </div>
    <div class="table-wrapper">
      <table class="table" id="reportTable">
        <thead>
          <tr>
            <th>Report Name</th>
            <th>Description</th>
            <th>Generated By</th>
            <th>Date Generated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</main>
</section>

<!-- jsPDF + AutoTable (kept here) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  try {
    // Make Chart.js render crisply using device pixel ratio and a readable font
    if (window.devicePixelRatio) {
      Chart.defaults.devicePixelRatio = window.devicePixelRatio;
    }
    Chart.defaults.color = '#0f172a'; // dark text color for ticks/labels
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
    Chart.defaults.font.size = 12;

    // Sales (line) chart
    const salesCanvas = document.getElementById('salesChart');
    if (salesCanvas) {
      const salesCtx = salesCanvas.getContext('2d');
      new Chart(salesCtx, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
          datasets: [{
            label: 'Sales',
            data: [12000, 15000, 13000, 18000, 20000],
            borderColor: '#2b6cb0',
            borderWidth: 2.2,
            pointRadius: 4,
            pointBackgroundColor: '#2b6cb0',
            backgroundColor: 'rgba(43,108,176,0.06)',
            tension: 0.25,
            fill: true,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              titleFont: { size: 13, weight: '600' },
              bodyFont: { size: 13 },
              padding: 8
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: '#eef2f7' },
              ticks: {
                color: '#0f172a',
                padding: 8,
                font: { size: 13, weight: '600' }
              }
            },
            x: {
              grid: { display: false },
              ticks: {
                color: '#0f172a',
                padding: 6,
                font: { size: 12, weight: '600' },
                maxRotation: 0,
                autoSkip: true
              }
            }
          }
        }
      });
    }

    // Inventory (bar) chart
    const inventoryCanvas = document.getElementById('inventoryChart');
    if (inventoryCanvas) {
      const inventoryCtx = inventoryCanvas.getContext('2d');
      new Chart(inventoryCtx, {
        type: 'bar',
        data: {
          labels: ['Paper', 'Ink', 'Covers', 'Packaging'],
          datasets: [{
            label: 'Stock Levels',
            data: [300, 120, 80, 200],
            backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#2196f3'],
            borderColor: ['rgba(36,124,43,0.95)', 'rgba(255,152,0,0.95)', 'rgba(244,67,54,0.95)', 'rgba(33,150,243,0.95)'],
            borderWidth: 1.2,
            hoverBorderColor: '#0b1220'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              titleFont: { size: 13, weight: '600' },
              bodyFont: { size: 13 },
              padding: 8
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                color: '#0f172a',
                padding: 8,
                font: { size: 13, weight: '600' }
              },
              grid: {
                color: '#eef2f7'
              }
            },
            x: {
              ticks: {
                color: '#0f172a',
                font: { size: 12, weight: '600' },
                padding: 6,
                maxRotation: 0,
                autoSkip: true
              },
              grid: { display: false }
            }
          }
        }
      });
    }
  } catch (err) {
    console.error('Chart initialization failed:', err);
  }

  // Helper: parse date string "YYYY-MM-DD" to a Date at local midnight
  function parseYMD(s) {
    if (!s) return null;
    // Prefer strict YYYY-MM-DD parsing to avoid timezone shifts.
    const m = String(s).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
      const y = parseInt(m[1], 10);
      const mm = parseInt(m[2], 10) - 1;
      const d = parseInt(m[3], 10);
      return new Date(y, mm, d); // local midnight
    }
    // Fallback to Date parsing for other ISO-like strings
    const iso = String(s).trim();
    const parsed = new Date(iso);
    return isNaN(parsed.getTime()) ? null : parsed;
  }

  function applyReportFilter(range) {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const today = new Date();
    const todayZero = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach((tr) => {
      // try data-date first (YYYY-MM-DD), fallback to the cell text
      const dateAttr = tr.dataset && tr.dataset.date ? tr.dataset.date : null;
      const dateCell = tr.cells[3];
      const dateText = dateAttr || (dateCell ? (dateCell.innerText || dateCell.textContent || '').trim() : '');
      if (!dateText) {
        tr.style.display = '';
        return;
      }

      const d = parseYMD(dateText);
      if (!d) {
        tr.style.display = '';
        return;
      }

      // compute difference in days (positive = days in past)
      const diffMs = todayZero - new Date(d.getFullYear(), d.getMonth(), d.getDate());
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24)); // 0 = today

      let show = true;
      switch (range) {
        case 'daily':
          show = (diffDays === 0);
          break;
        case 'weekly': // last 7 days including today
          show = (diffDays >= 0 && diffDays <= 6);
          break;
        case 'monthly': // last 30 days
          show = (diffDays >= 0 && diffDays <= 30);
          break;
        case 'yearly': // last 365 days
          show = (diffDays >= 0 && diffDays <= 365);
          break;
        case 'all':
        default:
          show = true;
      }
      tr.style.display = show ? '' : 'none';
    });

    // sort newest-first after filtering
    sortReportsTable(true);
  }

  // helper to read the currently selected range from the new dropdown
  function getActiveRange() {
    const sel = document.getElementById('reportFilterSelect');
    if (!sel) return (window.currentReportFilter || 'all');
    return sel.value || (window.currentReportFilter || 'all');
  }

  // Wire up the dropdown filter (replaces previous tab logic)
  (function initReportFilters() {
    const sel = document.getElementById('reportFilterSelect');
    if (!sel) return;

    // store default
    window.currentReportFilter = sel.value || 'all';

    function onChange() {
      window.currentReportFilter = sel.value || 'all';
      try {
        applyReportFilter(window.currentReportFilter);
      } catch (e) {
        console.error('applyReportFilter failed:', e);
      }
    }

    sel.addEventListener('change', onChange);

    // initialize
    try { applyReportFilter(window.currentReportFilter); } catch (e) { /* ignore init errors */ }
  })();

  // Add inside DOMContentLoaded handler (after initReportFilters or before export functions)

  (function wireGenerateModal() {
    const openBtn = document.getElementById('openGenerateModalBtn');
    const modal = document.getElementById('generateModal');
    const closeBtn = document.getElementById('generateModalClose');
    const cancelBtn = document.getElementById('genCancel');
    const submitBtn = document.getElementById('genSubmit');
    const form = document.getElementById('generateForm');

    if (!openBtn || !modal || !closeBtn || !submitBtn) return;

    let lastFocused = null;
    let focusableElements = [];
    let firstFocusable = null;
    let lastFocusable = null;
    let trapHandler = null;

    function updateFocusable() {
      focusableElements = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
      firstFocusable = focusableElements[0];
      lastFocusable = focusableElements[focusableElements.length - 1];
    }

    function openModal() {
      lastFocused = document.activeElement;
      modal.setAttribute('aria-hidden', 'false');
      // update focusable and focus first control
      updateFocusable();
      if (firstFocusable) firstFocusable.focus();

      // trap Tab inside modal
      trapHandler = function (e) {
        if (e.key !== 'Tab') return;
        // forward tab
        if (e.shiftKey) {
          if (document.activeElement === firstFocusable) {
            e.preventDefault();
            lastFocusable && lastFocusable.focus();
          }
        } else {
          if (document.activeElement === lastFocusable) {
            e.preventDefault();
            firstFocusable && firstFocusable.focus();
          }
        }
      };
      document.addEventListener('keydown', trapHandler);
      // prevent page scroll behind modal on mobile
      document.documentElement.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.setAttribute('aria-hidden', 'true');
      document.removeEventListener('keydown', trapHandler);
      trapHandler = null;
      // restore page scroll
      document.documentElement.style.overflow = '';
      // restore focus
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    openBtn.addEventListener('click', function () {
      openModal();
    });
    closeBtn.addEventListener('click', closeModal);
    cancelBtn && cancelBtn.addEventListener('click', closeModal);

    // close on backdrop click
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });

    // close on Escape (works in addition to trap)
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
    });

    // helper to filter rows by range + optional reportName
    function filterRowsForExport(range, reportName) {
      const table = document.getElementById('reportTable');
      if (!table) return;
      const todayZero = new Date();
      const tzToday = new Date(todayZero.getFullYear(), todayZero.getMonth(), todayZero.getDate());
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach(tr => {
        const nameCell = (tr.cells && tr.cells[0]) ? tr.cells[0].textContent.trim() : '';
        const dateCell = (tr.cells && tr.cells[3]) ? tr.cells[3].textContent.trim() : '';
        let dateMatch = true;
        if (dateCell) {
          // use the global parseYMD to avoid duplicated logic and timezone issues
          const d = parseYMD(dateCell);
          if (d) {
            const diffMs = tzToday - new Date(d.getFullYear(), d.getMonth(), d.getDate());
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            switch (range) {
              case 'daily': dateMatch = (diffDays === 0); break;
              case 'weekly': dateMatch = (diffDays >= 0 && diffDays <= 6); break;
              case 'monthly':
              case 'last30': dateMatch = (diffDays >= 0 && diffDays <= 30); break;
              case 'yearly': dateMatch = (diffDays >= 0 && diffDays <= 365); break;
              default: dateMatch = true;
            }
          }
        }

        const nameMatch = !reportName || reportName === 'all' || (nameCell === reportName);
        tr.style.display = (dateMatch && nameMatch) ? '' : 'none';
      });
    }

    submitBtn.addEventListener('click', function () {
      // read form values
      const reportType = document.getElementById('genReportType').value;
      const dateRangeInput = form.querySelector('input[name="dateRange"]:checked');
      const dateRange = dateRangeInput ? dateRangeInput.value : 'monthly';
      const description = (document.getElementById('genDescription') || { value: '' }).value.trim();

      // determine "generated by" (try to read user name from DOM, fallback to 'Owner')
      const generatedBy = (document.querySelector('.user-display-name') && document.querySelector('.user-display-name').textContent.trim()) || window.currentUserName || 'Owner';

      // date string for table (YYYY-MM-DD)
      const now = new Date();
      const dateStr = now.toISOString().slice(0,10);

      // helper to add a row to the reports table
      function addReportRow(name, desc, by, dateIso) {
        const table = document.getElementById('reportTable');
        if (!table) return;
        const tbody = table.querySelector('tbody') || table.appendChild(document.createElement('tbody'));

        const tr = document.createElement('tr');

        // Report Name
        const tdName = document.createElement('td');
        tdName.textContent = name || '';
        tr.appendChild(tdName);

        // Description
        const tdDesc = document.createElement('td');
        tdDesc.textContent = desc || '';
        tr.appendChild(tdDesc);

        // Generated By
        const tdBy = document.createElement('td');
        tdBy.textContent = by || '';
        tr.appendChild(tdBy);

        // Date Generated
        const tdDate = document.createElement('td');
        tdDate.textContent = dateIso || '';
        tr.appendChild(tdDate);

        // Actions (View / Download)
        const tdAction = document.createElement('td');

        const viewBtn = document.createElement('button');
        viewBtn.className = 'btn-view';
        viewBtn.type = 'button';
        viewBtn.textContent = 'View';
        viewBtn.addEventListener('click', function () {
          tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
          tr.style.transition = 'background-color 300ms ease';
          const prevBg = tr.style.backgroundColor;
          tr.style.backgroundColor = 'rgba(37,99,235,0.06)';
          setTimeout(() => tr.style.backgroundColor = prevBg || '', 900);
        });

        // Download button with contextual menu
        const dlBtn = document.createElement('button');
        dlBtn.className = 'btn-download';
        dlBtn.type = 'button';
        dlBtn.textContent = 'Download';

        // helper: export a single table row as CSV
        function exportSingleRowCSV(row) {
          try {
            const cells = row.querySelectorAll('td');
            const values = Array.from(cells).map((td, idx) => {
              if (idx === cells.length - 1) {
                const btns = Array.from(td.querySelectorAll('button')).map(b => b.innerText.trim()).filter(Boolean);
                return '"' + btns.join(' | ').replace(/"/g, '""') + '"';
              }
              const txt = (td.innerText || '').replace(/\r?\n|\r/g, ' ').replace(/"/g, '""').trim();
              return '"' + txt + '"';
            });

            const csv = values.join(',');
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = "report-row-" + Date.now() + ".csv";
            document.body.appendChild(link);
            link.click();
            link.remove();
          } catch (err) {
            console.error('Single-row CSV export failed:', err);
            alert('CSV export failed for this row.');
          }
        }

        // helper: export a single table row as PDF using jsPDF + autotable if available
        function exportSingleRowPDF(row) {
          try {
            if (!window.jspdf) {
              alert('PDF export not available (jspdf not loaded).');
              return;
            }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Title
            doc.setFontSize(14);
            doc.text("InkWise Report", 14, 15);

            // Description (cell 1) under title
            const descriptionText = (row.cells && row.cells[1]) ? (row.cells[1].innerText || '').trim() : '';
            let startY = 22;
            if (descriptionText) {
              doc.setFontSize(11);
              const split = doc.splitTextToSize(descriptionText, 180);
              doc.text(split, 14, startY);
              startY += (split.length * 6) + 6;
            }

            // table header from page header
            const head = [];
            const thead = document.querySelectorAll('#reportTable thead th');
            thead.forEach(th => head.push(th.innerText.trim()));

            // single body row
            const cells = row.querySelectorAll('td');
            const rowData = [];
            cells.forEach((td, idx) => {
              if (idx === cells.length - 1) {
                const btns = Array.from(td.querySelectorAll('button')).map(b => b.innerText.trim()).filter(Boolean);
                rowData.push(btns.join(' '));
              } else {
                rowData.push((td.innerText || '').trim());
              }
            });

            const body = [rowData];

            if (doc.autoTable) {
              doc.autoTable({
                head: [head],
                body: body,
                startY: startY,
                theme: 'grid',
                headStyles: { fillColor: [232, 241, 250] }
              });
            } else {
              // fallback: simple text
              let y = startY;
              rowData.forEach(r => {
                doc.setFontSize(11);
                doc.text(String(r), 14, y);
                y += 8;
              });
            }

            doc.save("report-row-" + Date.now() + ".pdf");
          } catch (err) {
            console.error('Single-row PDF export failed:', err);
            alert('PDF export failed for this row.');
          }
        }

        // show a small contextual menu next to the download button offering CSV / PDF
        function showDownloadMenu(button, row) {
          // remove any existing menu
          const existing = document.getElementById('singleRowDownloadMenu');
          if (existing) existing.remove();

          // build menu
          const menu = document.createElement('div');
          menu.id = 'singleRowDownloadMenu';
          menu.setAttribute('role', 'menu');
          menu.style.position = 'absolute';
          menu.style.minWidth = '150px';
          menu.style.background = '#fff';
          menu.style.border = '1px solid rgba(2,6,23,0.08)';
          menu.style.boxShadow = '0 8px 20px rgba(2,6,23,0.08)';
          menu.style.borderRadius = '8px';
          menu.style.padding = '6px';
          menu.style.zIndex = 1600;
          menu.style.fontWeight = 600;

          // place menu near button
          const rect = button.getBoundingClientRect();
          // calculate position taking scroll into account
          const top = rect.bottom + window.scrollY + 8;
          let left = rect.left + window.scrollX;
          // ensure menu doesn't overflow right edge
          const maxLeft = (window.scrollX + document.documentElement.clientWidth) - 170;
          if (left > maxLeft) left = maxLeft;
          menu.style.top = top + 'px';
          menu.style.left = left + 'px';

          // menu items
          const itemCSV = document.createElement('button');
          itemCSV.type = 'button';
          itemCSV.className = 'generate-item';
          itemCSV.textContent = 'Download CSV';
          itemCSV.style.display = 'block';
          itemCSV.style.width = '100%';
          itemCSV.style.padding = '8px 10px';
          itemCSV.style.border = 'none';
          itemCSV.style.background = 'transparent';
          itemCSV.style.textAlign = 'left';
          itemCSV.addEventListener('click', function (e) {
            e.stopPropagation();
            exportSingleRowCSV(row);
            menu.remove();
            cleanup();
          });

          const itemPDF = document.createElement('button');
          itemPDF.type = 'button';
          itemPDF.className = 'generate-item';
          itemPDF.textContent = 'Download PDF';
          itemPDF.style.display = 'block';
          itemPDF.style.width = '100%';
          itemPDF.style.padding = '8px 10px';
          itemPDF.style.border = 'none';
          itemPDF.style.background = 'transparent';
          itemPDF.style.textAlign = 'left';
          itemPDF.addEventListener('click', function (e) {
            e.stopPropagation();
            exportSingleRowPDF(row);
            menu.remove();
            cleanup();
          });

          menu.appendChild(itemCSV);
          menu.appendChild(itemPDF);
          document.body.appendChild(menu);

          // close when clicking outside or on scroll
          function onDocClick(ev) {
            if (!menu.contains(ev.target) && ev.target !== button) {
              menu.remove();
              cleanup();
            }
          }
          function onScroll() {
            // close on scroll to avoid mis-positioning
            menu.remove();
            cleanup();
          }
          document.addEventListener('click', onDocClick);
          window.addEventListener('scroll', onScroll, { passive: true });

          function cleanup() {
            document.removeEventListener('click', onDocClick);
            window.removeEventListener('scroll', onScroll);
          }
        }

        // open menu on dlBtn click
        dlBtn.addEventListener('click', function (ev) {
          ev.stopPropagation();
          showDownloadMenu(dlBtn, tr);
        });

        // store the raw ISO date on the row for reliable filtering/parsing
        if (dateIso) tr.dataset.date = dateIso;

        // append action buttons and the row once (sorting will place it visually)
        tdAction.appendChild(viewBtn);
        tdAction.appendChild(dlBtn);
        tr.appendChild(tdAction);
        tbody.appendChild(tr);

        // insert (append) into tbody — we'll rely on sortReportsTable() to order rows
        tbody.appendChild(tr);

  // after adding, keep table sorted and re-apply the current filter so the row appears/hides correctly
  sortReportsTable(true);
  const activeRange = getActiveRange();
  try { applyReportFilter(activeRange); } catch (e) { /* ignore if not available */ }

        return tr;
      }

  // persist description for export headers
  window.lastExportDescription = description;

  // Add the row (client-side)
  addReportRow(reportType, description, generatedBy, dateStr);

  // Re-apply current filter so the row respects the visible range
  const activeRange = getActiveRange();
  // applyReportFilter is defined elsewhere on the page
  try { applyReportFilter(activeRange); } catch (e) { /* ignore if not available */ }

      // show a centered, professional success notification (replaces the previous green toast)
      (function showToast(msg) {
  // remove any existing portal (prevents duplicates)
  const prevPortal = document.getElementById('globalToastPortal');
  if (prevPortal) prevPortal.remove();

  // create portal that covers the viewport and centers its content
  const portal = document.createElement('div');
  portal.id = 'globalToastPortal';
  portal.setAttribute('aria-hidden', 'true'); // portal itself is decorative
  Object.assign(portal.style, {
    position: 'fixed',
    inset: '0',               // top:0; right:0; bottom:0; left:0;
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    pointerEvents: 'none',    // allow clicks to pass through except on the toast
    zIndex: 1600
  });

  // toast element (interactive region)
  const toast = document.createElement('div');
  toast.id = 'reportSavedToast';
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  toast.textContent = msg;
  Object.assign(toast.style, {
    pointerEvents: 'auto',   // make the toast itself interactable if needed
    background: 'green',
    color: '#ffffff',
    padding: '10px 14px',
    borderRadius: '10px',
    boxShadow: '0 12px 40px rgba(2,6,23,0.10)',
    border: '1px solid rgba(15,23,42,0.06)',
    fontWeight: 400,
    transform: 'translateY(6px) scale(0.98)',
    opacity: '0',
    transition: 'opacity 220ms ease, transform 220ms ease',
    maxWidth: '88%',
    textAlign: 'center'
  });

  portal.appendChild(toast);
  document.body.appendChild(portal);

  // force layout then animate in
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0) scale(1)';
  });

  // auto-dismiss after a short delay
  const DURATION = 1500;
  const fadeOut = () => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(6px) scale(0.98)';
    // remove portal after transition
    setTimeout(() => {
      const p = document.getElementById('globalToastPortal');
      if (p) p.remove();
    }, 260);
  };

  const timer = setTimeout(fadeOut, DURATION);

  // allow early dismissal by click or Escape (optional)
  function onClick() {
    clearTimeout(timer);
    fadeOut();
    cleanup();
  }
  function onKey(e) {
    if (e.key === 'Escape') { clearTimeout(timer); fadeOut(); cleanup(); }
  }

  toast.addEventListener('click', onClick);
  document.addEventListener('keydown', onKey);

  function cleanup() {
    toast.removeEventListener('click', onClick);
    document.removeEventListener('keydown', onKey);
  }
})('Report saved');
      
      // clear the form (optional)
      form.reset();
      window.lastExportDescription = ''; // clear any export metadata

      // close modal
      closeModal();

      // NOTE: if you want to persist the saved report to the server, replace addReportRow(...) above
      // with an AJAX POST to a route that stores the report and returns an id / download links.
    });
  })();

  // Update export functions so they export only visible rows (skip display:none)
  window.exportCSV = function exportCSV() {
    try {
      const rows = [];
      const table = document.getElementById('reportTable');
      if (!table) return;

      // If a description was provided in the modal, include it as the first CSV row
      const desc = window.lastExportDescription ? String(window.lastExportDescription).replace(/"/g, '""') : null;
      if (desc) {
        rows.push('"' + 'Description: ' + desc + '"');
        rows.push(''); // blank row for separation
      }

      const trList = table.querySelectorAll('tr');
      trList.forEach((tr) => {
        if (getComputedStyle(tr).display === 'none') return; // skip hidden rows
        const cells = Array.from(tr.cells || []);
        const values = cells.map((cell, idx) => {
          if (idx === cells.length - 1) {
            const btns = Array.from(cell.querySelectorAll('button')).map(b => b.innerText.trim()).filter(Boolean);
            return '"' + btns.join(' | ').replace(/"/g, '""') + '"';
          }
          const txt = cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/"/g, '""').trim();
          return '"' + txt + '"';
        });
        rows.push(values.join(','));
      });

      const csvContent = rows.join('\n');
      const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = "reports.csv";
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      console.error('CSV export failed:', err);
      alert('CSV export failed. See console for details.');
    }
  };

  // Export PDF using jspdf + autotable
  window.exportPDF = function exportPDF() {
    try {
      if (!window.jspdf) {
        alert('PDF export is not available (jspdf not loaded).');
        return;
      }
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.text("InkWise Reports", 14, 15);

      // include description under the title if provided
      const desc = window.lastExportDescription ? String(window.lastExportDescription).trim() : '';
      let startY = 22;
      if (desc) {
        // wrap long descriptions if necessary (basic)
        const split = doc.splitTextToSize(desc, 180);
        doc.text(split, 14, 22);
        startY = 22 + (split.length * 6) + 4;
      }

      const table = document.getElementById('reportTable');
      const head = [];
      const body = [];
      if (table) {
        const thead = table.querySelectorAll('thead th');
        thead.forEach((th) => head.push(th.innerText.trim()));

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((tr) => {
          if (getComputedStyle(tr).display === 'none') return; // skip hidden rows
          const cells = tr.querySelectorAll('td');
          const rowData = [];
          cells.forEach((td, idx) => {
            if (idx === cells.length - 1) {
              const btns = Array.from(td.querySelectorAll('button')).map(b => b.innerText.trim()).filter(Boolean);
              rowData.push(btns.join(' | '));
            } else {
              rowData.push(td.innerText.trim());
            }
          });
          body.push(rowData);
        });
      }

      if (doc.autoTable) {
        doc.autoTable({
          head: [head],
          body: body,
          startY: startY,
          theme: 'grid',
          headStyles: { fillColor: [232, 241, 250] }
        });
      } else {
        let y = startY;
        body.forEach((r) => {
          doc.text(r.join(' | '), 14, y);
          y += 8;
        });
      }

      doc.save("reports.pdf");
    } catch (err) {
      console.error('PDF export failed:', err);
      alert('PDF export failed. See console for details.');
    }
  };

  // helper: sort the reports table by the Date Generated column (4th column)
  // desc=true -> newest first
  function sortReportsTable(desc = true) {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    // build array of rows with parsed dates
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      const aCell = (a.cells && a.cells[3]) ? (a.cells[3].textContent || '').trim() : '';
      const bCell = (b.cells && b.cells[3]) ? (b.cells[3].textContent || '').trim() : '';

      const aDate = parseYMD(aCell) || new Date(0);
      const bDate = parseYMD(bCell) || new Date(0);

      return desc ? (bDate.getTime() - aDate.getTime()) : (aDate.getTime() - bDate.getTime());
    });

    // re-append rows in sorted order
    rows.forEach(r => tbody.appendChild(r));
  }
});
</script>
@endsection
