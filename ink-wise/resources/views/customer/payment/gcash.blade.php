<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment - InkWise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }

        .payment-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .gcash-logo {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #0066cc, #00cc66);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .payment-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 1rem;
        }

        .payment-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .payment-details {
            background: var(--surface-muted);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--divider);
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            color: var(--text-strong);
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
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background-color: #0066cc;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0052a3;
        }

        .btn-secondary {
            background-color: var(--surface-muted);
            color: var(--text-default);
        }

        .btn-secondary:hover {
            background-color: var(--divider);
        }

        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 1rem;
            }

            .payment-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <!-- GCash Logo -->
            <div class="gcash-logo">
                GCash
            </div>

            <!-- Payment Title -->
            <h1 class="payment-title">Complete Your Payment</h1>

            <!-- Payment Amount -->
            <div class="payment-amount">₱{{ number_format($order->total_amount, 2) }}</div>

            <!-- Payment Details -->
            <div class="payment-details">
                <div class="detail-row">
                    <span>Order Number:</span>
                    <span>#{{ $order->order_number }}</span>
                </div>
                <div class="detail-row">
                    <span>Payment Method:</span>
                    <span>GCash</span>
                </div>
                <div class="detail-row">
                    <span>Amount:</span>
                    <span>₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div style="margin-bottom: 2rem; color: var(--text-muted); line-height: 1.6;">
                <p>You will be redirected to GCash to complete your payment securely.</p>
                <p style="font-size: 0.875rem; margin-top: 0.5rem;">
                    This is a demo payment gateway. In a real application, you would be redirected to the actual GCash payment page.
                </p>
            </div>

            <!-- Action Buttons -->
            <div>
                <button type="button" class="btn btn-primary" id="payNowBtn">
                    Pay with GCash Now
                </button>
                <a href="{{ route('customer.checkout.payment.cancel', ['orderId' => $order->id]) }}" class="btn btn-secondary">
                    Cancel Payment
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const payNowBtn = document.getElementById('payNowBtn');

            payNowBtn.addEventListener('click', () => {
                payNowBtn.disabled = true;
                payNowBtn.innerHTML = '<span class="loading"></span> Processing...';

                // Simulate payment processing delay
                setTimeout(() => {
                    // Redirect to payment processing
                    window.location.href = '{{ $paymentUrl }}';
                }, 2000);
            });
        });
    </script>
</body>
</html>