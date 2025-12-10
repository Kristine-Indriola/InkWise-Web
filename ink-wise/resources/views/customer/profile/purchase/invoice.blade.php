@extends('layouts.customerprofile')

@section('title', 'Invoice')

@section('content')
<div class="bg-white rounded-2xl shadow p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Invoice</h1>
        <a href="{{ route('customer.my_purchase.completed') }}" class="text-[#a6b7ff] hover:text-[#8b95e5] font-semibold">
            ← Back to Completed Orders
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
        $statusFlow = ['draft', 'pending', 'processing', 'in_production', 'confirmed', 'completed'];
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

    <!-- Invoice Header -->
    <div class="border-b-2 border-gray-200 pb-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $settings->contact_company ?? 'InkWise' }}</h2>
                <p class="text-gray-600">Custom Printing Services</p>
                @if($settings->contact_address)
                    <p class="text-gray-600">{{ $settings->contact_address }}</p>
                @else
                    <p class="text-gray-600">123 Print Street, Design City</p>
                @endif
                @if($settings->contact_email)
                    <p class="text-gray-600">Email: {{ $settings->contact_email }}</p>
                @else
                    <p class="text-gray-600">Email: info@inkwise.com</p>
                @endif
                @if($settings->contact_phone)
                    <p class="text-gray-600">Phone: {{ $settings->contact_phone }}</p>
                @else
                    <p class="text-gray-600">Phone: (123) 456-7890</p>
                @endif
            </div>
            <div class="text-right">
                <h3 class="text-xl font-semibold text-gray-800">INVOICE</h3>
                <p class="text-gray-600">Invoice #: {{ $order->order_number ?? $order->id }}</p>
                <p class="text-gray-600">Date: {{ $formatDate($order->created_at, 'M d, Y') }}</p>
                <p class="text-gray-600">Status: {{ $statusOptions[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus)) }}</p>
            </div>
        </div>

        <!-- Bill To -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Bill To:</h4>
                <div class="text-gray-700">
                    <p class="font-medium">{{ $order->customer->first_name ?? '' }} {{ $order->customer->middle_name ?? '' }} {{ $order->customer->last_name ?? '' }}</p>
                    <p>{{ $order->customer->email ?? '' }}</p>
                    <p>{{ $order->customer->contact_number ?? '' }}</p>
                </div>
            </div>
            <div class="md:text-right">
                <h4 class="font-semibold text-gray-800 mb-2">Ship To:</h4>
                <div class="text-gray-700">
                    @if($order->customer->user && $order->customer->user->addresses->first())
                        <p>{{ $order->customer->user->addresses->first()->street }}</p>
                        <p>{{ $order->customer->user->addresses->first()->city }}, {{ $order->customer->user->addresses->first()->province }} {{ $order->customer->user->addresses->first()->postal_code }}</p>
                    @else
                        <p>Same as billing address</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="mb-6">
        <h4 class="font-semibold text-gray-800 mb-4">Order Items</h4>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-4 py-2 text-left font-semibold">Item</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-semibold">Description</th>
                        <th class="border border-gray-300 px-4 py-2 text-center font-semibold">Qty</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-semibold">Unit Price</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        @php
                            $productName = $item->product_name ?? 'Custom Product';
                            $quantity = (int) $item->quantity;
                            $paperStock = $item->paperStockSelection->paperStock->name ?? 'Standard';
                            $unitPrice = $item->unit_price ?? 0;
                            $totalPrice = $item->total_price ?? ($unitPrice * $quantity);
                        @endphp
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->product->image ?? asset('customerimages/image/weddinginvite.png') }}" alt="{{ $productName }}" class="w-12 h-12 object-cover rounded border">
                                    <span class="font-medium">{{ $productName }}</span>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-gray-600">
                                Paper Stock: {{ $paperStock }}
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-center">{{ $quantity }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">₱{{ number_format($unitPrice, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right font-medium">₱{{ number_format($totalPrice, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                                No items found for this order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="flex justify-end mb-6">
        <div class="w-64">
            <div class="border-t-2 border-gray-300 pt-4">
                <div class="flex justify-between mb-2">
                    <span class="font-semibold">Subtotal:</span>
                    <span>₱{{ number_format($order->total_amount ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="font-semibold">Tax:</span>
                    <span>₱0.00</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t border-gray-300 pt-2">
                    <span>Total:</span>
                    <span>₱{{ number_format($order->total_amount ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    @if($order->payments->count() > 0)
    <div class="mb-6">
        <h4 class="font-semibold text-gray-800 mb-4">Payment Information</h4>
        <div class="space-y-2">
            @foreach($order->payments as $payment)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <span class="font-medium">{{ ucfirst($payment->payment_method ?? 'Payment') }}</span>
                        <span class="text-sm text-gray-600 ml-2">{{ $formatDate($payment->created_at, 'M d, Y') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold {{ $payment->status === 'paid' ? 'text-green-600' : 'text-gray-600' }}">
                            ₱{{ number_format($payment->amount ?? 0, 2) }}
                        </span>
                        <div class="text-sm {{ $payment->status === 'paid' ? 'text-green-600' : 'text-orange-600' }}">
                            {{ ucfirst($payment->status ?? 'pending') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="border-t-2 border-gray-200 pt-6 text-center text-gray-600">
        <p>Thank you for your business!</p>
        <p class="text-sm mt-2">For any questions about this invoice, please contact us at {{ $settings->contact_email ?? 'info@inkwise.com' }}</p>
    </div>

    <!-- Print Button -->
    <div class="flex justify-center mt-6">
        <button onclick="window.print()" class="bg-[#a6b7ff] hover:bg-[#8b95e5] text-white px-6 py-2 rounded-lg font-semibold transition-colors">
            Print Invoice
        </button>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        font-size: 12px;
    }
    .bg-white {
        background: white !important;
    }
}
</style>
@endsection