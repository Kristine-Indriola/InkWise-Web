<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderFlow\FinalizeOrderRequest;
use App\Http\Requests\OrderFlow\SelectEnvelopeRequest;
use App\Http\Requests\OrderFlow\SelectGiveawayRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductEnvelope;
use App\Services\OrderFlowService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderFlowController extends Controller
{
    private const SESSION_ORDER_ID = 'current_order_id';
    private const SESSION_SUMMARY_KEY = 'order_summary_payload';
    private const DEFAULT_TAX_RATE = 0.12;
    private const DEFAULT_SHIPPING_FEE = 250;

    public function __construct(private readonly OrderFlowService $orderFlow)
    {
    }

    public function edit(Request $request, ?Product $product = null): ViewContract
    {
        $productId = $request->integer('product_id');
        $product = $this->orderFlow->resolveProduct($product, $productId);
        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();

        return view('customer.Invitations.editing', [
            'product' => $product,
            'frontImage' => $images['front'],
            'backImage' => $images['back'],
            'previewImages' => $images['all'],
            'defaultQuantity' => $product ? $this->orderFlow->defaultQuantityFor($product) : 50,
        ]);
    }

    public function storeDesignSelection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::with([
            'template',
            'uploads',
            'images',
            'paperStocks',
            'addons',
            'colors',
            'bulkOrders',
        ])->findOrFail($data['product_id']);

        $quantity = $data['quantity'] ?? $this->orderFlow->defaultQuantityFor($product);
        $unitPrice = $this->orderFlow->unitPriceFor($product);
        $subtotal = round($unitPrice * $quantity, 2);
        $taxAmount = round($subtotal * static::DEFAULT_TAX_RATE, 2);
        $shippingFee = static::DEFAULT_SHIPPING_FEE;
        $total = round($subtotal + $taxAmount + $shippingFee, 2);

        $order = null;

        DB::transaction(function () use (&$order, $product, $quantity, $unitPrice, $subtotal, $taxAmount, $shippingFee, $total) {
            $this->clearExistingOrder();

            $customerOrder = $this->orderFlow->createCustomerOrder(Auth::user());

            $order = $customerOrder->orders()->create([
                'customer_id' => $customerOrder->customer_id,
                'user_id' => optional(Auth::user())->user_id,
                'order_number' => $this->orderFlow->generateOrderNumber(),
                'status' => 'pending',
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'shipping_option' => 'standard',
                'payment_method' => null,
                'payment_status' => 'pending',
                'summary_snapshot' => null,
                'metadata' => [
                    'source' => 'design_editor',
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);

            $designMetadata = $this->orderFlow->buildDesignMetadata($product);

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name ?? 'Custom Invitation',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'design_metadata' => $designMetadata,
            ]);

            $this->orderFlow->attachOptionalSelections($orderItem, $product);

            $order->update([
                'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $orderItem),
            ]);
        });

        if ($order instanceof Order) {
            session()->put(static::SESSION_ORDER_ID, $order->id);
            $this->updateSessionSummary($order);
        }

        return redirect()->route('order.review');
    }

    public function review(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $this->updateSessionSummary($order);

        $item = $order->items->first();
        $product = optional($item)->product;
        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
        $designMeta = $item?->design_metadata ?? [];
        $placeholderItems = collect(Arr::get($designMeta, 'placeholders', []));

        return view('customer.orderflow.review', [
            'order' => $order,
            'product' => $product,
            'proof' => null,
            'templateRef' => optional($product)->template,
            'finalArtworkFront' => $images['front'],
            'finalArtworkBack' => $images['back'],
            'finalArtwork' => [
                'front' => $images['front'],
                'back' => $images['back'],
            ],
            'placeholderItems' => $placeholderItems,
            'continueHref' => route('order.finalstep'),
            'editHref' => $product ? route('design.edit', ['product' => $product->id]) : route('design.edit'),
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function finalStep(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $this->updateSessionSummary($order);

        $item = $order->items->first();
        $product = optional($item)->product;

        if ($product) {
            $product->loadMissing([
                'template',
                'uploads',
                'images',
                'paperStocks',
                'addons',
                'bulkOrders',
            ]);
        }

        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
        $selectedQuantity = $item?->quantity;
        $selectedPaperStockId = $item?->paperStockSelection?->paper_stock_id;
        $selectedAddonIds = $item?->addons?->pluck('addon_id')->filter()->values()->all();

        $quantityOptions = $this->orderFlow->buildQuantityOptions($product, $selectedQuantity);
        $paperStockOptions = $this->orderFlow->buildPaperStockOptions($product, $selectedPaperStockId);
        $addonGroups = $this->orderFlow->buildAddonGroups($product, $selectedAddonIds);

        return view('customer.orderflow.finalstep', [
            'order' => $order,
            'product' => $product,
            'proof' => null,
            'templateRef' => optional($product)->template,
            'finalArtworkFront' => $images['front'],
            'finalArtworkBack' => $images['back'],
            'quantityOptions' => $quantityOptions,
            'paperStocks' => $paperStockOptions,
            'addonGroups' => $addonGroups,
            'estimatedDeliveryDate' => Carbon::now()->addWeekdays(5)->format('F j, Y'),
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function saveFinalStep(FinalizeOrderRequest $request): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $payload = $request->validated();
        $updatedOrder = $order;

        DB::transaction(function () use ($order, $payload, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->applyFinalSelections($order, $payload);
        });

        $order = $updatedOrder ?? $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'Unable to refresh the current order.',
            ], 500);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'message' => 'Order selections saved.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function envelope(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder(false);
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $this->updateSessionSummary($order);

        return view('customer.Envelope.Envelope', [
            'order' => $order,
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function envelopeOptions(): JsonResponse
    {
        $fallbackImage = asset('images/no-image.png');

        $envelopes = ProductEnvelope::query()
            ->with(['product'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (ProductEnvelope $envelope) use ($fallbackImage) {
                return [
                    'id' => $envelope->id,
                    'product_id' => $envelope->product_id,
                    'name' => $envelope->product?->name ?? $envelope->envelope_material_name ?? 'Envelope',
                    'price' => $envelope->price_per_unit ?? 0,
                    'image' => $this->orderFlow->resolveMediaPath($envelope->envelope_image, $fallbackImage),
                    'material' => $envelope->envelope_material_name,
                    'max_qty' => $envelope->max_qty ?? $envelope->max_quantity,
                    'updated_at' => $envelope->updated_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($envelopes);
    }

    public function giveawayOptions(): JsonResponse
    {
        $fallbackImage = asset('images/placeholder.png');

        $giveaways = Product::query()
            ->with(['template', 'uploads', 'images', 'bulkOrders'])
            ->where('product_type', 'Giveaway')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Product $product) use ($fallbackImage) {
                $images = $this->orderFlow->resolveProductImages($product);
                $unitPrice = $this->orderFlow->unitPriceFor($product);
                $bulkTier = $product->bulkOrders->sortBy('min_qty')->first();

                return [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name ?? 'Giveaway',
                    'price' => $unitPrice,
                    'image' => $images['front'] ?? $fallbackImage,
                    'images' => $images['all'] ?? [],
                    'description' => Str::limit(strip_tags($product->description ?? ''), 220),
                    'material' => null,
                    'min_qty' => $bulkTier?->min_qty,
                    'max_qty' => $bulkTier?->max_qty,
                    'preview_url' => route('product.preview', $product->id),
                    'updated_at' => $product->updated_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($giveaways);
    }

    /**
     * Temporary debug helper that returns resolved image URLs for giveaway products.
     */
    public function debugGiveawayImages(): JsonResponse
    {
        $giveaways = \App\Models\Product::query()
            ->with(['template', 'uploads', 'images'])
            ->where('product_type', 'Giveaway')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($product) {
                $images = $this->orderFlow->resolveProductImages($product);
                $uploads = $product->uploads ?? collect();
                $firstImageUpload = $uploads->first(fn ($u) => str_starts_with($u->mime_type ?? '', 'image/'));

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'declared_image' => $product->image,
                    'first_upload' => $firstImageUpload?->filename ? asset('storage/uploads/products/' . $product->id . '/' . $firstImageUpload->filename) : null,
                    'resolved' => $images,
                ];
            });

        return response()->json($giveaways->values());
    }

    public function storeEnvelope(SelectEnvelopeRequest $request): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $payload = $request->validated();
        $updatedOrder = $order;

        DB::transaction(function () use ($order, $payload, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->applyEnvelopeSelection($order, $payload);
        });

        $order = $updatedOrder ?? $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'Unable to refresh the current order.',
            ], 500);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'message' => 'Envelope selection saved.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function clearEnvelope(): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $updatedOrder = $order;

        DB::transaction(function () use ($order, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->clearEnvelopeSelection($order);
        });

        $order = $updatedOrder ?? $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'Unable to refresh the current order.',
            ], 500);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'message' => 'Envelope selection cleared.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function storeGiveaway(SelectGiveawayRequest $request): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $payload = $request->validated();
        $updatedOrder = $order;

        DB::transaction(function () use ($order, $payload, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->applyGiveawaySelection($order, $payload);
        });

        $order = $updatedOrder ?? $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'Unable to refresh the current order.',
            ], 500);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'message' => 'Giveaway selection saved.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function clearGiveaway(): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $updatedOrder = $order;

        DB::transaction(function () use ($order, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->clearGiveawaySelection($order);
        });

        $order = $updatedOrder ?? $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'Unable to refresh the current order.',
            ], 500);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'message' => 'Giveaway selection cleared.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function summary(Request $request): RedirectResponse|ViewContract|JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $this->updateSessionSummary($order);

        $summary = session(static::SESSION_SUMMARY_KEY);

        if ($request->expectsJson()) {
            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'data' => $summary,
                'updated_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        return view('customer.orderflow.ordersummary', [
            'order' => $order,
            'orderSummary' => $summary,
        ]);
    }

    public function summaryJson(): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $this->updateSessionSummary($order);

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'data' => session(static::SESSION_SUMMARY_KEY),
            'updated_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function clearSummary(): JsonResponse
    {
        $order = $this->currentOrder(false);
        if (!$order) {
            return response()->json([
                'message' => 'No active order found for the current session.',
            ], 404);
        }

        $orderId = $order->id;
        $orderNumber = $order->order_number;

        DB::transaction(function () {
            $this->clearExistingOrder();
        });

        return response()->json([
            'message' => 'Order summary cleared.',
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'data' => null,
        ]);
    }

    public function checkout(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $halfPayment = round($order->total_amount / 2, 2);

        if ($order->payment_method !== 'gcash' || $order->payment_status === 'pending') {
            $metadata = $order->metadata ?? [];
            $metadata['payments'][] = [
                'method' => 'gcash',
                'amount' => $halfPayment,
                'status' => 'partial',
                'recorded_at' => now()->toIso8601String(),
            ];

            $order->update([
                'payment_method' => 'gcash',
                'payment_status' => 'partial',
                'metadata' => $metadata,
            ]);
        }

        $order->refresh();
        $this->updateSessionSummary($order);

        return view('customer.orderflow.checkout', [
            'order' => $order->loadMissing(['items.product', 'items.addons', 'customerOrder']),
            'halfPayment' => $halfPayment,
            'balanceDue' => max($order->total_amount - $halfPayment, 0),
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function completeCheckout(): RedirectResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $metadata = $order->metadata ?? [];
        $metadata['payments'][] = [
            'method' => 'gcash',
            'amount' => $order->total_amount,
            'status' => 'paid',
            'recorded_at' => now()->toIso8601String(),
        ];

        $order->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'metadata' => $metadata,
        ]);

        $this->updateSessionSummary($order);

        return redirect()->route('customer.checkout')->with('status', 'Order marked as completed.');
    }

    public function cancelCheckout(): RedirectResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $metadata = $order->metadata ?? [];
        $metadata['payments'][] = [
            'method' => 'gcash',
            'amount' => 0,
            'status' => 'cancelled',
            'recorded_at' => now()->toIso8601String(),
        ];

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        $this->updateSessionSummary($order);

        return redirect()->route('customer.checkout')->with('status', 'Order has been cancelled.');
    }

    public function giveaways(Request $request)
    {
        $order = $this->currentOrder();
        if (!$order) {
            return $this->redirectToCatalog();
        }

        $this->updateSessionSummary($order);
        $orderSummary = session(static::SESSION_SUMMARY_KEY);

        $selectedEvent = $request->query('event');
        $search = $request->query('q');

        $productsQuery = Product::query()
            ->with(['template', 'uploads', 'images', 'materials.material', 'bulkOrders'])
            ->where('product_type', 'Giveaway');

        if ($selectedEvent && $selectedEvent !== 'all') {
            $productsQuery->where(function ($query) use ($selectedEvent) {
                $query->where('event_type', $selectedEvent);
            });
        }

        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $productsQuery
            ->orderByDesc('updated_at')
            ->get();

        $eventTypes = Product::query()
            ->where('product_type', 'Giveaway')
            ->whereNotNull('event_type')
            ->where('event_type', '!=', '')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('customer.orderflow.giveaways', [
            'products' => $products,
            'eventTypes' => $eventTypes,
            'selectedEvent' => $selectedEvent,
            'searchTerm' => $search,
            'order' => $order,
            'orderSummary' => $orderSummary,
        ]);
    }

    private function currentOrder(bool $withRelations = true): ?Order
    {
        $orderId = session(static::SESSION_ORDER_ID);
        if (!$orderId) {
            return null;
        }

        $query = Order::query();

        if ($withRelations) {
            $query->with([
                'items.product.template',
                'items.product.images',
                'items.product.uploads',
                'items.bulkSelections.productBulkOrder',
                'items.paperStockSelection.paperStock',
                'items.addons.productAddon',
                'customerOrder',
            ]);
        }

        return $query->find($orderId);
    }

    private function clearExistingOrder(): void
    {
        $orderId = session(static::SESSION_ORDER_ID);
        if (!$orderId) {
            session()->forget(static::SESSION_SUMMARY_KEY);
            return;
        }

        $order = Order::with('customerOrder')->find($orderId);
        if (!$order) {
            session()->forget(static::SESSION_ORDER_ID);
            session()->forget(static::SESSION_SUMMARY_KEY);
            return;
        }

        $customerOrder = $order->customerOrder;

        $order->delete();

        if ($customerOrder && $customerOrder->orders()->count() === 0) {
            $customerOrder->delete();
        }

        session()->forget(static::SESSION_ORDER_ID);
        session()->forget(static::SESSION_SUMMARY_KEY);
    }

    private function updateSessionSummary(Order $order): void
    {
        $summary = $this->orderFlow->refreshSummary($order);

        $productId = $summary['productId'] ?? null;

        $summary['editUrl'] = $productId
            ? route('design.edit', ['product' => $productId])
            : route('design.edit');
        $summary['checkoutUrl'] = route('customer.checkout');
        $summary['giveawaysUrl'] = route('order.giveaways');
        $summary['envelopeUrl'] = route('order.envelope');
        $summary['summaryUrl'] = route('order.summary');

        session()->put(static::SESSION_SUMMARY_KEY, $summary);
    }

    private function redirectToCatalog(): RedirectResponse
    {
        return redirect()->route('templates.wedding.invitations')
            ->with('status', 'Start a new invitation to continue through the order flow.');
    }
}
