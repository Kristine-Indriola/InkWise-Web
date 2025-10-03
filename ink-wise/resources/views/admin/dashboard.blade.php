@extends('layouts.admin')

@section('title', 'Dashboard')


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/dashboard.css') }}">
@endpush
=======
@section('content')

  <div class="dashboard-container"><!-- added wrapper to constrain width -->

  {{-- ✅ Greeting Message --}}
  @if(session('success'))
      <div id="greetingMessage" 
           style="background: #dff0d8; color: #3c763d; padding: 12px; border-radius: 6px; margin-bottom: 20px; transition: opacity 1s ease;">
          {{ session('success') }}
      </div>
  @endif

  <div class="dashboard-actions">
    <a href="{{ route('admin.users.passwords.index') }}" class="dashboard-action-btn" title="Open password reset console">
      <i class="fa-solid fa-gear" aria-hidden="true"></i>
      <span>Password resets</span>
    </a>
  </div>

  <div class="cards">
    <div class="card">
      <div>🛒</div>
      <h3>Orders</h3>
      <p>20</p>
    </div>
    <div class="card">
      <div>⏳</div>
      <h3>Pending</h3>
      <p>35</p>
    </div>
    <div class="card">
      <div>⭐</div>
      <h3>Rating</h3>
      <p>4.0</p>
    </div>
  </div>


@section('content')
<main class="admin-page-shell dashboard-page" role="main">
    {{-- ✅ Greeting Message --}}
    @if(session('success'))
        <div id="greetingMessage" class="dashboard-alert" role="alert" aria-live="polite">
            {{ session('success') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Quick look at orders and stock health.</p>
        </div>
    </header>

    <section class="summary-grid" aria-label="Key performance highlights">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Orders</span>
                <span class="summary-card-chip accent">This Week</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">20</span>
                <span class="summary-card-icon" aria-hidden="true">🛒</span>
            </div>
            <span class="summary-card-meta">Orders processed</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Pending</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">35</span>
                <span class="summary-card-icon" aria-hidden="true">⏳</span>
            </div>
            <span class="summary-card-meta">Awaiting fulfillment</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Rating</span>
                <span class="summary-card-chip accent">Customer</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">4.0</span>
                <span class="summary-card-icon" aria-hidden="true">⭐</span>
            </div>
            <span class="summary-card-meta">Average feedback</span>
        </div>
    </section>


    <section class="dashboard-stock" aria-label="Inventory snapshot">
        <header class="section-header">
            <div>
                <h2 class="section-title">Stock Levels</h2>
                <p class="section-subtitle">Click anywhere on the table to jump to full materials management.</p>
            </div>
            <a href="{{ route('admin.materials.index') }}" class="pill-link" aria-label="Open full materials dashboard">View Materials</a>
        </header>
=======
    .cards .card {
      border: 2px solid #94b9ff !important;
      background: #fff;
      color: #94b9ff !important;
      box-shadow: 0 4px 8px rgba(148, 185, 255, 0.15);
    }
    .cards .card h3,
    .cards .card p,
    .cards .card div {
      color: #94b9ff !important;
    }
    .cards .card:hover {
      box-shadow: 0 6px 18px rgba(148, 185, 255, 0.25);
      background: #f0f6ff;
      border-color: #94b9ff;
    }
    .stock h3 {
      background: #94b9ff !important;
      color: #fff !important;
      padding: 12px 18px;
      border-radius: 10px 10px 0 0;
      margin: 0 -20px 15px -20px;
      font-weight: 700;
      font-size: 18px;
      letter-spacing: 1px;
    }

    .dashboard-actions {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 20px;
    }

    .dashboard-action-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(90deg, #6a2ebc, #3cd5c8);
      color: #fff;
      padding: 12px 18px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 12px 24px -18px rgba(106, 46, 188, 0.8);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dashboard-action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 30px -18px rgba(106, 46, 188, 0.9);
    }

    .dashboard-action-btn i {
      font-size: 18px;
    }
  </style>

        <div class="table-wrapper">
            <table class="table clickable-table" onclick="window.location='{{ route('admin.materials.index') }}'" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Material</th>
                        <th scope="col">Type</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Stock</th>
                        <th scope="col" class="status-col text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @php
                            $stock = $material->inventory->stock_level ?? 0;
                            $reorder = $material->inventory->reorder_level ?? 0;
                            $statusClass = 'ok';
                            $statusLabel = 'In Stock';
                            $badgeClass = 'stock-ok';

                            if ($stock <= 0) {
                                $statusClass = 'out';
                                $statusLabel = 'Out of Stock';
                                $badgeClass = 'stock-critical';
                            } elseif ($stock <= $reorder) {
                                $statusClass = 'low';
                                $statusLabel = 'Low Stock';
                                $badgeClass = 'stock-low';
                            }
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $material->unit }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stock }}</span>
                            </td>
                            <td class="text-center">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No materials available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        // Auto-hide greeting after 4 seconds
        setTimeout(() => {
            const greeting = document.getElementById('greetingMessage');
            if (greeting) {
                greeting.style.opacity = '0';
                setTimeout(() => greeting.remove(), 1000);
            }
        }, 4000);
    </script>
</main>
@endsection
