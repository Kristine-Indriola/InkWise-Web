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

        <ul class="notification-list">
            @forelse($notifications as $notification)
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
                    <p>No notifications yet. You're all caught up!</p>
                </li>
            @endforelse
        </ul>
    </section>
</main>
@endsection
