@extends('layouts.owner.app')

@section('title', 'Materials Management')

@push('styles')
    <link rel="stylesheet" href="css/owner/staffapp.css">
@endpush

@section('content')
@include('layouts.owner.sidebar') 

<section class="main-content">
    <div class="topbar">
        <div class="welcome-text"><strong>Welcome, Owner!</strong></div>

        <div class="topbar-actions">
            <button type="button" class="icon-btn" aria-label="Notifications">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

         <div style="margin-top: 20px;">
        <button onclick="window.history.back();" style="background: none; border: none; padding: 0; cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left" viewBox="0 0 24 24">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <div class="panel">
        <h3>Materials Search</h3>

        <!-- Search Form -->
        <form method="GET" action="{{ route('owner.materials.search') }}">
            <div class="search-wrap">
                <input class="search-input" type="text" name="search" placeholder="Search by item name or category..." value="{{ request()->input('search') }}" />
                <button type="submit" class="search-btn">Search</button>
            </div>
        </form>

        <!-- Materials Table -->
        <div class="table-wrap">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Stock Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @php
                            $stock = $material->inventory->stock_level ?? 0;
                            $reorder = $material->inventory->reorder_level ?? 0;

                            // Determine stock status
                            if ($stock <= 0) {
                                $statusClass = 'out-of-stock';
                                $statusText = 'Out of Stock';
                            } elseif ($stock <= $reorder) {
                                $statusClass = 'low-stock';
                                $statusText = 'Low Stock';
                            } else {
                                $statusClass = 'in-stock';
                                $statusText = 'In Stock';
                            }
                        @endphp
                        <tr>
                            <td>{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $stock }}</td>
                            <td><span class="status {{ $statusClass }}">{{ $statusText }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No materials found matching your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
