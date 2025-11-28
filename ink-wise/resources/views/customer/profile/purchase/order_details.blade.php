@extends('layouts.customerprofile')

@section('title', 'Order Details')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Order Details</h1>
        <a href="{{ route('customer.my_purchase.topickup') }}" class="text-[#a6b7ff] hover:text-[#8b95e5] font-semibold">
            ← Back to Ready for Pickup
        </a>
    </div>

    @php
        $statusOptions = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['pending', 'processing', 'in_production', 'confirmed', 'completed'];
        $normalizeMetadata = function ($metadata) {
            if (is_array($metadata)) {
                return $metadata;
            }
            if ($metadata instanceof \JsonSerializable) {
                return (array) $metadata;
            }
            if (is_string($metadata) && $metadata !== '') {
                $decoded = json_decode($metadata, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            return [];
        };
        $formatDate = function ($value, $format = 'M d, Y') {
            try {
                if ($value instanceof \Illuminate\Support\Carbon) {
                    return $value->format($format);
                }
                if ($value) {
                    return \Illuminate\Support\Carbon::parse($value)->format($format);
                }
            } catch (\Throwable $e) {
                return null;
            }
            return null;
        };
        $metadata = $normalizeMetadata($order->metadata ?? []);
        $currentStatus = $order->status;
        $currentStatusIndex = array_search($currentStatus, $statusFlow);
    @endphp

    <!-- Order Header -->
    <div class="bg-gray-50 rounded-xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Order #{{ $order->order_number ?? $order->id }}</h2>
                <p class="text-gray-600">Placed on {{ $formatDate($order->created_at, 'M d, Y \a\t g:i A') }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-[#a6b7ff]">₱{{ number_format($order->total_amount ?? 0, 2) }}</div>
                <div class="text-sm text-gray-600">{{ $statusOptions[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus)) }}</div>
            </div>
        </div>

        @if($metadata['status_note'] ?? null)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <div class="font-semibold text-blue-800">Status Note</div>
                        <div class="text-sm text-blue-700 mt-1">{{ $metadata['status_note'] }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order Items -->
        <div class="bg-white border rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Items</h3>
            <div class="space-y-4">
                @forelse($order->items as $item)
                    @php
                        $productName = $item->product_name ?? 'Custom Product';
                        $image = $item->product->image ?? asset('customerimages/image/weddinginvite.png');
                        $quantity = (int) $item->quantity;
                        $paperStock = $item->paperStockSelection->paper_stock_name ?? 'Standard';
                        $unitPrice = $item->unit_price ?? 0;
                        $totalPrice = $item->total_price ?? ($unitPrice * $quantity);
                    @endphp

                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                        <img src="{{ $image }}" alt="{{ $productName }}" class="w-16 h-16 object-cover rounded-lg border">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">{{ $productName }}</div>
                            <div class="text-sm text-gray-600">Quantity: {{ $quantity }} pcs</div>
                            <div class="text-sm text-gray-600">Paper: {{ $paperStock }}</div>
                            <div class="text-sm text-gray-600">₱{{ number_format($unitPrice, 2) }} each</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-800">₱{{ number_format($totalPrice, 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        No items found for this order.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="bg-white border rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Timeline</h3>
            <div class="space-y-4">
                @foreach($statusFlow as $index => $status)
                    @php
                        $statusLabel = $statusOptions[$status] ?? ucfirst(str_replace('_', ' ', $status));
                        $isCompleted = $index <= $currentStatusIndex;
                        $isCurrent = $index === $currentStatusIndex;
                        $activity = $order->activities->where('activity_type', 'status_change')->where('new_value', $status)->first();
                        $timestamp = $activity ? $activity->created_at : null;
                    @endphp

                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            @if($isCompleted)
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @elseif($isCurrent)
                                <div class="w-8 h-8 bg-[#a6b7ff] rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium {{ $isCompleted ? 'text-green-700' : ($isCurrent ? 'text-[#a6b7ff]' : 'text-gray-500') }}">
                                    {{ $statusLabel }}
                                </span>
                                @if($timestamp)
                                    <span class="text-xs text-gray-500">
                                        {{ $formatDate($timestamp, 'M d, Y g:i A') }}
                                    </span>
                                @endif
                            </div>
                            @if($activity && $activity->description)
                                <div class="text-sm text-gray-600 mt-1">{{ $activity->description }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    @if($order->payments->count() > 0)
    <div class="bg-white border rounded-xl p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Information</h3>
        <div class="space-y-3">
            @foreach($order->payments as $payment)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="font-medium text-gray-800">{{ ucfirst($payment->payment_method ?? 'Payment') }}</div>
                        <div class="text-sm text-gray-600">{{ $formatDate($payment->created_at, 'M d, Y g:i A') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold {{ $payment->status === 'paid' ? 'text-green-600' : 'text-gray-600' }}">
                            ₱{{ number_format($payment->amount ?? 0, 2) }}
                        </div>
                        <div class="text-sm {{ $payment->status === 'paid' ? 'text-green-600' : 'text-orange-600' }}">
                            {{ ucfirst($payment->status ?? 'pending') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-gray-800">Total Paid</span>
                <span class="font-bold text-green-600">₱{{ number_format($order->totalPaid(), 2) }}</span>
            </div>
            @if($order->balanceDue() > 0)
                <div class="flex justify-between items-center mt-2">
                    <span class="font-semibold text-gray-800">Balance Due</span>
                    <span class="font-bold text-orange-600">₱{{ number_format($order->balanceDue(), 2) }}</span>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Order Activities -->
    @if($order->activities->count() > 0)
    <div class="bg-white border rounded-xl p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Activities</h3>
        <div class="space-y-3">
            @foreach($order->activities as $activity)
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-[#a6b7ff] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-800">{{ $activity->description ?? 'Activity recorded' }}</div>
                        <div class="text-sm text-gray-600">
                            {{ $formatDate($activity->created_at, 'M d, Y g:i A') }}
                            @if($activity->user_name)
                                by {{ $activity->user_name }}
                            @endif
                        </div>
                        @if($activity->activity_type === 'status_change' && $activity->old_value && $activity->new_value)
                            <div class="text-xs text-gray-500 mt-1">
                                Status changed from "{{ $statusOptions[$activity->old_value] ?? $activity->old_value }}" to "{{ $statusOptions[$activity->new_value] ?? $activity->new_value }}"
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Rating Section -->
    @if($order->status === 'completed' && !$order->rating)
    <div class="bg-white border rounded-xl p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Rate Your Order</h3>
        <form action="{{ route('customer.order-ratings.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="rating" value="{{ $i }}" id="rating-{{ $i }}" class="sr-only">
                        <label for="rating-{{ $i }}" class="cursor-pointer">
                            <svg class="w-8 h-8 text-gray-300 hover:text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
            </div>
            <div>
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                <textarea name="comment" id="comment" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#a6b7ff] focus:border-transparent" placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" class="bg-[#a6b7ff] hover:bg-[#8b95e5] text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                Submit Rating
            </button>
        </form>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 mt-6">
        <a href="{{ route('customer.my_purchase.topickup') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors text-center">
            Back to Orders
        </a>
        @if($order->status === 'confirmed')
            <form action="{{ route('customer.orders.confirm_received', ['order' => $order->id]) }}" method="POST" class="sm:ml-auto">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors w-full sm:w-auto" onclick="return confirm('Mark this order as completed?');">
                    Order Picked Up
                </button>
            </form>
        @endif
    </div>
</div>

<script>
// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('input[name="rating"]');
    const starLabels = document.querySelectorAll('label[for^="rating-"]');

    starLabels.forEach((label, index) => {
        label.addEventListener('click', function() {
            // Reset all stars
            starLabels.forEach(l => {
                const svg = l.querySelector('svg');
                svg.classList.remove('text-yellow-400');
                svg.classList.add('text-gray-300');
            });

            // Fill selected stars
            for(let i = 0; i <= index; i++) {
                const svg = starLabels[i].querySelector('svg');
                svg.classList.remove('text-gray-300');
                svg.classList.add('text-yellow-400');
            }

            // Check the radio button
            stars[index].checked = true;
        });
    });
});
</script>
@endsection