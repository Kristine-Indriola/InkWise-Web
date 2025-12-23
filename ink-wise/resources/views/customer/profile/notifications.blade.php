@extends('layouts.customerprofile')

@section('title', 'Notifications')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h2 class="text-xl font-semibold mb-6">Notifications</h2>

    @php
        $notifications = auth()->user()->notifications;
    @endphp

    @if($notifications->count() > 0)
        <div class="space-y-4">
            @foreach($notifications as $notification)
                @php
                    $isRead = !is_null($notification->read_at);
                    $data = $notification->data;
                @endphp
                <div class="border rounded-lg p-4 {{ $isRead ? 'bg-gray-50' : 'bg-blue-50 border-blue-200' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            @php
                                $statusRoutes = [
                                    'in_production' => route('customer.my_purchase.inproduction'),
                                    'confirmed' => route('customer.my_purchase.toship'),
                                    'to_receive' => route('customer.my_purchase.toreceive'),
                                    'completed' => route('customer.my_purchase.completed'),
                                ];
                                $redirectUrl = $statusRoutes[$data['new_status']] ?? route('customer.my_purchase');
                                $readUrl = route('customer.notifications.read', $notification->id) . '?redirect=' . urlencode($redirectUrl);
                            @endphp
                            <a href="{{ $readUrl }}" class="block text-gray-800 {{ $isRead ? '' : 'font-semibold' }} hover:text-blue-600">
                                {{ $data['message'] ?? 'New notification' }}
                            </a>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!$isRead)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    New
                                </span>
                            @endif
                            <form method="GET" action="{{ route('customer.notifications.read', $notification->id) }}" class="inline">
                                <button type="submit" class="text-sm text-gray-600 hover:text-gray-800 {{ $isRead ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isRead ? 'disabled' : '' }}>
                                    {{ $isRead ? 'Read' : 'Mark as Read' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 12.683A17.925 17.925 0 0112 21c7.962 0 12-1.21 12-2.683m-12 2.683a17.925 17.925 0 01-7.132-8.317M12 21c4.411 0 8-4.03 8-9s-3.589-9-8-9-8 4.03-8 9a9.06 9.06 0 001.832 5.683L4 21l4.868-8.317z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
            <p class="mt-1 text-sm text-gray-500">You don't have any notifications yet.</p>
        </div>
    @endif
</div>
@endsection
