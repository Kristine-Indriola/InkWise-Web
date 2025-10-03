@extends('layouts.admin')

@section('title', 'Dashboard')

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

  <div class="stock">
    <h3>Stock Level</h3>

    <table class="clickable-table" onclick="window.location='{{ route('admin.materials.index') }}'">
      <thead>
        <tr>
          <th>Materials</th>
          <th>Type</th>
          <th>Unit</th>
          <th>Stock Level</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($materials as $material)
          @php
              $stock = $material->inventory->stock_level ?? 0;
              $reorder = $material->inventory->reorder_level ?? 0;
              $status = 'in';
              $statusLabel = 'In Stock';

              if ($stock <= 0) {
                  $status = 'critical';
                  $statusLabel = 'Out of Stock';
              } elseif ($stock <= $reorder) {
                  $status = 'low';
                  $statusLabel = 'Low Stock';
              }
          @endphp

          <tr>
            <td>{{ $material->material_name }}</td>
            <td>{{ $material->material_type }}</td>
            <td>{{ $material->unit }}</td>
            <td>{{ $stock }}</td>
            <td><span class="status {{ $status }}">{{ $statusLabel }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center">No materials available.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <style>
    /* container to center and limit dashboard width */
    .dashboard-container {
      width: 100%;
      max-width: 1500px; 
      margin: 20px auto;
      padding: 0 18px;
      box-sizing: border-box;
    }

    .clickable-table {
      cursor: pointer;
    }
    .clickable-table tbody tr:hover {
      background-color: #f1f1f1;
    }

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

  <script>
    // Auto-hide greeting after 4 seconds
    setTimeout(() => {
        const greeting = document.getElementById('greetingMessage');
        if (greeting) {
            greeting.style.opacity = '0'; // fade out
            setTimeout(() => greeting.remove(), 1000); // remove after fade
        }
    }, 4000);
  </script>

  </div><!-- /.dashboard-container -->

@endsection
