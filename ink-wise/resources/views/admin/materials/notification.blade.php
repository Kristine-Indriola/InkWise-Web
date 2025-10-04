@extends('layouts.admin')

@section('title', 'Notifications')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/notifications.css') }}">
@endpush

@section('content')
<main class="admin-page-shell notifications-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">System Notifications</h1>
            <p class="page-subtitle">Inventory alerts and administrative updates that require your attention.</p>
        </div>
    </header>

    <section class="notifications-grid" aria-label="Inventory alerts">
        <article class="notification-card notification-card--warning">
            <header class="notification-card__header">
                <div class="notification-card__icon" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 class="notification-card__title">Low stock</h2>
                    <p class="notification-card__description">Materials approaching their reorder level across your inventory.</p>
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
                                        <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                            <span>Restock</span>
                                        </a>
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
                    <p class="notification-card__description">Items that need immediate replenishment to avoid delays.</p>
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
                                        <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-danger">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            <span>Restock</span>
                                        </a>
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

    <section class="notification-card" aria-label="General notifications">
        <header class="notification-card__header">
            <div class="notification-card__icon" aria-hidden="true">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h2 class="notification-card__title">General notifications</h2>
                <p class="notification-card__description">Account approvals, staff invites, and other system updates.</p>
            </div>
        </header>

        @php
            $generalNotifications = auth()->user()->notifications;
        @endphp

        <ul class="notification-list">
            @forelse($generalNotifications as $notification)
                @php
                    $isRead = !is_null($notification->read_at);
                @endphp
                <li class="notification-list__item {{ $isRead ? 'is-read' : 'is-unread' }}">
                    <div class="notification-list__content">
                        <p class="notification-list__message">{{ $notification->data['message'] ?? 'New notification' }}</p>
                        <time class="notification-list__time" datetime="{{ $notification->created_at->toIso8601String() }}">
                            {{ $notification->created_at->diffForHumans() }}
                        </time>
                    </div>
                    <span class="notification-list__status">
                        <i class="fa-solid {{ $isRead ? 'fa-circle-check' : 'fa-envelope' }}" aria-hidden="true"></i>
                        <span>{{ $isRead ? 'Read' : 'Unread' }}</span>
                    </span>
                </li>
            @empty
                <li class="notification-list__empty">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <p>No general notifications right now.</p>
                </li>
            @endforelse
        </ul>
    </section>
</main>
@endsection
