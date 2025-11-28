@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/dashboard.css') }}">
@endpush

<style>
  .dashboard-alert {
    background: rgba(16, 185, 129, 0.1); 
    color: #065f46;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 16px 0 8px 0;
    font-weight: 600;
    text-align: left; 
    opacity: 0;
    animation: fadeInOut 4s ease-in-out;
  }
  @keyframes fadeInOut {
    0% { opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { opacity: 0; }
  }
  
  /* Make summary cards clickable */
  .summary-card {
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
  }
  
  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  }
  
  .summary-card.is-hovered {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  }
  
  .summary-card:active {
    transform: translateY(-2px);
  }
</style>

@section('content')
@php
    $staff = auth()->user();
    $staffName = $staff?->name ?? __('Staff Member');
@endphp

<main class="materials-page admin-page-shell staff-dashboard-page" role="main">
    @if(session('success'))
        <div class="dashboard-alert" role="alert" aria-live="polite">
            {{ session('success') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Welcome back, {{ $staffName }}</h1>
            <p class="page-subtitle">Here’s your personalized overview to keep orders, messages, and materials on track.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.profile.edit') }}" class="pill-link" aria-label="Update profile"><i class="fi fi-rr-user-pen"></i>&nbsp;Profile</a>
            <a href="{{ route('staff.materials.index') }}" class="pill-link is-active" aria-label="Open materials dashboard"><i class="fi fi-rr-box-open"></i>&nbsp;Materials</a>
        </div>
    </header>

        <section class="summary-grid" aria-label="Key performance highlights">
        <a href="{{ route('staff.order_list.index') }}" class="summary-card" data-link-card>
            <div class="summary-card-header">
                <span class="summary-card-label">Total Orders</span>
                <span class="summary-card-chip accent">All time</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($totalOrders ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true">🛒</span>
            </div>
            <span class="summary-card-meta">Orders currently tracked across your assignments.</span>
        </a>
        <a href="{{ route('staff.order_list.index') }}" class="summary-card" data-link-card>
            <div class="summary-card-header">
                <span class="summary-card-label">Assigned Orders</span>
                <span class="summary-card-chip warning">Action needed</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($assignedOrders ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true">📋</span>
            </div>
            <span class="summary-card-meta">Tasks waiting for your updates and confirmations.</span>
        </a>
        <a href="{{ route('staff.customer_profile') }}" class="summary-card" data-link-card>
            <div class="summary-card-header">
                <span class="summary-card-label">Customers</span>
                <span class="summary-card-chip accent">Active</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($customers ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true">🤝</span>
            </div>
            <span class="summary-card-meta">Contacts you're currently supporting.</span>
        </a>
        <a href="{{ route('staff.messages.index') }}" class="summary-card" data-link-card>
            <div class="summary-card-header">
                <span class="summary-card-label">Unread Messages</span>
                <span class="summary-card-chip danger">Follow up</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($unreadMessages ?? 0) }}</span>
                <span class="summary-card-icon" aria-hidden="true">💬</span>
            </div>
            <span class="summary-card-meta">Reach out quickly to keep momentum.</span>
        </a>
    </section>

    <section class="staff-dashboard-section" aria-label="Quick links">
        <header class="section-header">
            <div>
                <h2 class="section-title">Quick Links</h2>
                <p class="section-subtitle">Jump straight to the tools you use most.</p>
            </div>
        </header>
        <div class="staff-quick-links">
            <a href="{{ route('staff.order_list.index') }}" class="staff-quick-link" data-link-card>
                <span class="staff-quick-link__icon" aria-hidden="true"><i class="fi fi-rr-tasks-alt"></i></span>
                <div class="staff-quick-link__copy">
                    <h3>Manage Orders</h3>
                    <p>Review what’s next and keep production flowing.</p>
                </div>
                <span class="staff-quick-link__cta" aria-hidden="true"><i class="fi fi-rr-arrow-right"></i></span>
            </a>
            <a href="{{ route('staff.messages.index') }}" class="staff-quick-link" data-link-card>
                <span class="staff-quick-link__icon" aria-hidden="true"><i class="fi fi-rr-comments"></i></span>
                <div class="staff-quick-link__copy">
                    <h3>Messages</h3>
                    <p>Respond quickly to keep clients in the loop.</p>
                </div>
                <span class="staff-quick-link__cta" aria-hidden="true"><i class="fi fi-rr-arrow-right"></i></span>
            </a>
            <a href="{{ route('staff.materials.index') }}" class="staff-quick-link" data-link-card>
                <span class="staff-quick-link__icon" aria-hidden="true"><i class="fi fi-rr-warehouse"></i></span>
                <div class="staff-quick-link__copy">
                    <h3>Inventory Tracker</h3>
                    <p>Log stock adjustments right after receiving updates.</p>
                </div>
                <span class="staff-quick-link__cta" aria-hidden="true"><i class="fi fi-rr-arrow-right"></i></span>
            </a>
            <a href="{{ route('staff.materials.notification') }}" class="staff-quick-link" data-link-card>
                <span class="staff-quick-link__icon" aria-hidden="true"><i class="fi fi-rr-bell"></i></span>
                <div class="staff-quick-link__copy">
                    <h3>Low Stock Alerts</h3>
                    <p>See which materials are nearing reorder levels.</p>
                </div>
                <span class="staff-quick-link__cta" aria-hidden="true"><i class="fi fi-rr-arrow-right"></i></span>
            </a>
        </div>
    </section>

   

    <section class="staff-dashboard-section staff-dashboard-updates" aria-label="Materials health">
        <header class="section-header">
            <div>
                <h2 class="section-title">Materials Health</h2>
                <p class="section-subtitle">Stay proactive with upcoming replenishment needs.</p>
            </div>
            <div class="section-actions">
                <a href="{{ route('staff.materials.index') }}" class="pill-link"><i class="fi fi-rr-box"></i>&nbsp;View full catalog</a>
            </div>
        </header>
        <div class="staff-dashboard-updates__content">
            <div class="staff-dashboard-updates__copy">
                <h3>Watch your low-stock queue</h3>
                <p>Check low stock alerts regularly so you can flag the admin team before supplies run out.</p>
            </div>
            <a href="{{ route('staff.materials.notification') }}" class="staff-dashboard-updates__cta" data-link-card>
                <span class="staff-dashboard-updates__icon" aria-hidden="true"><i class="fi fi-rr-triangle-warning"></i></span>
                <div class="staff-dashboard-updates__text">
                    <strong>Open notifications</strong>
                    <span>Review materials flagged for replenishment.</span>
                </div>
                <span class="staff-dashboard-updates__arrow" aria-hidden="true"><i class="fi fi-rr-arrow-right"></i></span>
            </a>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-link-card]').forEach(function (card) {
                card.addEventListener('mouseenter', function () {
                    card.classList.add('is-hovered');
                });
                card.addEventListener('mouseleave', function () {
                    card.classList.remove('is-hovered');
                });
                card.addEventListener('focus', function () {
                    card.classList.add('is-hovered');
                });
                card.addEventListener('blur', function () {
                    card.classList.remove('is-hovered');
                });
            });

            const alertBanner = document.querySelector('.dashboard-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.style.opacity = '0';
                    setTimeout(function () {
                        alertBanner.remove();
                    }, 600);
                }, 4000);
            }
        });
    </script>
@endpush
