@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Page-scoped compact table overrides (do not modify materials.css) -->
<style>
  /* Reduce outer page padding and add top spacing for fixed topbar */
  main.materials-page.admin-page-shell.materials-container {
    padding: 10px 16px !important;
    padding-top: calc(var(--owner-topbar-height, 64px) + 8px) !important;
    margin-left: 230px !important;
  }

  /* Constrain inner content width so table is centered and not too wide */
  main.materials-page .page-inner { max-width: 1100px; margin:0 auto; padding:0 8px; }

  /* Make table wrapper compact and allow horizontal scroll on narrow screens */
  main.materials-page .table-wrapper { padding: 8px !important; overflow-x:auto; }

  /* Compact table: smaller min-width, font-size and cell padding */
  main.materials-page .table { min-width: 680px; font-size:0.88rem; }
  main.materials-page .table thead th,
  main.materials-page .table tbody td { padding:8px 10px; }

  /* Slightly smaller header text for page subtitle area */
  main.materials-page .page-title { font-size:1.25rem; }

  /* Tweak badges to be slightly smaller */
  main.materials-page .badge { padding:5px 10px; font-size:0.72rem; }

  @media (max-width:900px) {
    main.materials-page .page-inner { max-width:100%; }
    main.materials-page .table { min-width: 560px; font-size:0.82rem; }
    main.materials-page { margin-left: 90px !important; }
  }
  /* Summary cards (page-scoped) - keep lightweight and consistent with products/index */
  .summary-grid { margin:0 0 12px 0; display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; }
  .summary-card { background:#fff; border-radius:10px; padding:12px; box-shadow:0 6px 18px rgba(15,23,42,0.04); display:block; text-decoration:none; color:inherit; }
  .summary-card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
  .summary-card-label { font-size:0.86rem; color:#475569; }
  .summary-card-value { display:block; font-size:1.25rem; font-weight:800; color:#0f172a; margin-top:4px; }
  .summary-card-meta { color:#6b7280; font-size:0.82rem; }

  /* Dark mode styles */
  .dark-mode .summary-card { background:#374151; color:#f9fafb; }
  .dark-mode .summary-card-label { color:#d1d5db; }
  .dark-mode .summary-card-value { color:#f9fafb; }
  .dark-mode .summary-card-meta { color:#9ca3af; }

  /* Dark mode for table */
  .dark-mode .table { background:#1f2937; color:#f9fafb; }
  .dark-mode .table thead th { background:#374151; color:#f9fafb; border-color:#4b5563; }
  .dark-mode .table tbody td { border-color:#4b5563; }
  .dark-mode .table tbody tr:hover { background:#374151; }

  /* Dark mode for body */
  .dark-mode body { background:#111827; }
</style>

<main class="materials-page admin-page-shell materials-container" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Transactions</h1>
      <p class="page-subtitle">Payment summaries and statuses</p>
    </div>
  </header>

  <div class="page-inner">
    @php
      // Compute simple transaction summaries from $transactions when available,
      // otherwise attempt a safe model query if Transaction model exists.
      $totalTransactions = 0;
      $totalAmount = 0.00;
      $paidCount = 0;
      $pendingCount = 0;

      if (isset($transactions) && $transactions instanceof \Illuminate\Support\Collection) {
          $totalTransactions = $transactions->count();
          $totalAmount = $transactions->sum(fn($t) => (float) ($t->amount ?? ($t->total ?? 0)) );
          $paidCount = $transactions->filter(fn($t) => strtolower(trim($t->status ?? '')) === 'paid')->count();
          $pendingCount = $transactions->filter(fn($t) => in_array(strtolower(trim($t->status ?? '')), ['pending','unpaid']))->count();
      } elseif (class_exists(\App\Models\Transaction::class)) {
          try {
              $totalTransactions = \App\Models\Transaction::count();
              $totalAmount = (float) \App\Models\Transaction::sum('amount');
              $paidCount = \App\Models\Transaction::whereRaw("LOWER(COALESCE(status, '')) = 'paid'")->count();
              $pendingCount = \App\Models\Transaction::whereIn('status', ['pending','unpaid'])->count();
          } catch (\Exception $e) {
              // swallow DB exceptions in view; keep zero defaults
          }
      }
    @endphp

    <section class="summary-grid" aria-label="Transactions summary">
      <a href="{{ url()->current() }}" class="summary-card" aria-label="Total transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: list -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Total Transactions</span>
          </div>
          <span class="summary-card-chip accent">All</span>
        </div>
        <span class="summary-card-value">{{ number_format($totalTransactions) }}</span>
        <span class="summary-card-meta">Transactions recorded</span>
      </a>

      <a href="{{ url()->current() }}" class="summary-card" aria-label="Total amount">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: peso / money -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v8M9 6h6M9 18h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Total Amount</span>
          </div>
          <span class="summary-card-chip accent">PHP</span>
        </div>
        <span class="summary-card-value">₱{{ number_format($totalAmount, 2) }}</span>
        <span class="summary-card-meta">Collected / processed</span>
      </a>

      <a href="{{ request()->fullUrlWithQuery(['status' => 'paid']) }}" class="summary-card" aria-label="Paid transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: check circle -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 12.5l1.8 1.8 4.2-4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Paid</span>
          </div>
          <span class="summary-card-chip accent">Settled</span>
        </div>
        <span class="summary-card-value">{{ number_format($paidCount) }}</span>
        <span class="summary-card-meta">Confirmed payments</span>
      </a>

      <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="summary-card" aria-label="Pending transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: clock -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Pending</span>
          </div>
          <span class="summary-card-chip accent">Review</span>
        </div>
        <span class="summary-card-value">{{ number_format($pendingCount) }}</span>
        <span class="summary-card-meta">Awaiting confirmation</span>
      </a>
    </section>
    <section class="materials-toolbar" aria-label="Transactions search">
      <div class="materials-toolbar__search">
        <form method="GET" action="{{ url()->current() }}">
          <div class="search-input">
            <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
            <input class="form-control" type="text" name="search" placeholder="Search by Transaction ID, Order or Customer" value="{{ request('search') }}">
          </div>
          <button type="submit" class="btn btn-secondary">Search</button>
        </form>
      </div>
      <div class="materials-toolbar__actions" style="display:flex; gap:8px; align-items:center;">
        <!-- Add Transaction (opens UI flow modal) -->
        <button type="button" class="btn btn-primary" data-action="open-add-transaction" title="Add Transaction">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span style="margin-left:8px;">Add</span>
        </button>

        <!-- Import CSV (placeholder) -->
        <button type="button" class="btn btn-secondary" data-action="open-import-csv" title="Import CSV">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M8 11l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Import
        </button>

        <!-- Export (placeholder) -->
        <button type="button" class="btn btn-secondary" data-action="export-csv" title="Export CSV">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 10l5-5 5 5M12 5v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Export
        </button>

        <!-- Quick filters: Paid / Pending -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'paid']) }}" class="btn" title="Show paid">Paid</a>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="btn btn-warning" title="Show pending">Pending</a>

        <!-- View History placeholder -->
        <button type="button" class="btn btn-outline" data-action="open-history" title="View history">History</button>
      </div>
    </section>

    <!-- Modal placeholders (non-functional flows) -->
    <div id="modalOverlay" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,0.5); z-index:60;">
      <div id="modalBox" style="max-width:720px; margin:6vh auto; background:#fff; border-radius:10px; padding:18px; position:relative;">
        <button id="modalClose" style="position:absolute; right:12px; top:12px; border:0; background:transparent; font-size:18px;">×</button>
        <div id="modalContent">
          <!-- Content will be injected by page script to demonstrate flows -->
        </div>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Payment Method</th>
            <th>Date</th>
            <th>Amount (PHP)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="fw-bold">TXN10001</td>
            <td>#1001</td>
            <td>Frechy</td>
            <td>GCash</td>
            <td>2025-04-28</td>
            <td>12,000.00</td>
            <td><span class="badge stock-ok">Paid</span></td>
          </tr>
          <tr>
            <td class="fw-bold">TXN10002</td>
            <td>#1002</td>
            <td>Kristine</td>
            <td>COD</td>
            <td>2025-04-28</td>
            <td>7,500.00</td>
            <td><span class="badge stock-low">Pending</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

@endsection

@push('scripts')
<script>
  (function(){
    const overlay = document.getElementById('modalOverlay');
    const box = document.getElementById('modalBox');
    const content = document.getElementById('modalContent');
    const closeBtn = document.getElementById('modalClose');

    function openModal(html) {
      if (!overlay) return;
      content.innerHTML = html;
      overlay.style.display = 'block';
    }
    function closeModal() { if (!overlay) return; overlay.style.display='none'; content.innerHTML=''; }

    document.addEventListener('click', function(e){
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.dataset.action;
      if (action === 'open-add-transaction') {
        openModal(`<h3>Add Transaction (UI Demo)</h3>
          <p>This is a non-functional demo of the add transaction flow. Fill fields and click Submit to simulate.</p>
          <form id="demoAddForm">
            <label>Order ID<br><input name="order_id" class="form-control" /></label><br>
            <label>Amount<br><input name="amount" class="form-control" /></label><br>
            <label>Payment Method<br><input name="method" class="form-control" /></label><br>
            <div style="margin-top:10px;"><button type="button" id="demoSubmit" class="btn btn-primary">Submit (demo)</button></div>
          </form>`);
      }

      if (action === 'open-import-csv') {
        openModal(`<h3>Import CSV (UI Demo)</h3>
          <p>Select a CSV file to preview rows. This demo does not upload.</p>
          <input type="file" accept=".csv" id="demoCsvInput" />
          <div id="demoCsvPreview" style="margin-top:12px; font-size:0.9rem; color:#374151;"></div>`);
      }

      if (action === 'export-csv') {
        openModal(`<h3>Export CSV (UI Demo)</h3>
          <p>This would trigger a server export in a real app. Here it's just a demo.</p>
          <div style="margin-top:12px;"><button class="btn btn-secondary" id="demoExportBtn">Download (demo)</button></div>`);
      }

      if (action === 'open-history') {
        openModal(`<h3>Transaction History (UI Demo)</h3>
          <p>Show recent activity; this is a visual-only placeholder.</p>
          <ul style="margin-top:8px; list-style:none; padding-left:0; color:#374151;"><li>2025-04-28 — TXN10001 was processed (Paid)</li><li>2025-04-28 — TXN10002 created (Pending)</li></ul>`);
      }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

    // Demo handlers inside modal
    document.addEventListener('click', function(e){
      if (e.target && e.target.id === 'demoSubmit') {
        alert('Demo: transaction submitted (no server request)');
        closeModal();
      }
      if (e.target && e.target.id === 'demoExportBtn') {
        alert('Demo: export started (no server request)');
      }
      if (e.target && e.target.id === 'demoCsvInput') {
        // noop
      }
    });

    // CSV preview handling (delegated)
    document.addEventListener('change', function(e){
      if (e.target && e.target.id === 'demoCsvInput') {
        const file = e.target.files && e.target.files[0];
        const preview = document.getElementById('demoCsvPreview');
        if (!file || !preview) return;
        const reader = new FileReader();
        reader.onload = function(ev){
          const text = ev.target.result || '';
          const lines = text.split(/\r?\n/).slice(0,6).map(l => `<div style="padding:6px 0;border-bottom:1px solid #eef2ff;">${l}</div>`).join('');
          preview.innerHTML = `<div style="max-height:220px; overflow:auto;">${lines}</div>`;
        };
        reader.readAsText(file);
      }
    });
  })();
</script>
@endpush
