@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="notifications-container">
     <link rel="stylesheet" href="{{ asset('css/admin-css/notifications.css') }}">
    <h1>🔔 System Notifications</h1>

    {{-- Low Stock Section --}}
    <div class="notif-section low">
        <h2>⚠️ Low Stock</h2>
        @if($lowStock->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Type</th>
                        <th>Stock Level</th>
                        <th>Reorder Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStock as $material)
                        <tr>
                            <td>{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $material->inventory->stock_level }}</td>
                            <td>{{ $material->inventory->reorder_level }}</td>
                            <td>
                                <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning">Restock</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">✅ No low stock materials.</p>
        @endif
    </div>

    {{-- Out of Stock Section --}}
    <div class="notif-section out">
        <h2>❌ Out of Stock</h2>
        @if($outOfStock->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Type</th>
                        <th>Stock Level</th>
                        <th>Reorder Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outOfStock as $material)
                        <tr>
                            <td>{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $material->inventory->stock_level }}</td>
                            <td>{{ $material->inventory->reorder_level }}</td>
                            <td>
                                <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-danger">Restock</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">✅ No out of stock materials.</p>
        @endif
    </div>

    {{-- General Notifications (Staff approvals, etc.) --}}
    <div class="notif-section general">
        <h2>📢 General Notifications</h2>
        @if(auth()->user()->unreadNotifications->count())
            <ul class="general-list">
                @foreach(auth()->user()->unreadNotifications as $notification)
                    <li>
                        <span class="notif-message">
                            {{ $notification->data['message'] ?? 'New notification' }}
                        </span>
                        <small class="notif-time">{{ $notification->created_at->diffForHumans() }}</small>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="empty">✅ No new system notifications.</p>
        @endif
    </div>
</div>
@endsection
