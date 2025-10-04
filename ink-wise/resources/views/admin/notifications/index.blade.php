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
            <h1 class="page-title">All Notifications</h1>
            <p class="page-subtitle">Review recent system alerts and updates delivered to your account.</p>
        </div>
    </header>

    <section class="notification-card" aria-label="Notifications list">
        <header class="notification-card__header">
            <div class="notification-card__icon" aria-hidden="true">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h2 class="notification-card__title">Latest activity</h2>
                <p class="notification-card__description">Messages, approvals, and reminders sent to your admin inbox.</p>
            </div>
        </header>

        @php
            $defaultNotificationRoutes = [
                'StaffApprovedNotification' => route('admin.users.index'),
                'NewStaffCreated' => route('owner.staff.index'),
                'StockNotification' => route('admin.inventory.index'),
            ];
        @endphp

        <ul class="notification-list" id="notificationsList">
            @forelse($notifications as $notification)
                @php
                    $isRead = !is_null($notification->read_at);
                    $targetUrl = $notification->data['url'] ?? null;

                    if (! $targetUrl) {
                        $type = class_basename($notification->type);
                        $targetUrl = $defaultNotificationRoutes[$type] ?? null;
                    }
                @endphp
                <li class="notification-list__item {{ $isRead ? 'is-read' : 'is-unread' }}">
                    <a href="{{ $targetUrl ?? '#' }}"
                       class="notification-list__link"
                       data-notification-id="{{ $notification->id }}"
                       data-read-url="{{ route('notifications.read', $notification->id) }}"
                       data-target-url="{{ $targetUrl ?? '' }}">
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
                    </a>
                </li>
            @empty
                <li class="notification-list__empty">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <p>No notifications yet. You're all caught up!</p>
                </li>
            @endforelse
        </ul>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const list = document.getElementById('notificationsList');

    if (!list) return;

    list.addEventListener('click', async (event) => {
        const link = event.target.closest('.notification-list__link');
        if (!link) return;

        const targetUrl = link.dataset.targetUrl;
        if (!targetUrl) return;

        event.preventDefault();

        const readUrl = link.dataset.readUrl;

        if (readUrl) {
            try {
                await fetch(readUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Failed to mark notification as read', error);
            }
        }

        window.location.href = targetUrl;
    });
});
</script>
@endpush
