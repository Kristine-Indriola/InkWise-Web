@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/notifications.css') }}">
@endpush

@section('title', 'Stock Notifications')

@section('content')
<main class="materials-page admin-page-shell notifications-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Stock Notifications</h1>
            <p class="page-subtitle">Sample inventory alerts for invitations and giveaways - staff focus on order management.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.materials.index') }}" class="pill-link is-active" aria-label="Back to materials list">
                <i class="fi fi-rr-arrow-left"></i>&nbsp;Back to Materials
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-notifications-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info" role="alert">
        <i class="fi fi-rr-info"></i>
        <span>This is sample notification data. Staff members focus on managing customer orders and sending notifications.</span>
    </div>

    <section class="notifications-grid" aria-label="Inventory alerts">
        <article class="notification-card notification-card--warning">
            <header class="notification-card__header">
                <div class="notification-card__icon" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 class="notification-card__title">Low stock</h2>
                    <p class="notification-card__description">Sample materials approaching their reorder level.</p>
                </div>
            </header>

            @if($lowStock->count())
                <div class="notification-table" role="region" aria-live="polite">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Material</th>
                                <th scope="col">Type</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Reorder level</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStock as $material)
                                <tr>
                                    <td>{{ $material->material_name }}</td>
                                    <td>{{ $material->material_type }}</td>
                                    <td><span class="inventory-chip inventory-chip--warning">{{ $material->inventory->stock_level }}</span></td>
                                    <td>{{ $material->inventory->reorder_level }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-warning" disabled>
                                            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                            <span>Sample</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="notification-card__empty">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    All materials are above the reorder threshold.
                </p>
            @endif
        </article>

        <article class="notification-card notification-card--danger">
            <header class="notification-card__header">
                <div class="notification-card__icon" aria-hidden="true">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div>
                    <h2 class="notification-card__title">Out of stock</h2>
                    <p class="notification-card__description">Sample items that need immediate replenishment.</p>
                </div>
            </header>

            @if($outOfStock->count())
                <div class="notification-table" role="region" aria-live="polite">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Material</th>
                                <th scope="col">Type</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Reorder level</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($outOfStock as $material)
                                <tr>
                                    <td>{{ $material->material_name }}</td>
                                    <td>{{ $material->material_type }}</td>
                                    <td><span class="inventory-chip inventory-chip--danger">{{ $material->inventory->stock_level }}</span></td>
                                    <td>{{ $material->inventory->reorder_level }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-danger" disabled>
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            <span>Sample</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="notification-card__empty">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Nothing is out of stock right now.
                </p>
            @endif
        </article>
    </section>

    <section class="notification-card" aria-label="Order notifications">
        <header class="notification-card__header">
            <div class="notification-card__icon" aria-hidden="true">
                <i class="fa-solid fa-shopping-cart"></i>
            </div>
            <div>
                <h2 class="notification-card__title">Order notifications</h2>
                <p class="notification-card__description">Recent order updates and customer notifications.</p>
            </div>
        </header>

        <ul class="notification-list">
            <li class="notification-list__item is-unread">
                <div class="notification-list__content">
                    <p class="notification-list__message">New order received for Wedding Invitations</p>
                    <time class="notification-list__time" datetime="2025-10-04T10:30:00">
                        2 hours ago
                    </time>
                </div>
                <span class="notification-list__status">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    <span>Unread</span>
                </span>
            </li>
            <li class="notification-list__item is-read">
                <div class="notification-list__content">
                    <p class="notification-list__message">Order #1005 completed and ready for delivery</p>
                    <time class="notification-list__time" datetime="2025-10-03T14:20:00">
                        Yesterday
                    </time>
                </div>
                <span class="notification-list__status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span>Read</span>
                </span>
            </li>
            <li class="notification-list__item is-unread">
                <div class="notification-list__content">
                    <p class="notification-list__message">Customer inquiry about Corporate Event Invitations</p>
                    <time class="notification-list__time" datetime="2025-10-03T09:15:00">
                        Yesterday
                    </time>
                </div>
                <span class="notification-list__status">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    <span>Unread</span>
                </span>
            </li>
            <li class="notification-list__item is-read">
                <div class="notification-list__content">
                    <p class="notification-list__message">Payment received for Birthday Party Giveaways</p>
                    <time class="notification-list__time" datetime="2025-10-02T16:45:00">
                        2 days ago
                    </time>
                </div>
                <span class="notification-list__status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span>Read</span>
                </span>
            </li>
        </ul>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-notifications-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }
        });
    </script>
@endpush
