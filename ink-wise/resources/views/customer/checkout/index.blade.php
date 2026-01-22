<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - InkWise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/template.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
    <style>
        :root {
            --page-gradient: radial-gradient(circle at 20% 20%, rgba(148, 163, 184, 0.12), transparent 35%),
                              radial-gradient(circle at 80% 0%, rgba(99, 102, 241, 0.08), transparent 32%),
                              #f7f9fc;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --surface-strong: #0f172a;
            --divider: rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 22px 70px rgba(15, 23, 42, 0.10);
            --shadow-md: 0 12px 40px rgba(15, 23, 42, 0.08);
            --text-strong: #0f172a;
            --text-default: #1f2937;
            --text-muted: #475569;
            --text-soft: #94a3b8;
            --accent: #0f172a;
            --accent-dark: #0b1220;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: var(--page-gradient);
            color: var(--text-default);
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-top: 2rem;
        }

        .order-summary {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            height: fit-content;
        }

        .checkout-form {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--divider);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-strong);
            margin-bottom: 1rem;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--divider);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .radio-option:hover {
            border-color: var(--accent);
        }

        .radio-option.selected {
            border-color: var(--accent);
            background-color: rgba(15, 23, 42, 0.05);
        }

        .radio-input {
            margin-right: 0.75rem;
        }

        .radio-label {
            font-weight: 500;
            color: var(--text-default);
        }

        .radio-description {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-default);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--divider);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .cart-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--divider);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            object-fit: cover;
            background-color: var(--surface-muted);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-strong);
            margin-bottom: 0.25rem;
        }

        .item-meta {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--accent);
        }

        .preview-thumbnails {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .preview-thumb {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .preview-thumb:hover {
            border-color: var(--accent);
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
        }

        .totals-row.total {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-strong);
            border-top: 2px solid var(--divider);
            margin-top: 0.75rem;
            padding-top: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--accent-dark);
        }

        .btn-secondary {
            background-color: var(--surface-muted);
            color: var(--text-default);
        }

        .btn-secondary:hover {
            background-color: var(--divider);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .alert-success {
            background-color: rgba(22, 163, 74, 0.1);
            border: 1px solid rgba(22, 163, 74, 0.2);
            color: var(--success);
        }

        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .checkout-container {
                padding: 1rem;
            }

            .order-summary {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header style="background: var(--surface); border-bottom: 1px solid var(--divider); padding: 1rem 0;">
        <div class="checkout-container">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="{{ route('customer.catalog') }}" style="font-size: 1.5rem; font-weight: 700; color: var(--accent); text-decoration: none;">
                        ← Back to Catalog
                    </a>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                    InkWise Checkout
                </div>
                <div></div>
            </div>
        </div>
    </header>

    <div class="checkout-container">
        <!-- Alerts -->
        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="checkout-grid">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form id="checkoutForm" method="POST" action="{{ route('customer.cart.checkout.process') }}">
                    @csrf

                    <!-- Shipping Method -->
                    <div class="form-section">
                        <h3 class="section-title">Shipping Method</h3>
                        <div class="radio-group">
                            <div class="radio-option selected" data-value="pickup">
                                <input type="radio" name="shipping_method" value="pickup" class="radio-input" checked>
                                <div>
                                    <div class="radio-label">Pickup at Store</div>
                                    <div class="radio-description">Free pickup at our store location</div>
                                </div>
                            </div>
                            <div class="radio-option" data-value="delivery">
                                <input type="radio" name="shipping_method" value="delivery" class="radio-input">
                                <div>
                                    <div class="radio-label">Home Delivery</div>
                                    <div class="radio-description">Delivered to your address (additional fees apply)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address (Hidden by default) -->
                    <div class="form-section" id="shippingAddressSection" style="display: none;">
                        <h3 class="section-title">Shipping Address</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="shipping_address[full_name]" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="shipping_address[email]" class="form-input" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="shipping_address[phone]" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Person (Optional)</label>
                                <input type="text" name="shipping_address[contact_person]" class="form-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address Line 1 *</label>
                            <input type="text" name="shipping_address[address_line_1]" class="form-input" placeholder="Street address, building, apartment" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">City *</label>
                                <input type="text" name="shipping_address[city]" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Province *</label>
                                <input type="text" name="shipping_address[province]" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Postal Code *</label>
                                <input type="text" name="shipping_address[postal_code]" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3 class="section-title">Payment Method</h3>
                        <div class="radio-group">
                            <div class="radio-option selected" data-value="gcash">
                                <input type="radio" name="payment_method" value="gcash" class="radio-input" checked>
                                <div>
                                    <div class="radio-label">GCash</div>
                                    <div class="radio-description">Pay instantly with GCash</div>
                                </div>
                            </div>
                            <div class="radio-option" data-value="cod">
                                <input type="radio" name="payment_method" value="cod" class="radio-input">
                                <div>
                                    <div class="radio-label">Cash on Delivery</div>
                                    <div class="radio-description">Pay when you receive your order</div>
                                </div>
                            </div>
                            <div class="radio-option" data-value="bank_transfer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="radio-input">
                                <div>
                                    <div class="radio-label">Bank Transfer</div>
                                    <div class="radio-description">Direct bank transfer payment</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="form-section">
                        <h3 class="section-title">Special Instructions (Optional)</h3>
                        <div class="form-group">
                            <textarea name="special_instructions" class="form-input" rows="3" placeholder="Any special requests or instructions for your order..."></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <a href="{{ route('customer.cart') }}" class="btn btn-secondary">Back to Cart</a>
                        <button type="submit" class="btn btn-primary" id="placeOrderBtn">
                            Place Order
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-strong); margin-bottom: 1.5rem;">
                    Order Summary
                </h3>

                <!-- Cart Items -->
                <div style="margin-bottom: 2rem;">
                    @foreach($cartItems as $item)
                        <div class="cart-item">
                            <img src="{{ $item->product->image ?? asset('images/placeholder.png') }}"
                                 alt="{{ $item->product->name ?? 'Product' }}"
                                 class="item-image">
                            <div class="item-details">
                                <div class="item-name">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                <div class="item-meta">Quantity: {{ $item->quantity }}</div>
                                @if(!empty($item->metadata['front_preview']) || !empty($item->metadata['back_preview']))
                                    <div class="preview-thumbnails">
                                        @if(!empty($item->metadata['front_preview']))
                                            <img src="{{ $item->metadata['front_preview'] }}"
                                                 alt="Front Preview"
                                                 class="preview-thumb"
                                                 onclick="showPreviewModal('{{ $item->metadata['front_preview'] }}', 'Front Design')">
                                        @endif
                                        @if(!empty($item->metadata['back_preview']))
                                            <img src="{{ $item->metadata['back_preview'] }}"
                                                 alt="Back Preview"
                                                 class="preview-thumb"
                                                 onclick="showPreviewModal('{{ $item->metadata['back_preview'] }}', 'Back Design')">
                                        @endif
                                    </div>
                                @endif
                                <div class="item-price">₱{{ number_format($item->total_price, 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Totals -->
                <div>
                    <div class="totals-row">
                        <span>Subtotal</span>
                        <span>₱{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="totals-row">
                        <span>Tax (12%)</span>
                        <span>₱{{ number_format($taxAmount, 2) }}</span>
                    </div>
                    <div class="totals-row">
                        <span>Shipping</span>
                        <span>₱{{ number_format($shippingFee, 2) }}</span>
                    </div>
                    <div class="totals-row total">
                        <span>Total</span>
                        <span>₱{{ number_format($totalAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); max-width: 500px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 id="previewTitle" style="margin: 0; font-size: 1.25rem; font-weight: 600;"></h3>
                <button onclick="closePreviewModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <img id="previewImage" src="" alt="" style="width: 100%; border-radius: var(--radius-md);">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Shipping method selection
            const shippingOptions = document.querySelectorAll('input[name="shipping_method"]');
            const shippingAddressSection = document.getElementById('shippingAddressSection');

            shippingOptions.forEach(option => {
                option.addEventListener('change', (e) => {
                    // Update visual selection
                    document.querySelectorAll('.radio-option[data-value]').forEach(el => {
                        el.classList.remove('selected');
                    });
                    e.target.closest('.radio-option').classList.add('selected');

                    // Show/hide shipping address
                    if (e.target.value === 'delivery') {
                        shippingAddressSection.style.display = 'block';
                        // Make address fields required
                        shippingAddressSection.querySelectorAll('input[required]').forEach(input => {
                            input.required = true;
                        });
                    } else {
                        shippingAddressSection.style.display = 'none';
                        // Remove required from address fields
                        shippingAddressSection.querySelectorAll('input[required]').forEach(input => {
                            input.required = false;
                        });
                    }
                });
            });

            // Payment method selection
            const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
            paymentOptions.forEach(option => {
                option.addEventListener('change', (e) => {
                    // Update visual selection
                    document.querySelectorAll('.radio-option[data-value]').forEach(el => {
                        el.classList.remove('selected');
                    });
                    e.target.closest('.radio-option').classList.add('selected');
                });
            });

            // Form submission
            const checkoutForm = document.getElementById('checkoutForm');
            const placeOrderBtn = document.getElementById('placeOrderBtn');

            checkoutForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

                if (paymentMethod === 'gcash') {
                    // Handle GCash payment with AJAX
                    await handleGCashPayment();
                } else {
                    // Handle other payment methods with standard form submission
                    await handleStandardCheckout();
                }
            });

            async function handleGCashPayment() {
                placeOrderBtn.disabled = true;
                placeOrderBtn.innerHTML = '<span class="loading"></span> Processing GCash Payment...';

                try {
                    // First, process the order
                    const formData = new FormData(checkoutForm);
                    const orderResponse = await fetch(checkoutForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        }
                    });

                    const orderResult = await orderResponse.json();

                    if (!orderResult.success) {
                        throw new Error(orderResult.message || 'Failed to create order');
                    }

                    // Now create GCash payment
                    const paymentData = {
                        name: formData.get('shipping_address[full_name]') || 'Customer',
                        email: formData.get('shipping_address[email]') || 'customer@example.com',
                        phone: formData.get('shipping_address[phone]') || '',
                        mode: 'full', // or 'deposit' based on your logic
                        amount: {{ $totalAmount }}
                    };

                    const paymentResponse = await fetch('{{ route("payment.gcash.create") }}', {
                        method: 'POST',
                        body: JSON.stringify(paymentData),
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        }
                    });

                    const paymentResult = await paymentResponse.json();

                    if (paymentResult.success) {
                        if (paymentResult.redirect_url) {
                            window.location.href = paymentResult.redirect_url;
                        } else {
                            window.location.href = `{{ route('customer.order.success', ':orderId') }}`.replace(':orderId', orderResult.order_id);
                        }
                    } else {
                        throw new Error(paymentResult.message || 'Payment failed');
                    }

                } catch (error) {
                    console.error('GCash payment error:', error);
                    alert('Payment failed: ' + error.message);

                    placeOrderBtn.disabled = false;
                    placeOrderBtn.textContent = 'Place Order';
                }
            }

            async function handleStandardCheckout() {
                placeOrderBtn.disabled = true;
                placeOrderBtn.innerHTML = '<span class="loading"></span> Processing...';

                try {
                    const formData = new FormData(checkoutForm);
                    const response = await fetch(checkoutForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        if (result.payment_url) {
                            // Redirect to payment gateway
                            window.location.href = result.payment_url;
                        } else {
                            // For COD or other methods without payment URL
                            window.location.href = `{{ route('customer.order.success', ':orderId') }}`.replace(':orderId', result.order_id);
                        }
                    } else {
                        // Show errors
                        let errorMessage = result.message || 'An error occurred while processing your order.';
                        if (result.errors) {
                            const errorList = Object.values(result.errors).flat();
                            errorMessage += '\n' + errorList.join('\n');
                        }
                        alert(errorMessage);

                        placeOrderBtn.disabled = false;
                        placeOrderBtn.textContent = 'Place Order';
                    }
                } catch (error) {
                    console.error('Checkout error:', error);
                    alert('An error occurred while processing your order. Please try again.');

                    placeOrderBtn.disabled = false;
                    placeOrderBtn.textContent = 'Place Order';
                }
            }
        });

        // Preview modal functions
        function showPreviewModal(imageSrc, title) {
            document.getElementById('previewImage').src = imageSrc;
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewModal').style.display = 'flex';
        }

        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('previewModal').addEventListener('click', (e) => {
            if (e.target.id === 'previewModal') {
                closePreviewModal();
            }
        });
    </script>
</body>
</html>