@php
  $pageTitle = $pageTitle ?? 'Reports';
  $pageSubtitle = $pageSubtitle ?? 'Exportable reports & analytics';
  $summaryCards = $summaryCards ?? [];
  $charts = $charts ?? [];
  $tableTitle = $tableTitle ?? 'Reports';
  $generateModalTitle = $generateModalTitle ?? 'Generate Report';
  $summaryCardIcons = [
    'revenue' => [
      'class' => 'summary-card-icon--revenue',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 17l6-6 4 4 6-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M14.5 8H20v5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ],
    'orders' => [
      'class' => 'summary-card-icon--orders',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 4h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 3h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M10 11.5l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ],
    'average-order' => [
      'class' => 'summary-card-icon--average',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 14a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 8v4l3-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 18h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    ],
    'profit' => [
      'class' => 'summary-card-icon--profit',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8c0-2.1 3.4-3.5 5-3.5S17 5.9 17 8s-3.4 3.5-5 3.5S7 10.1 7 8z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 8v6c0 2.1 3.4 3.5 5 3.5s5-1.4 5-3.5V8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 14c0 2.1 3.4 3.5 5 3.5s5-1.4 5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ],
    'inventory-total' => [
      'class' => 'summary-card-icon--inventory-total',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9.5 12 14l9-4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3.5 21 8v8l-9 4.5-9-4.5V8l9-4.5z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12.5V20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M21 8l-9 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    ],
    'inventory-low' => [
      'class' => 'summary-card-icon--inventory-low',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5 21 19.5H3L12 4.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 10v3.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="17" r="1" fill="currentColor"/></svg>',
    ],
    'inventory-out' => [
      'class' => 'summary-card-icon--inventory-out',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M9 9l6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M15 9l-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    ],
    'inventory-pending' => [
      'class' => 'summary-card-icon--inventory-pending',
      'svg' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 16V9a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 13h3a2 2 0 0 1 2 2v3H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 16h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M7 16v1a2 2 0 0 0 2 2h2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    ],
  ];
@endphp

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<style>
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
    background: rgba(148, 185, 255, 0.18);
    color: #2563eb;
    transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
  }

  .summary-card-icon svg {
    width: 18px;
    height: 18px;
  }

  .summary-card-icon--revenue {
    background: rgba(59, 130, 246, 0.2);
    color: #1d4ed8;
  }

  .summary-card-icon--orders {
    background: rgba(16, 185, 129, 0.22);
    color: #047857;
  }

  .summary-card-icon--average {
    background: rgba(245, 158, 11, 0.24);
    color: #b45309;
  }

  .summary-card-icon--profit {
    background: rgba(139, 92, 246, 0.2);
    color: #6d28d9;
  }

  .summary-card-icon--inventory-total {
    background: rgba(37, 99, 235, 0.22);
    color: #1d4ed8;
  }

  .summary-card-icon--inventory-low {
    background: rgba(249, 115, 22, 0.24);
    color: #c2410c;
  }

  .summary-card-icon--inventory-out {
    background: rgba(239, 68, 68, 0.24);
    color: #b91c1c;
  }

  .summary-card-icon--inventory-pending {
    background: rgba(34, 197, 94, 0.22);
    color: #047857;
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
    .owner-dashboard-main { padding: 24px 20px 32px 12px; }
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
  .dark-mode .summary-card-icon { background: rgba(148, 185, 255, 0.28); color: #93c5fd; }
  .dark-mode .summary-card-icon--revenue { background: rgba(59, 130, 246, 0.32); color: #bfdbfe; }
  .dark-mode .summary-card-icon--orders { background: rgba(16, 185, 129, 0.3); color: #6ee7b7; }
  .dark-mode .summary-card-icon--average { background: rgba(245, 158, 11, 0.32); color: #fcd34d; }
  .dark-mode .summary-card-icon--profit { background: rgba(139, 92, 246, 0.32); color: #ddd6fe; }
  .dark-mode .summary-card-icon--inventory-total { background: rgba(59, 130, 246, 0.32); color: #bfdbfe; }
  .dark-mode .summary-card-icon--inventory-low { background: rgba(249, 115, 22, 0.34); color: #fdba74; }
  .dark-mode .summary-card-icon--inventory-out { background: rgba(239, 68, 68, 0.34); color: #fca5a5; }
  .dark-mode .summary-card-icon--inventory-pending { background: rgba(34, 197, 94, 0.32); color: #a7f3d0; }
  .dark-mode .chart-card { background: #1f2937; box-shadow: 0 16px 32px rgba(15, 23, 42, 0.5); }
  .dark-mode .chart-card h4 { color: #f9fafb; }
  .dark-mode .materials-toolbar__search select { background: #374151; border-color: #4b5563; color: #f9fafb; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35); }
  .dark-mode .owner-dashboard-inner .table-wrapper { background: #1f2937; border-color: rgba(148, 185, 255, 0.32); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04); }
  .dark-mode .owner-dashboard-inner .table { color: #f9fafb; }
  .dark-mode .owner-dashboard-inner .table thead th { background: rgba(148, 185, 255, 0.22); color: #0f172a; }
  .dark-mode .owner-dashboard-inner .table tbody td { border-color: rgba(148, 185, 255, 0.18); }
  .dark-mode .owner-dashboard-inner .table tbody tr:hover { background: rgba(148, 185, 255, 0.12); }

  .generate-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.48);
    z-index: 1500;
  }

  .generate-modal[aria-hidden="false"] {
    display: flex;
  }

  .generate-modal-dialog {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 28px 48px rgba(15, 23, 42, 0.24);
    overflow: hidden;
  }

  .generate-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(148, 185, 255, 0.22);
  }

  .generate-modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
  }

  .generate-modal-close {
    border: none;
    background: transparent;
    font-size: 1.2rem;
    cursor: pointer;
    color: #6b7280;
    transition: color 0.18s ease, transform 0.18s ease;
  }

  .generate-modal-close:hover,
  .generate-modal-close:focus {
    color: #0f172a;
    transform: scale(1.05);
  }

  .generate-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .generate-form .form-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .generate-form .form-label {
    font-weight: 600;
    font-size: 0.9rem;
    color: #475569;
  }

  .generate-form .form-control,
  .generate-form textarea {
    width: 100%;
    border-radius: 10px;
    border: 1px solid rgba(148, 185, 255, 0.32);
    padding: 10px 12px;
    font-size: 0.95rem;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .generate-form .form-control:focus,
  .generate-form textarea:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.38);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
  }

  .generate-modal-footer {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .date-range-fieldset {
    border: none;
    padding: 0;
    margin: 0;
  }

  .date-range-fieldset .radio-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }

  .date-range-fieldset label {
    font-weight: 500;
    color: #475569;
  }

  .dark-mode .generate-modal-dialog {
    background: #1f2937;
    color: #f9fafb;
    box-shadow: 0 32px 60px rgba(0, 0, 0, 0.45);
  }

  .dark-mode .generate-modal-header {
    border-color: rgba(148, 185, 255, 0.25);
  }

  .dark-mode .generate-modal-close {
    color: #cbd5f5;
  }

  .dark-mode .generate-modal-close:hover,
  .dark-mode .generate-modal-close:focus {
    color: #ffffff;
  }

  .dark-mode .generate-form .form-control,
  .dark-mode .generate-form textarea {
    background: #374151;
    border-color: rgba(148, 185, 255, 0.32);
    color: #f9fafb;
  }

  .dark-mode .generate-form .form-label {
    color: #e2e8f0;
  }
</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">{{ $pageTitle }}</h1>
        <p class="page-subtitle">{{ $pageSubtitle }}</p>
      </div>
    </header>

    <div class="page-inner owner-dashboard-inner">
      @if(!empty($summaryCards))
        <section class="summary-grid" aria-label="{{ $pageTitle }} summary">
          @foreach($summaryCards as $card)
            @php
              $chip = $card['chip'] ?? null;
              $chipData = is_array($chip) ? $chip : ['text' => $chip];
              $chipText = $chipData['text'] ?? '';
              $chipAccent = $chipData['accent'] ?? true;
              $chipClass = $chipText ? 'summary-card-chip' . ($chipAccent ? ' accent' : '') : '';
              $href = $card['href'] ?? null;
              $iconKey = $card['icon'] ?? null;
              $iconData = $iconKey && isset($summaryCardIcons[$iconKey]) ? $summaryCardIcons[$iconKey] : null;
              $iconClass = $iconData ? 'summary-card-icon ' . $iconData['class'] : null;
              $iconSvg = $iconData['svg'] ?? null;
            @endphp
            @if($href)
              <a href="{{ $href }}" class="summary-card">
                <div class="summary-card-header">
                  <div class="summary-card-heading">
                    @if($iconSvg)
                      <span class="{{ $iconClass }}" aria-hidden="true">{!! $iconSvg !!}</span>
                    @endif
                    <span class="summary-card-label">{{ $card['label'] ?? '—' }}</span>
                  </div>
                  @if($chipText)
                    <span class="{{ $chipClass }}">{{ $chipText }}</span>
                  @endif
                </div>
                <span class="summary-card-value">{{ $card['value'] ?? '—' }}</span>
                <span class="summary-card-meta">{{ $card['meta'] ?? '' }}</span>
              </a>
            @else
              <div class="summary-card">
                <div class="summary-card-header">
                  <div class="summary-card-heading">
                    @if($iconSvg)
                      <span class="{{ $iconClass }}" aria-hidden="true">{!! $iconSvg !!}</span>
                    @endif
                    <span class="summary-card-label">{{ $card['label'] ?? '—' }}</span>
                  </div>
                  @if($chipText)
                    <span class="{{ $chipClass }}">{{ $chipText }}</span>
                  @endif
                </div>
                <span class="summary-card-value">{{ $card['value'] ?? '—' }}</span>
                <span class="summary-card-meta">{{ $card['meta'] ?? '' }}</span>
              </div>
            @endif
          @endforeach
        </section>
      @endif

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

      @if(!empty($charts))
        <div class="reports-charts">
          @foreach($charts as $chart)
            <div class="chart-card">
              <h4>{{ $chart['title'] ?? 'Chart' }}</h4>
              <canvas id="{{ $chart['id'] ?? ('chart-' . $loop->index) }}" aria-label="{{ $chart['title'] ?? 'Chart' }}" role="img"></canvas>
            </div>
          @endforeach
        </div>
      @endif

      <div id="generateModal" class="generate-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="generateModalTitle">
        <div class="generate-modal-dialog" role="document">
          <header class="generate-modal-header">
            <h2 id="generateModalTitle">{{ $generateModalTitle }}</h2>
            <button id="generateModalClose" class="generate-modal-close" aria-label="Close dialog">✕</button>
          </header>

          <div class="generate-modal-body">
            <form id="generateForm" class="generate-form" onsubmit="return false;">
              <div class="form-row">
                <label for="genReportType" class="form-label">Report Type</label>
                <select id="genReportType" name="reportType" class="form-control">
                  <option value="Sales Report">Sales Report</option>
                  <option value="Inventory Report">Inventory Report</option>
                  <option value="All Reports">All Reports</option>
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
        <h3>{{ $tableTitle }}</h3>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  try {
    if (window.devicePixelRatio) {
      Chart.defaults.devicePixelRatio = window.devicePixelRatio;
    }
    Chart.defaults.color = '#0f172a';
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
    Chart.defaults.font.size = 12;

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

  function parseYMD(s) {
    if (!s) return null;
    const m = String(s).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
      const y = parseInt(m[1], 10);
      const mm = parseInt(m[2], 10) - 1;
      const d = parseInt(m[3], 10);
      return new Date(y, mm, d);
    }
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

      const diffMs = todayZero - new Date(d.getFullYear(), d.getMonth(), d.getDate());
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

      let show = true;
      switch (range) {
        case 'daily':
          show = (diffDays === 0);
          break;
        case 'weekly':
          show = (diffDays >= 0 && diffDays <= 6);
          break;
        case 'monthly':
          show = (diffDays >= 0 && diffDays <= 30);
          break;
        case 'yearly':
          show = (diffDays >= 0 && diffDays <= 365);
          break;
        case 'all':
        default:
          show = true;
      }
      tr.style.display = show ? '' : 'none';
    });

    sortReportsTable(true);
  }

  function getActiveRange() {
    const sel = document.getElementById('reportFilterSelect');
    if (!sel) return (window.currentReportFilter || 'all');
    return sel.value || (window.currentReportFilter || 'all');
  }

  (function initReportFilters() {
    const sel = document.getElementById('reportFilterSelect');
    if (!sel) return;

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

    try { applyReportFilter(window.currentReportFilter); } catch (e) { /* ignore */ }
  })();

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
      updateFocusable();
      if (firstFocusable) firstFocusable.focus();

      trapHandler = function (e) {
        if (e.key !== 'Tab') return;
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
      document.documentElement.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.setAttribute('aria-hidden', 'true');
      document.removeEventListener('keydown', trapHandler);
      trapHandler = null;
      document.documentElement.style.overflow = '';
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    openBtn.addEventListener('click', function () {
      openModal();
    });
    closeBtn.addEventListener('click', closeModal);
    cancelBtn && cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
    });

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
      const reportType = document.getElementById('genReportType').value;
      const dateRangeInput = form.querySelector('input[name="dateRange"]:checked');
      const dateRange = dateRangeInput ? dateRangeInput.value : 'monthly';
      const description = (document.getElementById('genDescription') || { value: '' }).value.trim();
      const generatedBy = (document.querySelector('.user-display-name') && document.querySelector('.user-display-name').textContent.trim()) || window.currentUserName || 'Owner';
      const now = new Date();
      const dateStr = now.toISOString().slice(0,10);

      function addReportRow(name, desc, by, dateIso) {
        const table = document.getElementById('reportTable');
        if (!table) return;
        const tbody = table.querySelector('tbody') || table.appendChild(document.createElement('tbody'));

        const tr = document.createElement('tr');
        tr.dataset.date = dateIso;

        const nameTd = document.createElement('td');
        nameTd.textContent = name;
        tr.appendChild(nameTd);

        const descTd = document.createElement('td');
        descTd.textContent = desc || '—';
        tr.appendChild(descTd);

        const byTd = document.createElement('td');
        byTd.textContent = by;
        tr.appendChild(byTd);

        const dateTd = document.createElement('td');
        dateTd.textContent = dateIso;
        tr.appendChild(dateTd);

        const actionTd = document.createElement('td');
        const downloadBtn = document.createElement('button');
        downloadBtn.type = 'button';
        downloadBtn.className = 'btn btn-link';
        downloadBtn.textContent = 'Download';
        downloadBtn.addEventListener('click', function () {
          alert('Downloading ' + name + '...');
        });
        actionTd.appendChild(downloadBtn);
        tr.appendChild(actionTd);

        tbody.prepend(tr);
      }

      addReportRow(reportType, description, generatedBy, dateStr);

      filterRowsForExport(dateRange, reportType === 'All Reports' ? 'all' : reportType);
      sortReportsTable(true);
      form.reset();
      closeModal();
    });
  })();

  function sortReportsTable(desc = false) {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      const aDate = parseYMD(a.dataset.date || (a.cells[3] ? a.cells[3].textContent : ''));
      const bDate = parseYMD(b.dataset.date || (b.cells[3] ? b.cells[3].textContent : ''));
      const aTime = aDate ? aDate.getTime() : 0;
      const bTime = bDate ? bDate.getTime() : 0;
      return desc ? bTime - aTime : aTime - bTime;
    });

    rows.forEach(row => tbody.appendChild(row));
  }

  window.sortReportsTable = sortReportsTable;

  window.exportCSV = function exportCSV() {
    const table = document.getElementById('reportTable');
    if (!table) return;

    let csvContent = '';
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
      const cols = Array.from(row.querySelectorAll('th, td')).map(col => '"' + (col.textContent || '').trim().replace(/"/g, '""') + '"');
      csvContent += cols.join(',') + '\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'reports.csv');
    link.click();
    URL.revokeObjectURL(url);
  };

  window.exportPDF = function exportPDF() {
    const table = document.getElementById('reportTable');
    if (!table) return;

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Reports', 14, 16);
    doc.autoTable({ html: '#reportTable', startY: 22 });
    doc.save('reports.pdf');
  };
});
</script>
