<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private const SESSION_ORDER_ID = 'current_order_id';
    private const PROVIDER = 'paymongo';

    private ?string $secretKey;
    private ?string $webhookSecret;
    private ?string $caBundle;

    public function __construct()
    {
        $this->secretKey = config('services.paymongo.secret');
        $this->webhookSecret = config('services.paymongo.webhook_secret');
        $this->caBundle = config('services.paymongo.ca_bundle');
    }

    public function createGCashPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'mode' => ['nullable', 'in:half,full'],
        ]);

        if (empty($this->secretKey)) {
            return response()->json([
                'message' => 'PayMongo secret key is not configured.',
            ], 500);
        }

        $order = $this->resolveCurrentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'We could not find an active order to charge.',
            ], 404);
        }

        $order->loadMissing('customerOrder');

        $mode = $validated['mode'] ?? 'half';

        $metadata = $order->metadata ?? [];
        $summary = $this->summarizePayments($order, $metadata);

        if ($summary['balance'] <= 0) {
            return response()->json([
                'message' => 'This order is already fully paid.',
            ], 409);
        }

        $amountToCharge = $mode === 'full' ? $summary['balance'] : $summary['deposit_due'];
        if ($amountToCharge <= 0) {
            return response()->json([
                'message' => 'Nothing to charge for the selected payment mode.',
            ], 409);
        }

        $pendingPaymentUrl = Arr::get($metadata, 'paymongo.next_action_url');
        if (Arr::get($metadata, 'paymongo.status') === 'awaiting_next_action' && $pendingPaymentUrl) {
            return response()->json([
                'redirect_url' => $pendingPaymentUrl,
                'pending' => true,
                'message' => 'You have an ongoing GCash payment. Please finish it in the opened window.',
            ]);
        }

        $intentDescription = sprintf('Inkwise order %s %s payment', $order->order_number, $mode === 'full' ? 'full' : 'deposit');

        $name = $validated['name']
            ?? $order->customerOrder->name
            ?? optional(Auth::user())->name
            ?? 'Inkwise Customer';

        $email = $validated['email']
            ?? $order->customerOrder->email
            ?? optional(Auth::user())->email;

        $phone = $validated['phone']
            ?? $order->customerOrder->phone
            ?? optional(optional(Auth::user())->customer)->contact_number
            ?? '09170000000';

        $amountInCentavos = (int) round($amountToCharge * 100);

        $intentMetadata = $this->preparePaymongoMetadata([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'mode' => $mode,
            'user_id' => Auth::id(),
        ]);

        $intentResponse = $this->paymongo()->post('payment_intents', [
            'data' => [
                'attributes' => [
                    'amount' => $amountInCentavos,
                    'currency' => 'PHP',
                    'payment_method_allowed' => ['gcash'],
                    'description' => $intentDescription,
                    'statement_descriptor' => 'Inkwise',
                    'metadata' => $intentMetadata,
                ],
            ],
        ]);

        if ($intentResponse->failed()) {
            return $this->handlePaymongoError($intentResponse, 'creating payment intent');
        }

        $intentData = $intentResponse->json();
        $paymentIntentId = Arr::get($intentData, 'data.id');
        $clientKey = Arr::get($intentData, 'data.attributes.client_key');

        $paymentMethodResponse = $this->paymongo()->post('payment_methods', [
            'data' => [
                'attributes' => [
                    'type' => 'gcash',
                    'billing' => array_filter([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]),
                ],
            ],
        ]);

        if ($paymentMethodResponse->failed()) {
            return $this->handlePaymongoError($paymentMethodResponse, 'creating payment method');
        }

        $paymentMethodData = $paymentMethodResponse->json();
        $paymentMethodId = Arr::get($paymentMethodData, 'data.id');

        $attachResponse = $this->paymongo()->post("payment_intents/{$paymentIntentId}/attach", [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'return_url' => route('payment.gcash.return'),
                ],
            ],
        ]);

        if ($attachResponse->failed()) {
            return $this->handlePaymongoError($attachResponse, 'attaching payment method');
        }

        $attachedData = $attachResponse->json();
        $redirectUrl = Arr::get($attachedData, 'data.attributes.next_action.redirect.url');
        $status = Arr::get($attachedData, 'data.attributes.status');

        $metadata['paymongo'] = array_merge($metadata['paymongo'] ?? [], [
            'intent_id' => $paymentIntentId,
            'payment_method_id' => $paymentMethodId,
            'client_key' => $clientKey,
            'mode' => $mode,
            'amount' => round($amountToCharge, 2),
            'amount_centavos' => $amountInCentavos,
            'status' => $status,
            'next_action_url' => $redirectUrl,
            'last_created_at' => now()->toIso8601String(),
        ]);

        $order->forceFill([
            'payment_method' => 'gcash',
            // When GCash payment is created, mark payment status as pending unless already paid
            'payment_status' => $order->payment_status === 'paid' ? 'paid' : 'pending',
            'metadata' => $metadata,
        ])->save();

        return response()->json([
            'redirect_url' => $redirectUrl,
            'status' => $status,
            'amount' => round($amountToCharge, 2),
            'amount_formatted' => number_format($amountToCharge, 2),
        ]);
    }

    public function handleGCashReturn(Request $request): RedirectResponse
    {
        $intentId = $request->query('payment_intent_id') ?? $request->query('id');
        if (!$intentId) {
            return redirect()->route('customer.checkout')->with('status', 'We could not verify the payment status.');
        }

        $order = $this->resolveCurrentOrder();
        if (!$order || Arr::get($order->metadata, 'paymongo.intent_id') !== $intentId) {
            $order = $this->findOrderByIntentId($intentId) ?? $order;
        }

        if (!$order) {
            return redirect()->route('customer.checkout')->with('status', 'We could not match the payment to an order.');
        }

        $intentResponse = $this->paymongo()->get("payment_intents/{$intentId}");
        if ($intentResponse->failed()) {
            Log::warning('Unable to retrieve PayMongo intent after customer return.', [
                'intent_id' => $intentId,
                'status' => $intentResponse->status(),
                'body' => $intentResponse->json(),
            ]);

            return redirect()->route('customer.checkout')->with('status', 'We are still processing your payment. Please refresh this page in a moment.');
        }

        $intentData = $intentResponse->json();
        $status = Arr::get($intentData, 'data.attributes.status');
        $payments = collect(Arr::get($intentData, 'data.attributes.payments', []));
        $latestPayment = $payments->sortByDesc(fn ($payment) => Arr::get($payment, 'attributes.created_at'))->first();

        if ($status === 'succeeded' && $latestPayment) {
            $amount = (int) Arr::get($latestPayment, 'attributes.amount', 0) / 100;
            $paymentId = Arr::get($latestPayment, 'id') ?? ('pi:' . $intentId);

            $this->applyPaymentToOrder($order, [
                'payment_id' => $paymentId,
                'intent_id' => $intentId,
                'mode' => Arr::get($order->metadata, 'paymongo.mode', 'half'),
                'amount' => $amount,
                'raw' => $latestPayment,
                'intent_status' => $status,
            ]);

            $message = 'Payment received! Thank you for settling your order.';
        } elseif (in_array($status, ['awaiting_next_action', 'awaiting_payment_method'], true)) {
            $message = 'Please finish your GCash payment in the opened window to complete the order.';
        } else {
            $message = 'We were unable to confirm the payment. Please try again or contact support.';
        }

        return redirect()->route('customer.checkout')->with('status', $message);
    }

    public function webhook(Request $request): JsonResponse
    {
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid PayMongo webhook signature received.');

            return response()->json([
                'message' => 'Invalid signature.',
            ], 400);
        }

        $payload = $request->all();
        $eventType = Arr::get($payload, 'data.attributes.type');

        if ($eventType === 'payment.paid') {
            $paymentData = Arr::get($payload, 'data.attributes.data');
            $intentId = Arr::get($paymentData, 'attributes.payment_intent_id');
            $order = $this->findOrderByIntentId($intentId);

            if ($order) {
                $amount = (int) Arr::get($paymentData, 'attributes.amount', 0) / 100;

                $this->applyPaymentToOrder($order, [
                    'payment_id' => Arr::get($paymentData, 'id'),
                    'intent_id' => $intentId,
                    'mode' => Arr::get($order->metadata, 'paymongo.mode', 'half'),
                    'amount' => $amount,
                    'raw' => $paymentData,
                    'intent_status' => 'succeeded',
                ]);
            } else {
                Log::warning('PayMongo webhook could not match payment intent to order.', [
                    'intent_id' => $intentId,
                ]);
            }
        }

        if ($eventType === 'payment.failed') {
            $paymentData = Arr::get($payload, 'data.attributes.data');
            $intentId = Arr::get($paymentData, 'attributes.payment_intent_id');
            $order = $this->findOrderByIntentId($intentId);

            if ($order) {
                $metadata = $order->metadata ?? [];
                $metadata['paymongo'] = array_merge($metadata['paymongo'] ?? [], [
                    'status' => 'failed',
                    'last_failure' => [
                        'payment_id' => Arr::get($paymentData, 'id'),
                        'recorded_at' => now()->toIso8601String(),
                        'reason' => Arr::get($paymentData, 'attributes.failure_message')
                            ?? Arr::get($paymentData, 'attributes.description')
                            ?? 'Payment failed.',
                    ],
                ]);

                $order->forceFill(['metadata' => $metadata])->save();
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function resolveCurrentOrder(): ?Order
    {
        $orderId = session(static::SESSION_ORDER_ID);
        if (!$orderId) {
            return null;
        }

        return Order::query()->find($orderId);
    }

    private function paymongo(): PendingRequest
    {
        $options = [];
        $resolvedBundle = $this->resolveCaBundlePath($this->caBundle);

        if ($this->caBundle && !$resolvedBundle) {
            Log::warning('Configured PayMongo CA bundle could not be found or read.', [
                'configured_path' => $this->caBundle,
            ]);
        }

        if ($resolvedBundle) {
            $options['verify'] = $resolvedBundle;
        }

        return Http::withOptions($options)->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(($this->secretKey ?? '') . ':'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->baseUrl('https://api.paymongo.com/v1');
    }

    /**
     * PayMongo only accepts flattened string metadata values.
     * Converts arrays/objects into JSON strings and drops nulls to prevent
     * "metadata attributes cannot be nested" errors from the API.
     */
    private function preparePaymongoMetadata(array $metadata): array
    {
        $prepared = [];

        foreach ($metadata as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized = null;

            if ($value instanceof \DateTimeInterface) {
                $normalized = $value->format(DATE_ATOM);
            } elseif (is_bool($value)) {
                $normalized = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $normalized = (string) $value;
            } elseif ($value instanceof \JsonSerializable || is_array($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($encoded !== false) {
                    $normalized = $encoded;
                }
            } else {
                $normalized = (string) $value;
            }

            if ($normalized === null || $normalized === '') {
                continue;
            }

            $prepared[(string) $key] = $normalized;
        }

        return $prepared;
    }

    private function resolveCaBundlePath(?string $path): ?string
    {
        $candidates = array_filter([
            $this->normalizeCaBundlePath($path),
            $this->normalizeCaBundlePath(env('CURL_CA_BUNDLE')),
            $this->normalizeCaBundlePath(env('SSL_CERT_FILE')),
            $this->normalizeCaBundlePath(ini_get('curl.cainfo') ?: null),
            $this->normalizeCaBundlePath(ini_get('openssl.cafile') ?: null),
        ]);

        $defaultLocations = [
            base_path('cacert.pem'),
            base_path('certs/cacert.pem'),
            storage_path('app/certs/cacert.pem'),
            storage_path('certs/cacert.pem'),
            storage_path('cacert.pem'),
        ];

        if (DIRECTORY_SEPARATOR === '\\') {
            $defaultLocations[] = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt';
            $defaultLocations[] = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';
        }

        foreach ($defaultLocations as $location) {
            $candidates[] = $this->normalizeCaBundlePath($location);
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeCaBundlePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($path, 'file://')) {
            $path = substr($path, 7);
        }

        $windowsDrivePattern = '/^[A-Za-z]:[\\\\\/]/';
        if (!Str::startsWith($path, DIRECTORY_SEPARATOR)
            && !preg_match($windowsDrivePattern, $path)
            && !Str::startsWith($path, '\\\\')) {
            $path = base_path($path);
        }

        return $path;
    }

    private function summarizePayments(Order $order, ?array $metadata = null): array
    {
        $metadata ??= $order->metadata ?? [];

        $paymentRecords = $order->relationLoaded('payments')
            ? $order->payments
            : $order->payments()->get();

        if ($paymentRecords->isNotEmpty()) {
            $payments = $paymentRecords
                ->sortByDesc(fn (Payment $payment) => $payment->recorded_at ?? $payment->created_at)
                ->map(function (Payment $payment) {
                    return [
                        'provider' => $payment->provider,
                        'provider_id' => $payment->provider_payment_id,
                        'intent_id' => $payment->intent_id,
                        'method' => $payment->method,
                        'mode' => $payment->mode,
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'recorded_at' => optional($payment->recorded_at ?? $payment->created_at)->toIso8601String(),
                        'raw' => $payment->raw_payload,
                    ];
                });

            $metadata['payments'] = $payments->values()->all();
        } else {
            $payments = collect(Arr::get($metadata, 'payments', []));
        }

        $successful = $payments->filter(fn ($payment) => Arr::get($payment, 'status') === 'paid');
        $totalPaid = round($successful->sum(fn ($payment) => (float) Arr::get($payment, 'amount', 0)), 2);
        $balance = round(max(($order->total_amount ?? 0) - $totalPaid, 0), 2);
        $depositDue = $balance <= 0
            ? 0
            : min(round(max($order->total_amount / 2, 0), 2), $balance);

        return [
            'metadata' => $metadata,
            'payments' => $payments->values()->all(),
            'total_paid' => $totalPaid,
            'balance' => $balance,
            'deposit_due' => $depositDue,
        ];
    }

    private function applyPaymentToOrder(Order $order, array $payload, string $status = 'paid'): Order
    {
        $metadata = $order->metadata ?? [];
        $paymentId = Arr::get($payload, 'payment_id');
        $intentId = Arr::get($payload, 'intent_id');
        $amount = round((float) Arr::get($payload, 'amount', 0), 2);

        $recordedAt = Arr::get($payload, 'recorded_at');
        if ($recordedAt && ! $recordedAt instanceof \DateTimeInterface) {
            $recordedAt = Carbon::parse($recordedAt);
        }

        $attributes = [
            'customer_id' => $order->customer_id,
            'provider' => static::PROVIDER,
            'recorded_by' => optional(Auth::user())->user_id,
            'intent_id' => $intentId,
            'method' => Arr::get($payload, 'method', 'gcash'),
            'mode' => Arr::get($payload, 'mode', 'half'),
            'amount' => $amount,
            'currency' => 'PHP',
            'status' => $status,
            'raw_payload' => Arr::get($payload, 'raw'),
        ];

        if ($recordedAt instanceof \DateTimeInterface) {
            $attributes['recorded_at'] = Carbon::instance($recordedAt);
        } elseif ($status === 'paid') {
            $attributes['recorded_at'] = now();
        }

        $payment = Payment::updateOrCreate(
            [
                'order_id' => $order->id,
                'provider' => static::PROVIDER,
                'provider_payment_id' => $paymentId,
            ],
            $attributes
        );

        $order->unsetRelation('payments');
        $order->load('payments');

        $metadata['payments'] = $order->payments
            ->sortByDesc(fn (Payment $payment) => $payment->recorded_at ?? $payment->created_at)
            ->map(function (Payment $payment) {
                return [
                    'provider' => $payment->provider,
                    'provider_id' => $payment->provider_payment_id,
                    'intent_id' => $payment->intent_id,
                    'method' => $payment->method,
                    'mode' => $payment->mode,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'recorded_at' => optional($payment->recorded_at ?? $payment->created_at)->toIso8601String(),
                    'raw' => $payment->raw_payload,
                ];
            })
            ->values()
            ->all();

        $metadata['paymongo'] = array_merge($metadata['paymongo'] ?? [], [
            'intent_id' => $intentId ?? Arr::get($metadata, 'paymongo.intent_id'),
            'status' => Arr::get($payload, 'intent_status', $status),
            'last_payment_id' => $paymentId ?? Arr::get($metadata, 'paymongo.last_payment_id'),
            'last_paid_at' => now()->toIso8601String(),
            'last_payment_record_id' => $payment->id,
        ]);

        $summary = $this->summarizePayments($order, $metadata);

        $attributes = [
            'metadata' => $summary['metadata'],
            'payment_status' => $summary['balance'] <= 0 ? 'paid' : ($summary['total_paid'] > 0 ? 'partial' : $order->payment_status),
        ];

        if ($summary['balance'] <= 0 && $order->status !== 'completed') {
            $attributes['status'] = 'completed';
        }

        $order->forceFill($attributes)->save();

        return $order->fresh(['payments']);
    }

    private function verifyWebhookSignature(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            return true;
        }

        $header = $request->header('Paymongo-Signature');
        if (!$header) {
            return false;
        }

        $parts = $this->parseSignatureHeader($header);
        if (!isset($parts['t'], $parts['v1'])) {
            return false;
        }

        $signedPayload = $parts['t'] . '.' . $request->getContent();
        $computedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return hash_equals($computedSignature, $parts['v1']);
    }

    private function parseSignatureHeader(string $header): array
    {
        $segments = explode(',', $header);
        $parts = [];

        foreach ($segments as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key && $value) {
                $parts[$key] = $value;
            }
        }

        return $parts;
    }

    private function findOrderByIntentId(?string $intentId): ?Order
    {
        if (!$intentId) {
            return null;
        }

        try {
            $order = Order::query()
                ->where('metadata->paymongo->intent_id', $intentId)
                ->first();

            if ($order) {
                return $order;
            }
        } catch (QueryException $exception) {
            Log::warning('JSON intent lookup failed, falling back to collection scan.', [
                'intent_id' => $intentId,
                'message' => $exception->getMessage(),
            ]);
        }

        return Order::query()
            ->whereNotNull('metadata')
            ->get()
            ->first(fn (Order $order) => Arr::get($order->metadata, 'paymongo.intent_id') === $intentId);
    }

    private function handlePaymongoError(HttpResponse $response, string $context): JsonResponse
    {
        $payload = $response->json();
        $message = Arr::get($payload, 'errors.0.detail')
            ?? Arr::get($payload, 'errors.0.title')
            ?? 'PayMongo request failed.';

        Log::warning("PayMongo {$context} failed", [
            'status' => $response->status(),
            'context' => $context,
            'payload' => $payload,
        ]);

        return response()->json([
            'message' => $message,
            'errors' => Arr::get($payload, 'errors', []),
        ], $response->status() >= 400 ? $response->status() : 422);
    }
}
