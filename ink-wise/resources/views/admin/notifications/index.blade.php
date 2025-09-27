@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="notifications-container">
    <h1>🔔 All Notifications</h1>

    <ul>
        @forelse($notifications as $notification)
            <li>
                {{ $notification->data['message'] ?? 'New notification' }}
                <small>{{ $notification->created_at->diffForHumans() }}</small>
            </li>
        @empty
            <p class="empty">✅ No notifications.</p>
        @endforelse
    </ul>
</div>
@endsection
