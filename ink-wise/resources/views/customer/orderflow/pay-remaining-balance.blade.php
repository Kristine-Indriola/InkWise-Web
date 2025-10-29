@extends('layouts.customer')

@section('title', 'Pay Remaining Balance')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-yellow-50 to-orange-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            // Calculate actual remaining balance from payments
            $metadata = $order->metadata ?? [];
            $payments = collect($metadata['payments'] ?? []);
            $paidAmount = $payments->filter(fn($payment) => ($payment['status'] ?? null) === 'paid')->sum(fn($payment) => (float)($payment['amount'] ?? 0));
            $remainingBalance = max(($order->total_amount ?? 0) - $paidAmount, 0);
        @endphp
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Complete Your Payment</h1>
            <p class="text-gray-600">Pay the remaining balance for your order</p>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Order #{{ $order->order_number ?? 'N/A' }}</h2>
                <p class="text-orange-100 text-sm">Remaining Balance Payment</p>
            </div>

            <div class="p-6">
                <!-- Order Items -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
                    <div class="space-y-3">
                        @forelse($order->items ?? [] as $item)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $item->product_name ?? 'Product' }}</p>
                                <p class="text-sm text-gray-500">Quantity: {{ $item->quantity ?? 1 }}</p>
                            </div>
                            <p class="font-medium text-gray-900">₱{{ number_format($item->total_price ?? 0, 2) }}</p>
                        </div>
                        @empty
                        <p class="text-gray-500">No items found</p>
                        @endforelse
                    </div>
                </div>

                <!-- Payment Breakdown -->
                <div class="border-t pt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Summary</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total Order Amount:</span>
                            <span class="font-medium">₱{{ number_format($order->total_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Amount Paid:</span>
                            <span class="font-medium text-green-600">-₱{{ number_format($paidAmount, 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between text-lg font-semibold text-gray-900">
                                <span>Remaining Balance:</span>
                                <span class="text-orange-600">₱{{ number_format($remainingBalance, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fulfillment Info -->
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Fulfillment Method</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        @if($order->fulfillment_method ?? 'delivery' === 'pickup')
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Pickup at Store</p>
                                <p class="text-sm text-gray-600">Ready for pickup at our store location</p>
                            </div>
                        </div>
                        @else
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Home Delivery</p>
                                <p class="text-sm text-gray-600">{{ $order->address ?? 'Delivery address not available' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Pay Remaining Balance</h2>
                <p class="text-green-100 text-sm">Complete your order by paying the remaining amount</p>
            </div>

            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="text-3xl font-bold text-gray-900 mb-2">
                        ₱{{ number_format($remainingBalance, 2) }}
                    </div>
                    <p class="text-gray-600">Amount to pay now</p>
                </div>

                <!-- Payment Method -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Method</h3>
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">GCash Payment</p>
                                <p class="text-sm text-gray-600">Secure payment via GCash</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Button -->
                <div class="text-center">
                    <button id="payRemainingBalanceBtn"
                            class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Pay Remaining Balance
                    </button>
                    <p class="text-sm text-gray-500 mt-3">
                        You will be redirected to GCash to complete the payment securely
                    </p>
                </div>

                <!-- Back to Orders Link -->
                <div class="text-center mt-6 pt-6 border-t">
                    <a href="{{ route('customer.my_purchase.toship') }}"
                       class="text-orange-600 hover:text-orange-800 text-sm font-medium">
                        ← Back to My Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment Status Messages -->
        <div id="paymentAlert" class="mt-4" style="display: none;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const payButton = document.getElementById('payRemainingBalanceBtn');
    const paymentAlert = document.getElementById('paymentAlert');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const showPaymentMessage = (type, message) => {
        if (!paymentAlert) return;
        paymentAlert.className = `p-4 rounded-lg ${type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                                                  type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                                                  'bg-blue-50 text-blue-800 border border-blue-200'}`;
        paymentAlert.innerHTML = message;
        paymentAlert.style.display = 'block';
        paymentAlert.scrollIntoView({ behavior: 'smooth' });
    };

    const clearPaymentMessage = () => {
        if (!paymentAlert) return;
        paymentAlert.style.display = 'none';
        paymentAlert.innerHTML = '';
    };

    if (payButton) {
        payButton.addEventListener('click', async () => {
            payButton.disabled = true;
            payButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;

            clearPaymentMessage();

            try {
                const response = await fetch('{{ route("payment.gcash.create") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        order_id: '{{ $order->id ?? 0 }}',
                        amount: {{ $remainingBalance }},
                        mode: 'balance_payment',
                        name: '{{ $order->customerOrder->name ?? "Customer" }}',
                        email: '{{ $order->customerOrder->email ?? "" }}',
                        phone: '{{ $order->customerOrder->phone ?? "" }}',
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to start the GCash payment.');
                }

                if (data.redirect_url) {
                    showPaymentMessage('success', 'Redirecting you to GCash to complete the payment...');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                } else {
                    showPaymentMessage('success', data.message ?? 'GCash payment initialized.');
                }

            } catch (err) {
                console.error('Payment error:', err);
                showPaymentMessage('error', err.message ?? 'Unable to start the GCash payment. Please try again.');

                payButton.disabled = false;
                payButton.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    Pay Remaining Balance
                `;
            }
        });
    }

    // Check for payment status on page load (if returning from GCash)
    const urlParams = new URLSearchParams(window.location.search);
    const paymentStatus = urlParams.get('status');

    if (paymentStatus === 'success') {
        showPaymentMessage('success', 'Payment completed successfully! Your order is now fully paid.');
    } else if (paymentStatus === 'failed') {
        showPaymentMessage('error', 'Payment was not completed. Please try again.');
    }
});
</script>

@endsection
