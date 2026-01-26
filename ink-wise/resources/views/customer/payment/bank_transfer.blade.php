<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Payment - InkWise</title>
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
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }

        .payment-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .bank-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .payment-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 1rem;
            text-align: center;
        }

        .payment-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .instructions {
            background: var(--surface-muted);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .instruction-title {
            font-weight: 600;
            color: var(--text-strong);
            margin-bottom: 1rem;
        }

        .instruction-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .instruction-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
        }

        .instruction-item::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: var(--success);
            font-weight: bold;
        }

        .bank-details {
            background: var(--warning);
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            color: var(--text-strong);
        }

        .copy-btn {
            background: none;
            border: 1px solid var(--divider);
            border-radius: var(--radius-md);
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .copy-btn:hover {
            background: var(--divider);
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

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 1rem;
            }

            .payment-card {
                padding: 1.5rem;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .copy-btn {
                margin-left: 0;
                margin-top: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <!-- Bank Icon -->
            <div class="bank-icon">
                🏦
            </div>

            <!-- Payment Title -->
            <h1 class="payment-title">Bank Transfer Payment</h1>

            <!-- Payment Amount -->
            <div class="payment-amount">₱{{ number_format($order->total_amount, 2) }}</div>

            <!-- Instructions -->
            <div class="instructions">
                <div class="instruction-title">Payment Instructions:</div>
                <ol class="instruction-list">
                    <li class="instruction-item">Transfer the exact amount to the bank account details below</li>
                    <li class="instruction-item">Use your Order Number (#{{ $order->order_number }}) as reference</li>
                    <li class="instruction-item">Send proof of payment to our email or upload in your order details</li>
                    <li class="instruction-item">Your order will be confirmed once payment is verified (usually within 24 hours)</li>
                </ol>
            </div>

            <!-- Bank Details -->
            <div class="bank-details">
                <div style="font-weight: 600; margin-bottom: 1rem; color: var(--text-strong);">
                    Bank Account Details:
                </div>
                <div class="detail-row">
                    <span>Bank Name:</span>
                    <span>Sample Bank</span>
                </div>
                <div class="detail-row">
                    <span>Account Name:</span>
                    <span>InkWise Corporation</span>
                </div>
                <div class="detail-row">
                    <span>Account Number:</span>
                    <span>
                        1234567890
                        <button class="copy-btn" onclick="copyToClipboard('1234567890')">Copy</button>
                    </span>
                </div>
                <div class="detail-row">
                    <span>Reference/Order Number:</span>
                    <span>
                        #{{ $order->order_number }}
                        <button class="copy-btn" onclick="copyToClipboard('#{{ $order->order_number }}')">Copy</button>
                    </span>
                </div>
            </div>

            <!-- Alert -->
            <div class="alert">
                <strong>Important:</strong> Please ensure you include the Order Number in your transfer reference so we can identify your payment quickly.
            </div>

            <!-- Action Buttons -->
            <div>
                <a href="{{ route('customer.order.success', $order->id) }}" class="btn btn-primary">
                    I've Completed the Transfer
                </a>
                <a href="{{ route('customer.checkout.payment.cancel', ['orderId' => $order->id]) }}" class="btn btn-secondary">
                    Cancel Payment
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show temporary success message
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = 'var(--success)';
                btn.style.color = 'white';

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
    </script>
</body>
</html>