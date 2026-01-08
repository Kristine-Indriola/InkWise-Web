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

  .behavior-insights {
    margin: 40px 0 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .behavior-insights-header h2 {
    margin: 0;
    font-size: 1.28rem;
    font-weight: 700;
    color: #0f172a;
  }

  .behavior-insights-header p {
    margin: 0;
    font-size: 0.95rem;
    color: #6b7280;
  }

  .behavior-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    grid-auto-rows: 1fr;
  }

  .insight-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px 26px;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: 100%;
  }

  .insight-card-header {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .insight-card-header h3 {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 700;
    color: #0f172a;
  }

  .insight-card-meta {
    font-size: 0.86rem;
    color: #6b7280;
  }

  .insight-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .insight-list-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
  }

  .insight-list-label {
    font-weight: 600;
    color: #1f2937;
    flex: 1;
  }

  .insight-list-value {
    font-size: 0.88rem;
    color: #4b5563;
    white-space: nowrap;
  }

  .insight-empty {
    margin: 0;
    font-size: 0.9rem;
    color: #94a3b8;
  }

  .insight-stat-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
  }

  .insight-stat {
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: rgba(79, 70, 229, 0.08);
  }

  .insight-stat--teal { background: rgba(13, 148, 136, 0.1); }
  .insight-stat--amber { background: rgba(217, 119, 6, 0.12); }
  .insight-stat--indigo { background: rgba(79, 70, 229, 0.08); }

  .insight-stat-label {
    font-size: 0.78rem;
    color: #6366f1;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.045em;
  }

  .insight-stat--teal .insight-stat-label { color: #0f766e; }
  .insight-stat--amber .insight-stat-label { color: #b45309; }

  .insight-stat-value {
    font-size: 1.32rem;
    font-weight: 700;
    color: #0f172a;
  }

  .insight-stat-sublabel {
    font-size: 0.82rem;
    color: #6b7280;
  }

  .insight-sublist {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .insight-sublist li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.86rem;
    color: #475569;
  }

  .insight-sublist span {
    font-weight: 600;
    color: #111827;
  }

  .insight-chart {
    position: relative;
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 0;
  }

  .insight-chart canvas {
    width: 100% !important;
    height: 220px !important;
  }

  .insight-chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-top: 14px;
  }

  .dark-mode .insight-chart { background: transparent; }

  .dark-mode .behavior-insights-header h2 { color: #f9fafb; }
  .dark-mode .behavior-insights-header p { color: #9ca3af; }
  .dark-mode .behavior-grid { color: inherit; }
  .dark-mode .insight-card { background: #1f2937; box-shadow: 0 20px 38px rgba(15, 23, 42, 0.48); }
  .dark-mode .insight-card-header h3 { color: #e5e7eb; }
  .dark-mode .insight-card-meta { color: #9ca3af; }
  .dark-mode .insight-list-label { color: #e5e7eb; }
  .dark-mode .insight-list-value { color: #cbd5f5; }
  .dark-mode .insight-empty { color: #6b7280; }
  .dark-mode .insight-stat { background: rgba(88, 80, 236, 0.26); }
  .dark-mode .insight-stat--teal { background: rgba(13, 148, 136, 0.28); }
  .dark-mode .insight-stat--amber { background: rgba(217, 119, 6, 0.28); }
  .dark-mode .insight-stat-label { color: #c7d2fe; }
  .dark-mode .insight-stat--teal .insight-stat-label { color: #99f6e4; }
  .dark-mode .insight-stat--amber .insight-stat-label { color: #fcd34d; }
  .dark-mode .insight-stat-value { color: #f9fafb; }
  .dark-mode .insight-stat-sublabel { color: #cbd5f5; }
  .dark-mode .insight-sublist li { color: #cbd5f5; }
  .dark-mode .insight-sublist span { color: #f9fafb; }

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

  .summary-grid {
    grid-auto-rows: 1fr;
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
    height: 100%;
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
            <span class="summary-card-label">New Orders</span>
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
            <span class="summary-card-label">Revenue</span>
          </div>
          <span class="summary-card-chip">Finance</span>
        </div>
        <span class="summary-card-value" style="color:#0f172a;">PHP {{ number_format($totalRevenue ?? 0, 2) }}</span>
        <span class="summary-card-meta">Completed orders total</span>
      </div>
    </section>

  <!-- Charts Section -->
  <div class="charts">
    <div class="chart-container">
      <h3>Weekly Sales Trend</h3>
      <canvas id="weeklySalesChart"></canvas>
      <p id="weeklySalesEmpty" class="chart-empty-message" hidden>No sales recorded for the recent weeks.</p>
    </div>

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

    <div class="chart-container">
      <h3>Customer Retention Mix</h3>
      <canvas id="repeatCustomerChartSummary"></canvas>
      <p id="repeatCustomerChartSummaryEmpty" class="chart-empty-message" hidden>No retention data available yet.</p>
    </div>
  </div>

  @php
    $customerBehavior = $customerBehavior ?? [];
    $popularDesigns = $customerBehavior['popular_designs'] ?? [];
    $orderFrequency = $customerBehavior['order_frequency'] ?? [];
    $buyingPatterns = $customerBehavior['buying_patterns'] ?? [];
    $topDayBreakdown = collect($buyingPatterns['day_of_week_breakdown'] ?? [])->sortDesc()->take(3);
    $topTimeBreakdown = collect($buyingPatterns['time_of_day_breakdown'] ?? [])->sortDesc()->take(3);
  @endphp

  <section class="behavior-insights" aria-label="Customer behavior insights">
    <div class="behavior-insights-header">
      <h2>Customer Behavior Insights</h2>
      <p>Track popular designs, ordering cadence, and repeat buying signals.</p>
    </div>

    <div class="behavior-grid">
      <article class="insight-card" aria-label="Popular designs">
        <div class="insight-card-header">
          <h3>Popular Designs</h3>
          <span class="insight-card-meta">Top performers across completed orders</span>
        </div>

        @if(!empty($popularDesigns))
          <ul class="insight-list">
            @foreach($popularDesigns as $design)
              <li class="insight-list-item">
                <span class="insight-list-label">{{ $design['name'] }}</span>
                <span class="insight-list-value">{{ number_format($design['units_sold']) }} units · {{ number_format($design['order_count']) }} orders</span>
              </li>
            @endforeach
          </ul>
        @endif

        <div class="insight-chart" @if(empty($popularDesigns)) hidden @endif>
          <canvas id="popularDesignsChart" aria-label="Popular designs chart"></canvas>
        </div>
        <p class="insight-empty" id="popularDesignsChartEmpty" @if(!empty($popularDesigns)) hidden @endif>No design performance data yet.</p>
      </article>

      <article class="insight-card" aria-label="Order frequency">
        <div class="insight-card-header">
          <h3>Order Frequency</h3>
          <span class="insight-card-meta">Understand how often customers commit</span>
        </div>

        <div class="insight-stat-group">
          <div class="insight-stat insight-stat--teal">
            <span class="insight-stat-label">Orders (30 days)</span>
            <span class="insight-stat-value">{{ number_format($orderFrequency['orders_last_30_days'] ?? 0) }}</span>
            <span class="insight-stat-sublabel">Completed bookings over the last month</span>
          </div>
          <div class="insight-stat insight-stat--amber">
            <span class="insight-stat-label">Avg Orders / Customer</span>
            <span class="insight-stat-value">{{ number_format($orderFrequency['average_orders_per_customer'] ?? 0, 2) }}</span>
            <span class="insight-stat-sublabel">Across {{ number_format($orderFrequency['total_customers'] ?? 0) }} customers</span>
          </div>
          <div class="insight-stat insight-stat--indigo">
            <span class="insight-stat-label">Repeat Rate</span>
            <span class="insight-stat-value">{{ number_format($orderFrequency['repeat_customer_rate'] ?? 0, 1) }}%</span>
            <span class="insight-stat-sublabel">Share of customers with multiple orders</span>
          </div>
        </div>

        <div class="insight-chart">
          <canvas id="repeatCustomerChart" aria-label="Repeat versus new customers"></canvas>
          <p id="repeatCustomerChartEmpty" class="insight-empty" hidden>Not enough data to visualize repeat customers.</p>
        </div>
      </article>

      <article class="insight-card" aria-label="Buying patterns">
        <div class="insight-card-header">
          <h3>Buying Patterns</h3>
          <span class="insight-card-meta">Reveal timing preferences and order values</span>
        </div>

        <div class="insight-stat-group">
          <div class="insight-stat insight-stat--teal">
            <span class="insight-stat-label">Top Order Day</span>
            <span class="insight-stat-value">{{ $buyingPatterns['top_order_day'] ?? '—' }}</span>
            <span class="insight-stat-sublabel">Most active day in the last 90 days</span>
          </div>
          <div class="insight-stat insight-stat--amber">
            <span class="insight-stat-label">Preferred Time</span>
            <span class="insight-stat-value">{{ $buyingPatterns['top_order_time_window'] ?? '—' }}</span>
            <span class="insight-stat-sublabel">Peak order window recently</span>
          </div>
          <div class="insight-stat insight-stat--indigo">
            <span class="insight-stat-label">Average Value</span>
            <span class="insight-stat-value">PHP {{ number_format($buyingPatterns['average_order_value'] ?? 0, 2) }}</span>
            <span class="insight-stat-sublabel">Completed order average</span>
          </div>
        </div>

        @if($topDayBreakdown->isNotEmpty() || $topTimeBreakdown->isNotEmpty())
          <div>
            @if($topDayBreakdown->isNotEmpty())
              <h4 class="insight-card-meta">Top Order Days</h4>
              <ul class="insight-sublist">
                @foreach($topDayBreakdown as $day => $count)
                  <li><span>{{ $day }}</span> {{ number_format($count) }} orders</li>
                @endforeach
              </ul>
            @endif

            @if($topTimeBreakdown->isNotEmpty())
              <h4 class="insight-card-meta" style="margin-top:12px;">Time of Day Breakdown</h4>
              <ul class="insight-sublist">
                @foreach($topTimeBreakdown as $label => $count)
                  <li><span>{{ $label }}</span> {{ number_format($count) }} orders</li>
                @endforeach
              </ul>
            @endif
          </div>
        @else
          <p class="insight-empty">Not enough recent orders to determine timing trends.</p>
        @endif

        <div class="insight-chart-grid">
          <div class="insight-chart">
            <canvas id="dayOfWeekChart" aria-label="Orders by day of week"></canvas>
            <p id="dayOfWeekChartEmpty" class="insight-empty" hidden>No recent day-of-week distribution to display.</p>
          </div>
          <div class="insight-chart">
            <canvas id="timeOfDayChart" aria-label="Orders by time of day"></canvas>
            <p id="timeOfDayChartEmpty" class="insight-empty" hidden>No recent time-of-day distribution to display.</p>
          </div>
        </div>
      </article>
    </div>
  </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const weeklySalesTrend = @json($weeklySalesTrend ?? ['labels' => [], 'totals' => []]);
  const topSellingProducts = @json($topSellingProducts ?? ['labels' => [], 'data' => []]);
  const inventoryMovement = @json($inventoryMovement ?? ['labels' => [], 'incoming' => [], 'outgoing' => []]);
  const weeklySalesCanvas = document.getElementById('weeklySalesChart');
  const weeklySalesEmpty = document.getElementById('weeklySalesEmpty');

  if (weeklySalesCanvas) {
    const labels = Array.isArray(weeklySalesTrend.labels) ? weeklySalesTrend.labels : [];
    const totals = Array.isArray(weeklySalesTrend.totals) ? weeklySalesTrend.totals.map(Number) : [];
    const hasSalesData = labels.length > 0 && totals.some(value => value > 0);

    if (!hasSalesData) {
      weeklySalesCanvas.style.display = 'none';
      if (weeklySalesEmpty) {
        weeklySalesEmpty.hidden = false;
      }
    } else {
      weeklySalesCanvas.style.display = '';
      if (weeklySalesEmpty) {
        weeklySalesEmpty.hidden = true;
      }

      const maxValue = Math.max(...totals);
      const weeklyCtx = weeklySalesCanvas.getContext('2d');
      new Chart(weeklyCtx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Weekly Sales (PHP)',
              data: totals,
              borderColor: 'rgba(59,130,246,0.85)',
              backgroundColor: 'rgba(59,130,246,0.18)',
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 5,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const parsed = context.parsed || {};
                  const value = typeof parsed.y === 'number' ? parsed.y : 0;
                  return 'Sales: PHP ' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              suggestedMax: maxValue < 10 ? 10 : maxValue + Math.ceil(maxValue * 0.1),
              grid: { color: '#eef2f7' },
              ticks: {
                callback: function(value) {
                  return '₱' + Number(value).toLocaleString();
                }
              }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }
  }

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

  const behaviorData = @json($customerBehavior ?? []);
  const behaviorChartData = behaviorData.chart_data || {};

  const popularDesignCanvas = document.getElementById('popularDesignsChart');
  const popularDesignEmpty = document.getElementById('popularDesignsChartEmpty');
  if (popularDesignCanvas && window.Chart) {
    const popularData = behaviorChartData.popular_designs || {};
    const popularLabels = Array.isArray(popularData.labels) ? popularData.labels : [];
    const popularUnits = Array.isArray(popularData.units) ? popularData.units.map(Number) : [];
    const popularOrders = Array.isArray(popularData.orders) ? popularData.orders.map(Number) : [];
    const hasPopularData = popularLabels.length > 0 && (popularUnits.some(value => value > 0) || popularOrders.some(value => value > 0));

    if (!hasPopularData) {
      popularDesignCanvas.style.display = 'none';
      if (popularDesignEmpty) {
        popularDesignEmpty.hidden = false;
      }
    } else {
      if (popularDesignEmpty) {
        popularDesignEmpty.hidden = true;
      }
      popularDesignCanvas.style.display = '';

      const ctx = popularDesignCanvas.getContext('2d');
      const palette = [
        'rgba(37, 99, 235, 0.75)',
        'rgba(96, 165, 250, 0.75)',
        'rgba(14, 165, 233, 0.75)',
        'rgba(59, 130, 246, 0.65)',
        'rgba(37, 99, 235, 0.55)'
      ];
      const barColors = popularLabels.map((_, index) => palette[index % palette.length]);
      const hasOrdersSeries = popularOrders.some(value => value > 0);
      const datasets = [
        {
          label: 'Units Sold',
          data: popularUnits,
          backgroundColor: barColors,
          borderRadius: 8,
          maxBarThickness: 46
        }
      ];

      if (hasOrdersSeries) {
        datasets.push({
          type: 'line',
          label: 'Orders',
          data: popularOrders,
          borderColor: 'rgba(99, 102, 241, 0.9)',
          backgroundColor: 'rgba(99, 102, 241, 0.16)',
          tension: 0.35,
          pointRadius: 4,
          pointHoverRadius: 5,
          fill: true,
          yAxisID: 'y1'
        });
      }

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: popularLabels,
          datasets
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Units Sold'
              }
            },
            y1: {
              beginAtZero: true,
              display: hasOrdersSeries,
              position: 'right',
              grid: { drawOnChartArea: false },
              title: {
                display: hasOrdersSeries,
                text: 'Orders'
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  if (context.dataset.type === 'line') {
                    return `${context.dataset.label}: ${Number(context.parsed.y).toLocaleString()}`;
                  }
                  return `${context.dataset.label}: ${Number(context.parsed.y).toLocaleString()}`;
                }
              }
            }
          }
        }
      });
    }
  }

  const makeRepeatChart = (canvasId, emptyId, options = {}) => {
    const canvas = document.getElementById(canvasId);
    const emptyEl = emptyId ? document.getElementById(emptyId) : null;
    if (!canvas || !window.Chart) {
      return;
    }

    const repeatData = behaviorChartData.repeat_distribution || {};
    const repeatCustomers = Number(repeatData.repeat_customers || 0);
    const singleCustomers = Number(repeatData.single_customers || 0);
    const totalCustomers = repeatCustomers + singleCustomers;

    if (totalCustomers <= 0) {
      canvas.style.display = 'none';
      if (emptyEl) {
        emptyEl.hidden = false;
      }
      return;
    }

    canvas.style.display = '';
    if (emptyEl) {
      emptyEl.hidden = true;
    }

    const ctx = canvas.getContext('2d');
    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Repeat Customers', 'First-Time Customers'],
        datasets: [{
          data: [repeatCustomers, singleCustomers],
          backgroundColor: ['rgba(16, 185, 129, 0.82)', 'rgba(226, 232, 240, 0.9)'],
          borderColor: ['rgba(16, 185, 129, 0.92)', 'rgba(226, 232, 240, 1)'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: options.maintainAspectRatio ?? false,
        cutout: options.cutout ?? '65%',
        plugins: {
          legend: {
            position: options.legendPosition ?? 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = Number(context.parsed);
                const percentage = totalCustomers > 0 ? ((value / totalCustomers) * 100).toFixed(1) : 0;
                return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
              }
            }
          }
        }
      }
    });
  };

  makeRepeatChart('repeatCustomerChart', 'repeatCustomerChartEmpty', {
    cutout: '62%',
    legendPosition: 'bottom',
    maintainAspectRatio: false
  });
  makeRepeatChart('repeatCustomerChartSummary', 'repeatCustomerChartSummaryEmpty', {
    cutout: '58%',
    legendPosition: 'bottom',
    maintainAspectRatio: false
  });

  const dayCanvas = document.getElementById('dayOfWeekChart');
  const dayEmpty = document.getElementById('dayOfWeekChartEmpty');
  if (dayCanvas && window.Chart) {
    const dayData = behaviorChartData.day_of_week || {};
    const dayLabels = Array.isArray(dayData.labels) ? dayData.labels : [];
    const dayCounts = Array.isArray(dayData.counts) ? dayData.counts.map(Number) : [];
    const hasDayData = dayLabels.length > 0 && dayCounts.some(value => value > 0);

    if (!hasDayData) {
      dayCanvas.style.display = 'none';
      if (dayEmpty) {
        dayEmpty.hidden = false;
      }
    } else {
      dayCanvas.style.display = '';
      if (dayEmpty) {
        dayEmpty.hidden = true;
      }

      const ctx = dayCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: dayLabels,
          datasets: [{
            label: 'Orders',
            data: dayCounts,
            backgroundColor: 'rgba(59, 130, 246, 0.68)',
            borderRadius: 8,
            maxBarThickness: 38
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }
  }

  const timeCanvas = document.getElementById('timeOfDayChart');
  const timeEmpty = document.getElementById('timeOfDayChartEmpty');
  if (timeCanvas && window.Chart) {
    const timeData = behaviorChartData.time_of_day || {};
    const timeLabels = Array.isArray(timeData.labels) ? timeData.labels : [];
    const timeCounts = Array.isArray(timeData.counts) ? timeData.counts.map(Number) : [];
    const hasTimeData = timeLabels.length > 0 && timeCounts.some(value => value > 0);

    if (!hasTimeData) {
      timeCanvas.style.display = 'none';
      if (timeEmpty) {
        timeEmpty.hidden = false;
      }
    } else {
      timeCanvas.style.display = '';
      if (timeEmpty) {
        timeEmpty.hidden = true;
      }

      const ctx = timeCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: timeLabels,
          datasets: [{
            label: 'Orders',
            data: timeCounts,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderRadius: 8,
            maxBarThickness: 38
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
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
