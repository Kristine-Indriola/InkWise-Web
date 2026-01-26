<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - InkWise</title>
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

        .success-container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }

        .success-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 3rem 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 1rem;
        }

        .success-message {
            font-size: 1.125rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .order-details {
            background: var(--surface-muted);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .order-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--divider);
        }

        .order-detail-row:last-child {
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
            margin: 0.5rem;
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

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 1rem;
            }

            .success-card {
                padding: 2rem 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <!-- Success Icon -->
            <div class="success-icon">
                ✓
            </div>

            <!-- Success Title -->
            <h1 class="success-title">Order Placed Successfully!</h1>

            <!-- Success Message -->
            <p class="success-message">
                Thank you for your order! We've received your order and will start processing it shortly.
                You will receive an email confirmation with your order details.
            </p>

            <!-- Order Details -->
            <div class="order-details">
                <div class="order-detail-row">
                    <span>Order Number:</span>
                    <span>#{{ $order->order_number }}</span>
                </div>
                <div class="order-detail-row">
                    <span>Order Date:</span>
                    <span>{{ $order->created_at->format('M d, Y') }}</span>
                </div>
                <div class="order-detail-row">
                    <span>Status:</span>
                    <span>{{ ucfirst($order->status) }}</span>
                </div>
                <div class="order-detail-row">
                    <span>Payment Method:</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                </div>
                <div class="order-detail-row">
                    <span>Total Amount:</span>
                    <span>₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="{{ route('customer.my_purchase') }}" class="btn btn-primary">
                    View My Orders
                </a>
                <a href="{{ route('customer.catalog') }}" class="btn btn-secondary">
                    Continue Shopping
                </a>
            </div>

            <!-- Additional Information -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--divider);">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-strong); margin-bottom: 1rem;">
                    What happens next?
                </h3>
                <ul style="text-align: left; color: var(--text-muted); line-height: 1.6;">
                    <li>You will receive an email confirmation with your order details</li>
                    <li>Our team will review and prepare your custom designs</li>
                    <li>You'll receive updates on your order status via email</li>
                    <li>For pickup orders, you'll be notified when your order is ready</li>
                    <li>For delivery orders, tracking information will be provided</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>