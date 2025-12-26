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
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\OrderFlowService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderFlowController extends Controller
{
    private const SESSION_ORDER_ID = 'current_order_id';
    private const SESSION_SUMMARY_KEY = 'order_summary_payload';
    private const DEFAULT_TAX_RATE = 0.0;
    private const DEFAULT_SHIPPING_FEE = 0.0;

    public function __construct(private readonly OrderFlowService $orderFlow)
    {
    }

    public function edit(Request $request, ?Product $product = null): ViewContract
    {
        $productId = $request->integer('product_id');
        $product = $this->orderFlow->resolveProduct($product, $productId);
        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();

    $frontSvgData = $this->readInlineSvg($product?->template->front_image ?? null);
    $backSvgData = $this->readInlineSvg($product?->template->back_image ?? null);

    $frontSvg = $frontSvgData['content'] ?? null;
    $backSvg = $backSvgData['content'] ?? null;

    $templateParser = $this->buildTemplateParserSummary($product, $frontSvgData, $backSvgData);

        // Determine the correct view based on product type
        $viewName = $product && strtolower($product->product_type ?? '') === 'giveaway'
            ? 'customer.Giveaways.editing'
            : 'customer.Invitations.editing';

        return view($viewName, [
            'product' => $product,
            'frontImage' => $images['front'],
            'backImage' => $images['back'],
            'previewImages' => $images['all'],
            'imageSlots' => $product ? [
                ['side' => 'front', 'default' => $product->template->front_image ?? null],
                ['side' => 'back', 'default' => $product->template->back_image ?? null],
            ] : [],
            'defaultQuantity' => $product ? $this->orderFlow->defaultQuantityFor($product) : 50,
            'frontSvg' => $frontSvg,
            'backSvg' => $backSvg,
            'templateParser' => $templateParser,
        ]);
    }

    public function storeDesignSelection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'summary' => ['nullable', 'array'],
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

        // Instead of persisting an Order early in the flow, store a session-only
        // summary payload. The actual DB Order will be created when the user
        // advances to the final step (checkout) to avoid cluttering the orders
        // table with draft rows.

        $designMetadata = $this->orderFlow->buildDesignMetadata($product);

        $images = $this->orderFlow->resolveProductImages($product);

        // If the client provided a full summary payload (from final step), prefer it
        if (!empty($data['summary']) && is_array($data['summary'])) {
            $summary = $data['summary'];
            // ensure key productId exists
            $summary['productId'] = $summary['productId'] ?? $summary['product_id'] ?? $product->id;
            $summary['quantity'] = $summary['quantity'] ?? $quantity;
            $summary['unitPrice'] = $summary['unitPrice'] ?? $summary['unit_price'] ?? $unitPrice;
            $summary['subtotalAmount'] = $summary['subtotalAmount'] ?? $summary['subtotal_amount'] ?? round(($summary['unitPrice'] ?? $unitPrice) * ($summary['quantity'] ?? $quantity), 2);
            $summary['taxAmount'] = $summary['taxAmount'] ?? $taxAmount;
            $summary['shippingFee'] = $summary['shippingFee'] ?? $shippingFee;
            $summary['totalAmount'] = $summary['totalAmount'] ?? $summary['total_amount'] ?? round(($summary['subtotalAmount'] ?? $subtotal) + $summary['taxAmount'] + $summary['shippingFee'], 2);
        } else {
            $summary = [
                'orderId' => null,
                'orderNumber' => null,
                'orderStatus' => 'draft',
                'paymentStatus' => null,
                'productId' => $product->id,
                'productName' => $product->name ?? 'Custom Invitation',
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'subtotalAmount' => $subtotal,
                'taxAmount' => $taxAmount,
                'shippingFee' => $shippingFee,
                'totalAmount' => $total,
                'previewImages' => $images['all'] ?? [],
                'previewImage' => $images['front'] ?? null,
                'invitationImage' => $images['front'] ?? null,
                'paperStockId' => null,
                'paperStockName' => null,
                'paperStockPrice' => null,
                'addons' => [],
                'addonIds' => [],
                'metadata' => [
                    'design' => $designMetadata,
                ],
                'placeholders' => $designMetadata['placeholders'] ?? [],
                'extras' => [
                    'paper' => 0,
                    'addons' => 0,
                    'envelope' => 0,
                    'giveaway' => 0,
                ],
            ];
        }

        $summary['productId'] = $summary['productId'] ?? $product->id;
        $summary['productName'] = $summary['productName'] ?? ($product->name ?? 'Custom Invitation');

        // Replace any existing session summary for a fresh edit flow
        session()->put(static::SESSION_SUMMARY_KEY, $summary);

        $cart = null;
        $cartItem = null;

        try {
            [$cart, $cartItem] = $this->persistCartSelection($product, $summary);
        } catch (\Throwable $ex) {
            report($ex);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unable to add the item to your cart at this time. Please try again.',
                ], 500);
            }
        }

        // Ensure we are not pointing at an existing persisted order
        session()->forget(static::SESSION_ORDER_ID);

        if ($request->expectsJson()) {
            return response()->json([
                'cart_id' => $cart?->id,
                'cart_item_id' => $cartItem?->id,
                'quantity' => $cartItem?->quantity,
                'total_amount' => $cart?->total_amount,
            ], $cartItem ? 201 : 200);
        }

        return redirect()->route('order.review');
    }

    /**
     * Persist the provided summary into the active cart for the current session/user.
     *
     * @return array{0: Cart, 1: CartItem}
     */
    private function persistCartSelection(Product $product, array $summary): array
    {
        $userId = Auth::id();
        $sessionId = session()->getId();
        // We are moving to a cart_items-only approach. Store session_id on cart_items
        // and treat the collection of matching cart_items as the "active cart".

        $summary['productId'] = $summary['productId'] ?? $product->id;
        $summary['productName'] = $summary['productName'] ?? ($product->name ?? 'Custom Invitation');

        $paperTypeId = data_get($summary, 'paperStockId') ?? data_get($summary, 'paper_stock_id');
        $paperPrice = data_get($summary, 'paperStockPrice') ?? data_get($summary, 'paper_stock_price');

        $unit = data_get($summary, 'unitPrice') ?? data_get($summary, 'unit_price');
        if (!is_numeric($unit) || (float) $unit <= 0) {
            $unit = $this->orderFlow->unitPriceFor($product);
        }
        $unit = round((float) $unit, 2);

        $qty = data_get($summary, 'quantity');
        if (!is_numeric($qty) || (int) $qty <= 0) {
            $qty = $this->orderFlow->defaultQuantityFor($product);
        }
        $qty = max(1, (int) $qty);

        $totalAmount = data_get($summary, 'totalAmount') ?? data_get($summary, 'total_amount');
        if (!is_numeric($totalAmount) || (float) $totalAmount <= 0) {
            $totalAmount = round($unit * $qty, 2);
        } else {
            $totalAmount = round((float) $totalAmount, 2);
        }

        $summary['unitPrice'] = $unit;
        $summary['quantity'] = $qty;
        $summary['totalAmount'] = $totalAmount;

        $cartItemData = [
            'session_id' => $sessionId,
            'customer_id' => $userId,
            'product_type' => $product->product_type ?? data_get($summary, 'product_type'),
            'product_id' => $product->id,
            'quantity' => $qty,
            'paper_type_id' => $paperTypeId,
            'paper_price' => $paperPrice,
            'unit_price' => $unit,
            'total_price' => $totalAmount,
            'status' => data_get($summary, 'status', 'not_ordered'),
            'metadata' => $summary,
        ];

        // Find an existing matching cart_item for this session/user
        $existingQuery = CartItem::query()
            ->where(function ($q) use ($userId, $sessionId) {
                $q->where('customer_id', $userId)
                  ->orWhere('session_id', $sessionId);
            })
            ->where('product_id', $cartItemData['product_id'])
            ->where('status', $cartItemData['status'])
            ->when($paperTypeId, fn ($query) => $query->where('paper_type_id', $paperTypeId))
            ->when(!$paperTypeId, fn ($query) => $query->whereNull('paper_type_id'));

        $cartItem = $existingQuery->latest('id')->first();

        if ($cartItem) {
            $cartItem->fill($cartItemData);
            $cartItem->save();
        } else {
            $cartItem = CartItem::create($cartItemData);
        }

        // Build an in-memory representation of the active cart (collection + totals)
        $items = CartItem::where(function ($q) use ($userId, $sessionId) {
            $q->where('customer_id', $userId)
              ->orWhere('session_id', $sessionId);
        })->with(['product.template'])->orderByDesc('created_at')->get();

        $cart = new Cart();
        $cart->setRelation('items', $items);
        $cart->total_amount = $items->sum('total_price');

        $cartItem->refresh();

        return [$cart, $cartItem];
    }

    private function resolveActiveCart(): ?Cart
    {
        $sessionId = session()->getId();
        $userId = Auth::id();

        $items = CartItem::where(function ($q) use ($sessionId, $userId) {
            $q->where('session_id', $sessionId);
            if ($userId) {
                $q->orWhere('customer_id', $userId);
            }
        })->with(['product.template'])->orderByDesc('created_at')->get();

        if ($items->isEmpty()) {
            return null;
        }

        $cart = new Cart();
        $cart->setRelation('items', $items);
        $cart->total_amount = $items->sum('total_price');

        return $cart;
    }

    public function autosaveDesign(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'design' => ['required', 'array'],
            'design.updated_at' => ['nullable', 'string'],
            'design.sides' => ['nullable', 'array'],
            'design.sides.*' => ['nullable', 'array'],
            'design.texts' => ['nullable', 'array'],
            'design.texts.*' => ['nullable', 'array'],
            'design.images' => ['nullable', 'array'],
            'design.images.*' => ['nullable', 'array'],
            'design.canvas' => ['nullable', 'array'],
            'preview' => ['nullable', 'array'],
            'preview.image' => ['nullable', 'string'],
            'preview.images' => ['nullable', 'array'],
            'preview.images.*' => ['nullable', 'string'],
            'placeholders' => ['nullable', 'array'],
            'placeholders.*' => ['nullable', 'string'],
        ]);

        $placeholders = collect(Arr::get($payload, 'placeholders', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->values()
            ->all();

        $designMeta = $this->normalizeDesignAutosavePayload(Arr::get($payload, 'design', []), $placeholders);
        $preview = Arr::get($payload, 'preview', []);
        $previewImages = $this->deriveDesignPreviewImages($preview, $designMeta);
        $primaryPreview = $previewImages[0] ?? Arr::get($preview, 'image');

        $summary = session(static::SESSION_SUMMARY_KEY) ?? [];
        if (!is_array($summary)) {
            $summary = [];
        }

        $summary['metadata'] = is_array($summary['metadata'] ?? null)
            ? $summary['metadata']
            : [];
        $summary['metadata']['design'] = $designMeta;

        if (!empty($previewImages)) {
            $summary['previewImages'] = $previewImages;
        }

        if ($primaryPreview) {
            $summary['previewImage'] = $primaryPreview;
            $summary['invitationImage'] = $primaryPreview;
            if (empty($summary['previewImages'])) {
                $summary['previewImages'] = [$primaryPreview];
            } else {
                $summary['previewImages'][0] = $primaryPreview;
            }
        }

        if (!empty($placeholders)) {
            $summary['placeholders'] = $placeholders;
        }

        session()->put(static::SESSION_SUMMARY_KEY, $summary);

        $order = $this->currentOrder(false);
        if ($order) {
            DB::transaction(function () use (&$order, $designMeta, $placeholders, $summary) {
                $previewImages = $summary['previewImages'] ?? [];
                $previewImage = $summary['previewImage'] ?? null;

                $order = $this->orderFlow->applyDesignAutosave($order, [
                    'design' => $designMeta,
                    'placeholders' => $placeholders,
                    'preview_image' => $previewImage,
                    'preview_images' => $previewImages,
                ]);
            });
        }

        return response()->json([
            'message' => 'Design saved.',
            'saved_at' => $designMeta['updated_at'] ?? Carbon::now()->toIso8601String(),
            'order_id' => $order?->id,
            'summary' => $summary,
            'review_url' => route('order.review'),
        ]);
    }

    public function review(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder();

        // If there's no persisted order, allow rendering the review page from a
        // session-only summary created in the editor flow.
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || empty($summary['productId'])) {
                return $this->redirectToCatalog();
            }

            $product = $this->orderFlow->resolveProduct(null, $summary['productId']);
            $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
            $placeholderItems = collect($summary['placeholders'] ?? []);

            $summaryPreviewImages = array_values(array_filter($summary['previewImages'] ?? [], fn ($value) => is_string($value) && trim($value) !== ''));
            if (!empty($summaryPreviewImages)) {
                $images['all'] = $summaryPreviewImages;
                $images['front'] = $summaryPreviewImages[0];
                if (!empty($summaryPreviewImages[1])) {
                    $images['back'] = $summaryPreviewImages[1];
                }
            }

            if (!empty($summary['previewImage']) && is_string($summary['previewImage'])) {
                $images['front'] = $summary['previewImage'];
                if (empty($images['all'])) {
                    $images['all'] = [$summary['previewImage']];
                } else {
                    $images['all'][0] = $summary['previewImage'];
                }
            }

            $orderPlaceholder = (object) ['items' => collect()];

            return view('customer.orderflow.review', [
                'order' => $orderPlaceholder,
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
                'editHref' => $product && $product->template ? route('design.studio', ['template' => $product->template->id]) : route('design.edit'),
                'orderSummary' => $summary,
            ]);
        }

        $this->updateSessionSummary($order);

        // Mark any cart for this session as done (order persisted) but keep records
        try {
            $sessionId = session()->getId();
            $cart = Cart::where('session_id', $sessionId)->latest()->first();
            if ($cart) {
                $metadata = is_array($cart->metadata) ? $cart->metadata : (array) ($cart->metadata ?? []);
                $metadata['finalized_order_id'] = $order->id ?? null;
                $metadata['finalized_order_number'] = $order->order_number ?? null;
                $cart->update([
                    'status' => 'done',
                    'total_amount' => $order->total_amount ?? $cart->total_amount,
                    'metadata' => $metadata,
                ]);
            }
        } catch (\Throwable $_e) {
            report($_e);
        }

        $item = $order->items->first();
        $product = optional($item)->product;
        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
        $designMeta = $item?->design_metadata ?? [];
        $placeholderItems = collect(Arr::get($designMeta, 'placeholders', []));

        $summaryPayload = session(static::SESSION_SUMMARY_KEY);
        if (is_array($summaryPayload)) {
            $sessionPreviewImages = array_values(array_filter($summaryPayload['previewImages'] ?? [], fn ($value) => is_string($value) && trim($value) !== ''));
            if (!empty($sessionPreviewImages)) {
                $images['all'] = $sessionPreviewImages;
                $images['front'] = $sessionPreviewImages[0];
                if (!empty($sessionPreviewImages[1])) {
                    $images['back'] = $sessionPreviewImages[1];
                }
            }

            if (!empty($summaryPayload['previewImage']) && is_string($summaryPayload['previewImage'])) {
                $images['front'] = $summaryPayload['previewImage'];
                if (empty($images['all'])) {
                    $images['all'] = [$summaryPayload['previewImage']];
                } else {
                    $images['all'][0] = $summaryPayload['previewImage'];
                }
            }
        }

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
            'editHref' => $product && $product->template ? route('design.studio', ['template' => $product->template->id]) : route('design.edit'),
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function addToCart(Request $request): ViewContract
    {
        $order = $this->currentOrder();
        $cart = $this->resolveActiveCart();

        if (!$order && (!$cart || $cart->items->isEmpty())) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || empty($summary['productId'])) {
                return $this->redirectToCatalog();
            }

            $product = $this->orderFlow->resolveProduct(null, $summary['productId']);

            if ($product) {
                try {
                    [$cart] = $this->persistCartSelection($product, $summary);
                    $cart = $this->resolveActiveCart();
                } catch (\Throwable $_e) {
                    report($_e);
                }
            }
        }

        if ($order) {
            $this->updateSessionSummary($order);

            $item = $order->items->first();
            $product = optional($item)->product;
            $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();

            return view('customer.orderflow.addtocart', [
                'order' => $order,
                'product' => $product,
                'finalArtworkFront' => $images['front'],
                'finalArtworkBack' => $images['back'],
                'finalArtwork' => [
                    'front' => $images['front'],
                    'back' => $images['back'],
                ],
                'envelopeUrl' => route('order.envelope'),
                'orderSummary' => session(static::SESSION_SUMMARY_KEY),
                'cart' => $cart,
                'cartItems' => collect($cart?->items ?? []),
                'cartTotalAmount' => $cart?->total_amount ?? collect($cart?->items ?? [])->sum('total_price'),
            ]);
        }

        $cartItems = collect($cart?->items ?? []);

        if ($cartItems->isEmpty()) {
            return $this->redirectToCatalog();
        }

        $primaryItem = $cartItems->first();
        $product = $primaryItem?->product;

        $orderSummary = session(static::SESSION_SUMMARY_KEY);
        if (!is_array($orderSummary) || empty($orderSummary)) {
            $orderSummary = $primaryItem?->metadata ?? [];
        }

        if (!$product && !empty($orderSummary['productId'])) {
            $product = $this->orderFlow->resolveProduct(null, $orderSummary['productId']);
        }

        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();

        $previewImages = array_values(array_filter(data_get($orderSummary, 'previewImages', []), fn ($value) => is_string($value) && trim($value) !== ''));
        if (!empty($previewImages)) {
            $images['all'] = $previewImages;
            $images['front'] = $previewImages[0];
            if (!empty($previewImages[1])) {
                $images['back'] = $previewImages[1];
            }
        }

        if (!empty($orderSummary['previewImage']) && is_string($orderSummary['previewImage'])) {
            $images['front'] = $orderSummary['previewImage'];
            if (empty($images['all'])) {
                $images['all'] = [$orderSummary['previewImage']];
            } else {
                $images['all'][0] = $orderSummary['previewImage'];
            }
        }

        return view('customer.orderflow.addtocart', [
            'order' => null,
            'product' => $product,
            'finalArtworkFront' => $images['front'],
            'finalArtworkBack' => $images['back'],
            'finalArtwork' => [
                'front' => $images['front'],
                'back' => $images['back'],
            ],
            'envelopeUrl' => route('order.envelope'),
            'orderSummary' => $orderSummary,
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartTotalAmount' => $cart?->total_amount ?? $cartItems->sum('total_price'),
        ]);
    }

    public function finalStep(Request $request): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder();

        // Session-only or product-preview path
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);

            if (!$summary || empty($summary['productId'])) {
                // try product preview via query param
                $productId = $request->integer('product_id');
                if ($productId) {
                    $product = $this->orderFlow->resolveProduct(null, $productId);
                    $summary = [
                        'productId' => $productId,
                        'productName' => $product?->name ?? 'Custom Invitation',
                        'quantity' => $this->orderFlow->defaultQuantityFor($product),
                        'unitPrice' => $this->orderFlow->unitPriceFor($product),
                    ];
                } else {
                    return $this->redirectToCatalog();
                }
            }

            $product = $this->orderFlow->resolveProduct(null, $summary['productId']);

            if ($product) {
                $product->loadMissing([
                    'template',
                    'uploads',
                    'images',
                    'paperStocks.material',
                    'addons',
                    'bulkOrders',
                ]);
            }

            $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
            $selectedQuantity = $summary['quantity'] ?? $request->input('quantity') ?? null;
            $selectedPaperStockId = $summary['paperStockId'] ?? null;
            $selectedAddonIds = $summary['addonIds'] ?? [];

            $quantityOptions = $this->orderFlow->buildQuantityOptions($product, $selectedQuantity);
            $paperStockOptions = $this->orderFlow->buildPaperStockOptions($product, $selectedPaperStockId);
            $addonGroups = $this->orderFlow->buildAddonGroups($product, $selectedAddonIds);

            // Calculate quantity limits from bulk orders
            $bulkOrders = $product->bulkOrders ?? collect();
            $minQty = $bulkOrders->pluck('min_qty')->filter()->min() ?? 20;
            $maxQty = $bulkOrders->pluck('max_qty')->filter()->max();

            $orderPlaceholder = (object) ['items' => collect()];

            $minPickupDate = Carbon::tomorrow();
            $maxPickupDate = Carbon::now()->addMonths(2);
            $resolvedPickupDate = $this->resolvePickupDate(null, $summary, $minPickupDate, $maxPickupDate);

            return view('customer.orderflow.finalstep', [
                'order' => $orderPlaceholder,
                'product' => $product,
                'proof' => null,
                'templateRef' => optional($product)->template,
                'finalArtworkFront' => $images['front'],
                'finalArtworkBack' => $images['back'],
                'quantityOptions' => $quantityOptions,
                'paperStocks' => $paperStockOptions,
                'addonGroups' => $addonGroups,
                'bulkOrders' => $bulkOrders,
                'basePrice' => $this->orderFlow->unitPriceFor($product),
                'minQty' => $minQty,
                'maxQty' => $maxQty,
                'estimatedDeliveryDate' => $resolvedPickupDate->format('F j, Y'),
                'estimatedDeliveryDateFormatted' => $resolvedPickupDate->format('Y-m-d'),
                'estimatedDeliveryMinDate' => $minPickupDate->format('Y-m-d'),
                'estimatedDeliveryMaxDate' => $maxPickupDate->format('Y-m-d'),
                'orderSummary' => $summary,
            ]);
        }

        // Persisted order path
        $this->updateSessionSummary($order);

        $item = $order->items->first();
        $product = optional($item)->product;

        if ($product) {
            $product->loadMissing([
                'template',
                'uploads',
                'images',
                'paperStocks.material',
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

        // Calculate quantity limits from bulk orders
        $bulkOrders = $product->bulkOrders ?? collect();
        $minQty = $bulkOrders->pluck('min_qty')->filter()->min() ?? 20;
        $maxQty = $bulkOrders->pluck('max_qty')->filter()->max();

        $summaryPayload = session(static::SESSION_SUMMARY_KEY);
        $minPickupDate = Carbon::tomorrow();
        $maxPickupDate = Carbon::now()->addMonths(2);
        $resolvedPickupDate = $this->resolvePickupDate($order, $summaryPayload, $minPickupDate, $maxPickupDate);

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
            'bulkOrders' => $bulkOrders,
            'basePrice' => $this->orderFlow->unitPriceFor($product),
            'minQty' => $minQty,
            'maxQty' => $maxQty,
            'estimatedDeliveryDate' => $resolvedPickupDate->format('F j, Y'),
            'estimatedDeliveryDateFormatted' => $resolvedPickupDate->format('Y-m-d'),
            'estimatedDeliveryMinDate' => $minPickupDate->format('Y-m-d'),
            'estimatedDeliveryMaxDate' => $maxPickupDate->format('Y-m-d'),
            'orderSummary' => $summaryPayload,
        ]);
    }

    public function saveFinalStep(FinalizeOrderRequest $request): JsonResponse
    {
        $order = $this->currentOrder();
        $orderJustCreated = false;
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || !is_array($summary) || empty($summary['productId'])) {
                return response()->json([
                    'message' => 'No active order found for the current session.',
                ], 404);
            }

            // Check stock availability before creating order
            $stockShortages = $this->orderFlow->checkStockFromSummary($summary);
            if (!empty($stockShortages)) {
                $errorMessages = [];
                foreach ($stockShortages as $shortage) {
                    $status = $shortage['available'] <= 0 ? 'out of stock' : 'low stock';
                    $errorMessages[] = "{$shortage['material_name']} is {$status} (available: {$shortage['available']}, required: {$shortage['required']})";
                }
                return response()->json([
                    'message' => 'Insufficient stock for some materials: ' . implode('; ', $errorMessages),
                ], 400);
            }

            DB::transaction(function () use (&$order, $summary, &$orderJustCreated) {
                $customerOrder = $this->orderFlow->createCustomerOrder(Auth::user());
                $metadata = $this->orderFlow->buildInitialOrderMetadata($summary);

                $order = $customerOrder->orders()->create([
                    'customer_id' => $customerOrder->customer_id,
                    'user_id' => optional(Auth::user())->user_id,
                    'order_number' => $this->orderFlow->generateOrderNumber(),
                    'order_date' => now(),
                    'status' => 'pending',
                    'subtotal_amount' => $summary['subtotalAmount'] ?? 0,
                    'tax_amount' => $summary['taxAmount'] ?? 0,
                    'shipping_fee' => $summary['shippingFee'] ?? static::DEFAULT_SHIPPING_FEE,
                    'total_amount' => $summary['totalAmount'] ?? 0,
                    'shipping_option' => 'standard',
                    'payment_method' => null,
                    'payment_status' => 'pending',
                    'summary_snapshot' => null,
                    'metadata' => $metadata,
                ]);

                $orderJustCreated = true;

                $order = $this->orderFlow->initializeOrderFromSummary($order, $summary);
                $this->orderFlow->recalculateOrderTotals($order);
                $order->refresh();

                $primaryItem = $this->orderFlow->primaryInvitationItem($order);
                if ($primaryItem instanceof OrderItem) {
                    $order->update([
                        'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                    ]);
                }

                session()->put(static::SESSION_ORDER_ID, $order->id);
                session()->put(static::SESSION_SUMMARY_KEY, $this->orderFlow->buildSummary($order));
            });

            $order = $this->currentOrder();
        }

        // Guard: only enforce checkout allowance when one is set in session
        $allowedFor = session()->get('order_checkout_allowed_for');
        if ($allowedFor && (int) $allowedFor !== (int) $order->id) {
            return response()->json([
                'message' => 'Saving final selections is only allowed from the checkout page.',
            ], 403);
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

        // remove the one-time allowance to prevent other pages from reusing it
        session()->forget('order_checkout_allowed_for');

        if ($orderJustCreated && $order) {
            $this->notifyTeamOfNewOrder($order);
        }

        $latestSummary = session(static::SESSION_SUMMARY_KEY);
        if (is_array($latestSummary) && !empty($latestSummary['productId'])) {
            try {
                $productForCart = Product::find($latestSummary['productId']);
                if ($productForCart) {
                    $this->persistCartSelection($productForCart, $latestSummary);
                }
            } catch (\Throwable $cartError) {
                report($cartError);
            }
        }

        // Return admin redirect URL so the client (GCash button) can redirect to admin order summary
        try {
            $adminRedirect = route('admin.ordersummary.index', ['order' => $order->order_number]);
        } catch (\Throwable $e) {
            $adminRedirect = url('/admin/ordersummary') . '/' . ($order->order_number ?? $order->id);
        }

        return response()->json([
            'message' => 'Order selections saved.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
            'admin_redirect' => $adminRedirect,
        ]);
    }

    public function envelope(): RedirectResponse|ViewContract
    {
        $order = $this->currentOrder(false);

        // If there's no persisted order, allow the envelope page to be
        // rendered from a session-only summary (editor flow). This keeps the
        // envelope step accessible even before the Order is persisted.
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || empty($summary['productId'])) {
                return $this->redirectToCatalog();
            }

            return view('customer.Envelope.Envelope', [
                'order' => null,
                'orderSummary' => $summary,
            ]);
        }

        $this->updateSessionSummary($order);

        return view('customer.Envelope.Envelope', [
            'order' => $order,
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    public function envelopeOptions(): JsonResponse
    {
        $cacheKey = 'envelope_options_api_v3'; // Updated cache key to force refresh
        $cacheDuration = 300; // 5 minutes

        $orderFlow = $this->orderFlow;

        $envelopes = Cache::remember($cacheKey, $cacheDuration, function () use ($orderFlow) {
            $fallbackImage = asset('images/no-image.png');

            return ProductEnvelope::query()
                ->with(['product', 'product.images', 'product.template', 'material', 'material.inventory'])
                ->orderByDesc('updated_at')
                ->get()
                ->map(function (ProductEnvelope $envelope) use ($fallbackImage, $orderFlow) {
                    $product = $envelope->product;
                    
                    // Try multiple image sources like ProductController
                    $imageCandidates = [
                        $envelope->envelope_image,
                        optional($product?->images)->front,
                        optional($product?->images)->preview,
                        $product?->image,
                        optional($product?->template)->preview_front,
                        optional($product?->template)->image,
                    ];

                    $image = collect($imageCandidates)
                        ->filter()
                        ->map(function ($path) {
                            if (!$path) {
                                return null;
                            }
                            if (preg_match('/^(https?:)?\/\//i', $path)) {
                                return $path;
                            }

                            // If path looks like an absolute filesystem path starting with '/', keep it
                            if (str_starts_with($path, '/')) {
                                return $path;
                            }

                            // Try resolving via Storage (e.g. 'public/...')
                            try {
                                return Storage::url($path);
                            } catch (\Throwable $e) {
                                return null;
                            }
                        })
                        ->first() ?? $fallbackImage;

                    // Ensure the returned image is an absolute URL that the browser can load
                    if ($image && !preg_match('/^(https?:)?\/\//i', $image)) {
                        // Trim leading slash and make an absolute asset URL
                        $image = asset(ltrim($image, '/'));
                    }

                    $availability = $orderFlow->resolveEnvelopeAvailability($envelope);
                    $maxQuantity = $availability['max_quantity'] ?? null;
                    $availableStock = $availability['available_stock'] ?? null;

                    $defaultMin = 10;
                    $minQty = $defaultMin;
                    if ($maxQuantity !== null) {
                        if ($maxQuantity <= 0) {
                            $minQty = 0;
                        } else {
                            $minQty = max(1, min($defaultMin, $maxQuantity));
                        }
                    }

                    return [
                        'id' => $envelope->id,
                        'product_id' => $envelope->product_id,
                        'name' => $product?->name ?? $envelope->envelope_material_name ?? 'Envelope',
                        'price' => $envelope->price_per_unit ?? 0,
                        'image' => $image,
                        'material' => $envelope->envelope_material_name ?? optional(Arr::get($availability, 'material'))->material_name,
                        'material_id' => $envelope->material_id,
                        'min_qty' => $minQty,
                        'max_qty' => $maxQuantity,
                        'available_stock' => $availableStock,
                        'updated_at' => $envelope->updated_at?->toIso8601String(),
                    ];
                })
                ->values()
                ->toArray();
        });

        return response()->json($envelopes);
    }

    public function giveawayOptions(): JsonResponse
    {
        $fallbackImage = asset('images/placeholder.png');

        $giveaways = Product::query()
            ->with(['template', 'uploads', 'images', 'bulkOrders'])
            ->where('product_type', 'Giveaway')
            ->whereHas('uploads')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Product $product) use ($fallbackImage) {
                $payload = $this->formatGiveawayProduct($product, $fallbackImage);
                $payload['metadata'] = [
                    'id' => $payload['product_id'],
                    'name' => $payload['name'],
                    'price' => $payload['price'],
                    'image' => $payload['image'],
                    'min_qty' => $payload['min_qty'],
                    'max_qty' => $payload['max_qty'],
                    'step' => $payload['step'],
                ];

                return $payload;
            })
            ->values();

        return response()->json($giveaways);
    }

    private function formatGiveawayProduct(Product $product, ?string $fallbackImage = null): array
    {
        $images = $this->orderFlow->resolveProductImages($product);
        $unitPrice = $this->orderFlow->unitPriceFor($product);
        $bulkTier = $product->bulkOrders->sortBy('min_qty')->first();
        $templateId = $product->template?->id ?? $product->template_id;

        $designUrl = null;
        if ($templateId) {
            $designUrl = route('design.studio', [
                'template' => $templateId,
                'product' => $product->id,
            ]);
        } elseif (Route::has('design.edit')) {
            $designUrl = route('design.edit', ['product' => $product->id]);
        }

        $primaryImage = $images['front']
            ?? ($images['all'][0] ?? null)
            ?? $fallbackImage
            ?? asset('images/no-image.png');

        $defaultQty = max($bulkTier?->min_qty ?? $this->orderFlow->defaultQuantityFor($product), 1);

        $tiers = $product->bulkOrders->map(fn ($tier) => [
            'min_qty' => $tier->min_qty,
            'max_qty' => $tier->max_qty,
            'price' => $tier->price_per_unit,
        ])->values()->toArray();

        return [
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name ?? 'Giveaway',
            'price' => $unitPrice,
            'tiers' => $tiers,
            'image' => $primaryImage,
            'images' => $images['all'] ?? [],
            'description' => Str::limit(strip_tags($product->description ?? ''), 220),
            'material' => null,
            'min_qty' => $bulkTier?->min_qty ?? $defaultQty,
            'max_qty' => $bulkTier?->max_qty,
            'step' => max(1, $bulkTier?->min_qty ?? 5),
            'default_qty' => $defaultQty,
            'preview_url' => route('product.preview', $product->id),
            'event_type' => $product->event_type ?: null,
            'theme_style' => $product->theme_style ?: null,
            'updated_at' => $product->updated_at?->toIso8601String(),
            'template_id' => $templateId,
            'design_url' => $designUrl,
        ];
    }

    /**
     * Temporary debug helper that returns resolved image URLs for giveaway products.
     */
    public function debugGiveawayImages(): JsonResponse
    {
        $giveaways = \App\Models\Product::query()
            ->with(['template', 'uploads', 'images'])
            ->where('product_type', 'Giveaway')
            ->whereHas('uploads')
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
            // session-only flow: persist envelope selection into the session summary
            $payload = $request->validated();
            $summary = session(static::SESSION_SUMMARY_KEY) ?? [];

            $quantity = max(1, (int) ($payload['quantity'] ?? 0));
            $unitPrice = (float) ($payload['unit_price'] ?? 0);
            $total = $payload['total_price'] ?? null;
            if ($total === null) {
                $total = $quantity * $unitPrice;
            }

            $envelopeMeta = $payload['metadata'] ?? [];
            $resolvedEnvelope = $request->resolvedEnvelope();
            $availability = $request->resolvedEnvelopeAvailability();

            $maxQuantity = Arr::get($availability, 'max_quantity', $envelopeMeta['max_qty'] ?? null);
            if ($maxQuantity !== null) {
                $maxQuantity = (int) $maxQuantity;
                if ($maxQuantity >= 1 && $quantity > $maxQuantity) {
                    $quantity = $maxQuantity;
                    $total = $quantity * $unitPrice;
                }
            }

            $availableStock = Arr::get($availability, 'available_stock');
            $materialName = $envelopeMeta['material']
                ?? $resolvedEnvelope?->envelope_material_name
                ?? Arr::get($availability, 'material.material_name');

            $meta = array_filter([
                'id' => $payload['envelope_id'] ?? $envelopeMeta['id'] ?? null,
                'product_id' => $payload['product_id'] ?? $resolvedEnvelope?->product_id,
                'name' => $envelopeMeta['name'] ?? null,
                'price' => $unitPrice,
                'qty' => $quantity,
                'total' => (float) $total,
                'material' => $materialName,
                'image' => $envelopeMeta['image'] ?? null,
                'min_qty' => $envelopeMeta['min_qty'] ?? null,
                'max_qty' => $maxQuantity,
                'available_stock' => $availableStock,
                'material_id' => $resolvedEnvelope?->material_id,
                'updated_at' => now()->toIso8601String(),
            ], fn ($v) => $v !== null && $v !== '');

            $summary['giveaway'] = $summary['giveaway'] ?? null; // keep any existing
            $summary['envelope'] = $meta;
            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['envelope'] = (float) $meta['total'];

            // update totals
            $summary['subtotalAmount'] = ($summary['subtotalAmount'] ?? 0);
            $summary['taxAmount'] = round(($summary['subtotalAmount']) * static::DEFAULT_TAX_RATE, 2);
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['shippingFee'] ?? static::DEFAULT_SHIPPING_FEE) + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

            session()->put(static::SESSION_SUMMARY_KEY, $summary);

            return response()->json([
                'message' => 'Envelope selection saved to session.',
                'order_id' => null,
                'order_number' => null,
                'summary' => $summary,
            ]);
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
            $summary = session(static::SESSION_SUMMARY_KEY) ?? [];
            unset($summary['envelope']);
            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['envelope'] = 0;
            session()->put(static::SESSION_SUMMARY_KEY, $summary);

            return response()->json([
                'message' => 'Envelope selection cleared from session.',
                'order_id' => null,
                'order_number' => null,
                'summary' => $summary,
            ]);
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
            $payload = $request->validated();
            $summary = session(static::SESSION_SUMMARY_KEY) ?? [];

            $productId = (int) ($payload['product_id'] ?? 0);
            $product = $productId ? Product::with(['template', 'uploads', 'images', 'bulkOrders'])->find($productId) : null;

            $quantity = max(1, (int) ($payload['quantity'] ?? 0));
            $payloadUnitPrice = $payload['unit_price'] ?? null;
            $unitPrice = $payloadUnitPrice !== null ? (float) $payloadUnitPrice : ($product ? $this->orderFlow->unitPriceFor($product) : 0);
            $providedTotal = $payload['total_price'] ?? null;
            $total = $providedTotal !== null ? (float) $providedTotal : round($unitPrice * $quantity, 2);

            $metadata = $payload['metadata'] ?? [];
            $resolvedImages = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
            $normalizedImages = array_values(array_filter($metadata['images'] ?? $resolvedImages['all'] ?? [], fn ($src) => is_string($src) && trim($src) !== ''));

            $meta = array_filter([
                'id' => $metadata['id'] ?? $product?->id,
                'product_id' => $product?->id,
                'name' => $metadata['name'] ?? $product?->name,
                'price' => $unitPrice,
                'qty' => $quantity,
                'total' => round($total, 2),
                'image' => $metadata['image'] ?? ($normalizedImages[0] ?? $resolvedImages['front'] ?? null),
                'images' => $normalizedImages,
                'description' => $metadata['description'] ?? ($product?->description ? Str::limit(strip_tags($product->description), 220) : null),
                'max_qty' => $metadata['max_qty'] ?? null,
                'min_qty' => $metadata['min_qty'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ], function ($value, $key) {
                if ($key === 'images') {
                    return is_array($value) && !empty($value);
                }

                return $value !== null && $value !== '';
            }, ARRAY_FILTER_USE_BOTH);

            $giveaways = $summary['giveaways'] ?? [];
            if (empty($giveaways) && !empty($summary['giveaway'])) {
                $oldId = $summary['giveaway']['product_id'] ?? $summary['giveaway']['id'] ?? null;
                if ($oldId) {
                    $giveaways[$oldId] = $summary['giveaway'];
                }
            }
            
            $giveaways[$product->id] = $meta;
            $summary['giveaways'] = $giveaways;
            unset($summary['giveaway']);

            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['giveaway'] = (float) collect($giveaways)->sum('total');

            // update totals
            $summary['subtotalAmount'] = ($summary['subtotalAmount'] ?? 0);
            $summary['taxAmount'] = round(($summary['subtotalAmount']) * static::DEFAULT_TAX_RATE, 2);
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['shippingFee'] ?? static::DEFAULT_SHIPPING_FEE) + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

            session()->put(static::SESSION_SUMMARY_KEY, $summary);

            return response()->json([
                'message' => 'Giveaway added to session.',
                'order_id' => null,
                'order_number' => null,
                'summary' => $summary,
            ]);
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

    public function clearGiveaway(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $order = $this->currentOrder();
        
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY) ?? [];
            
            if ($productId) {
                $giveaways = $summary['giveaways'] ?? [];
                // Migrate old single giveaway if exists
                if (empty($giveaways) && !empty($summary['giveaway'])) {
                    $oldId = $summary['giveaway']['product_id'] ?? $summary['giveaway']['id'] ?? null;
                    if ($oldId) {
                        $giveaways[$oldId] = $summary['giveaway'];
                    }
                }
                
                unset($giveaways[$productId]);
                $summary['giveaways'] = $giveaways;
                unset($summary['giveaway']);
            } else {
                unset($summary['giveaway']);
                unset($summary['giveaways']);
            }

            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['giveaway'] = (float) collect($summary['giveaways'] ?? [])->sum('total');
            
            // update totals
            $summary['subtotalAmount'] = ($summary['subtotalAmount'] ?? 0);
            $summary['taxAmount'] = round(($summary['subtotalAmount']) * static::DEFAULT_TAX_RATE, 2);
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['shippingFee'] ?? static::DEFAULT_SHIPPING_FEE) + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

            session()->put(static::SESSION_SUMMARY_KEY, $summary);

            return response()->json([
                'message' => 'Giveaway selection cleared from session.',
                'order_id' => null,
                'order_number' => null,
                'summary' => $summary,
            ]);
        }

        $updatedOrder = $order;

        DB::transaction(function () use ($order, $productId, &$updatedOrder) {
            $updatedOrder = $this->orderFlow->clearGiveawaySelection($order, $productId);
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

        // Always render the cart page. If there is a persisted order, refresh
        // the session summary from it. Otherwise, allow the client-side
        // sessionStorage (inkwise-finalstep) to supply the draft payload.

        if ($order) {
            $this->updateSessionSummary($order);
        }

        $summary = session(static::SESSION_SUMMARY_KEY) ?? null;

        // If the session summary exists but lacks option lists (quantity, paper,
        // addons), attempt to enrich it from the product so the cart view can
        // render selection dropdowns even when the user didn't visit finalStep.
        if ($summary && is_array($summary)) {
            $needsQuantity = empty($summary['quantityOptions']);
            $needsPaper = empty($summary['paperStockOptions']);
            $needsAddons = empty($summary['addonGroups']);

            if ($needsQuantity || $needsPaper || $needsAddons) {
                $product = $this->orderFlow->resolveProduct(null, $summary['productId'] ?? null);
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

                if ($needsQuantity) {
                    $summary['quantityOptions'] = $this->orderFlow->buildQuantityOptions($product, $summary['quantity'] ?? null);
                }
                if ($needsPaper) {
                    $summary['paperStockOptions'] = $this->orderFlow->buildPaperStockOptions($product, $summary['paperStockId'] ?? null);
                    // Also populate simple paper stock selection fields for display
                    $firstPaper = $product && $product->paperStocks->isNotEmpty() ? $product->paperStocks->first() : null;
                    if ($firstPaper) {
                        $summary['paperStockId'] = $summary['paperStockId'] ?? $firstPaper->id;
                        $summary['paperStockName'] = $summary['paperStockName'] ?? $firstPaper->name;
                        $summary['paperStockPrice'] = $summary['paperStockPrice'] ?? $firstPaper->price;
                        $summary['previewSelections'] = $summary['previewSelections'] ?? [];
                        $summary['previewSelections']['paper_stock'] = $summary['previewSelections']['paper_stock'] ?? [
                            'id' => $summary['paperStockId'],
                            'name' => $summary['paperStockName'],
                            'price' => $summary['paperStockPrice'],
                        ];
                    }
                }
                if ($needsAddons) {
                    $summary['addonGroups'] = $this->orderFlow->buildAddonGroups($product, $summary['addonIds'] ?? []);
                    // Also provide a simplified addons array (id,name,price) for legacy consumers
                    $summary['addons'] = $summary['addons'] ?? $product->addons->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'name' => $a->name ?? 'Add-on',
                            'price' => $a->price ?? 0,
                            'type' => $a->addon_type ?? null,
                        ];
                    })->values()->all();
                }

                // Persist the enriched summary so subsequent calls and the
                // client-side script receive the option lists.
                session()->put(static::SESSION_SUMMARY_KEY, $summary);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'order_id' => $order?->id ?? null,
                'order_number' => $order?->order_number ?? null,
                'data' => $summary,
                'updated_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        return view('customer.orderflow.mycart', [
            'order' => $order ?? null,
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

    public function syncSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'summary' => ['required', 'array'],
        ]);

        $incoming = $this->sanitizeSummaryPayload($data['summary']);
        $existing = session(static::SESSION_SUMMARY_KEY) ?? [];

        $base = array_merge(
            Arr::except($existing, ['envelope', 'giveaway', 'extras', 'hasEnvelope', 'hasGiveaway']),
            Arr::except($incoming, ['envelope', 'giveaway', 'extras'])
        );

        if (array_key_exists('extras', $incoming)) {
            $base['extras'] = array_merge($existing['extras'] ?? [], $incoming['extras']);
        } elseif (isset($existing['extras'])) {
            $base['extras'] = $existing['extras'];
        }

        if (array_key_exists('envelope', $incoming)) {
            if (!empty($incoming['envelope'])) {
                $base['envelope'] = $incoming['envelope'];
                $base['hasEnvelope'] = true;
            } else {
                unset($base['envelope']);
                $base['hasEnvelope'] = false;
            }
        } elseif (isset($existing['envelope'])) {
            $base['envelope'] = $existing['envelope'];
            $base['hasEnvelope'] = $existing['hasEnvelope'] ?? true;
        }

        if (array_key_exists('giveaway', $incoming)) {
            if (!empty($incoming['giveaway'])) {
                $base['giveaway'] = $incoming['giveaway'];
                $base['hasGiveaway'] = true;
            } else {
                unset($base['giveaway']);
                $base['hasGiveaway'] = false;
            }
        } elseif (isset($existing['giveaway'])) {
            $base['giveaway'] = $existing['giveaway'];
            $base['hasGiveaway'] = $existing['hasGiveaway'] ?? true;
        }

        session()->put(static::SESSION_SUMMARY_KEY, $base);

        $order = $this->currentOrder();
        if ($order) {
            DB::transaction(function () use (&$order, $base) {
                $order = $this->orderFlow->initializeOrderFromSummary($order, $base);
                $this->orderFlow->recalculateOrderTotals($order);
                $order->refresh();
                $this->updateSessionSummary($order);
            });
        }

        return response()->json([
            'message' => 'Order summary synced.',
            'order_id' => $order?->id,
            'order_number' => $order?->order_number,
            'summary' => session(static::SESSION_SUMMARY_KEY),
        ]);
    }

    /**
     * Debug helper: return the current session order summary payload.
     * Only allowed in local environment or when allow_debug=1 is present.
     */
    public function debugSessionSummary(Request $request): JsonResponse
    {
        $allow = config('app.env') === 'local' || $request->query('allow_debug') == '1';
        if (!$allow) {
            return response()->json(['message' => 'Debug endpoint not available.'], 403);
        }

        $summary = session(static::SESSION_SUMMARY_KEY) ?? null;
        $preview = null;
        try {
            $raw = session()->get('inkwise-preview-selections');
            $preview = $raw;
        } catch (\Throwable $e) {
            $preview = null;
        }

        return response()->json([
            'summary' => $summary,
            'preview_store' => $preview,
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
        $orderJustCreated = false;

        // Check if order is already in production or completed - redirect to appropriate page
        if ($order) {
            if ($order->status === 'in_production') {
                return redirect()->route('customer.my_purchase.inproduction');
            }
            if ($order->status === 'completed') {
                return redirect()->route('customer.my_purchase.completed');
            }
            if ($order->status === 'cancelled') {
                return redirect()->route('customer.my_purchase.cancelled');
            }
            // For other statuses like 'draft' or 'pending', continue with checkout
        }

        // If there is no persisted Order yet, but we have a session summary,
        // persist the Order now because Checkout is the place where we commit
        // the user's selections to the database.
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || empty($summary['productId'])) {
                return $this->redirectToCatalog();
            }

            DB::transaction(function () use (&$order, $summary, &$orderJustCreated) {
                $this->clearExistingOrder();

                $customerOrder = $this->orderFlow->createCustomerOrder(Auth::user());

                $metadata = $this->orderFlow->buildInitialOrderMetadata($summary);

                $order = $customerOrder->orders()->create([
                    'customer_id' => $customerOrder->customer_id,
                    'user_id' => optional(Auth::user())->user_id,
                    'order_number' => $this->orderFlow->generateOrderNumber(),
                    'status' => 'draft',
                    'subtotal_amount' => $summary['subtotalAmount'] ?? 0,
                    'tax_amount' => $summary['taxAmount'] ?? 0,
                    'shipping_fee' => $summary['shippingFee'] ?? static::DEFAULT_SHIPPING_FEE,
                    'total_amount' => $summary['totalAmount'] ?? 0,
                    'shipping_option' => 'standard',
                    'payment_method' => null,
                    'payment_status' => 'pending',
                    'summary_snapshot' => null,
                    'metadata' => $metadata,
                ]);

                $orderJustCreated = true;

                $order = $this->orderFlow->initializeOrderFromSummary($order, $summary);

                $this->orderFlow->recalculateOrderTotals($order);

                $order = $order->refresh(['items.product', 'items.addons', 'items.paperStockSelection']);

                $primaryItem = $this->orderFlow->primaryInvitationItem($order);
                if ($primaryItem instanceof OrderItem) {
                    $order->update([
                        'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                    ]);
                }

                session()->put(static::SESSION_ORDER_ID, $order->id);
            });

            // reload order
            $order = $this->currentOrder();
        }

        // Mark that the current session is allowed to submit final-step for this order
        session()->put('order_checkout_allowed_for', $order->id);

        if ($order->payment_method !== 'gcash') {
            $order->update([
                'payment_method' => 'gcash',
                // mark pending when GCash is chosen unless already paid
                'payment_status' => $order->payment_status === 'paid' ? 'paid' : 'pending',
            ]);
        }

        $metadata = $order->metadata ?? [];
        $payments = collect($metadata['payments'] ?? []);
        $paidAmount = round($payments
            ->filter(fn ($payment) => ($payment['status'] ?? null) === 'paid')
            ->sum(fn ($payment) => (float) ($payment['amount'] ?? 0)), 2);

        $balanceDue = round(max(($order->total_amount ?? 0) - $paidAmount, 0), 2);
        $defaultDeposit = round(max($order->total_amount / 2, 0), 2);
        $depositAmount = $balanceDue <= 0 ? 0 : min($defaultDeposit, $balanceDue);

        $paymongoMeta = $metadata['paymongo'] ?? [];

        $order->refresh();
        $this->updateSessionSummary($order);

        if ($orderJustCreated) {
            $this->notifyTeamOfNewOrder($order);
        }

        return view('customer.orderflow.checkout', [
            'order' => $order->loadMissing(['items.product', 'items.addons', 'customerOrder']),
            'depositAmount' => $depositAmount,
            'paidAmount' => $paidAmount,
            'balanceDue' => $balanceDue,
            'paymentRecords' => $payments->values()->all(),
            'paymongoMeta' => $paymongoMeta,
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
            'payment_status' => 'paid',
            'metadata' => $metadata,
        ]);

        $this->updateSessionSummary($order);

        return redirect()
            ->route('customer.my_purchase.inproduction')
            ->with('status', 'Thanks! Your order is now in production.');
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

        // Support session-only flow: if there's no persisted order, allow the
        // giveaways page to be rendered from the session summary created in
        // the editor flow. Otherwise, redirect the user to the catalog.
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || empty($summary['productId'])) {
                return $this->redirectToCatalog();
            }

            // Render giveaways view using session summary
            $orderSummary = $summary;
        } else {
            $this->updateSessionSummary($order);
            $orderSummary = session(static::SESSION_SUMMARY_KEY);
        }

        $initialCatalog = Product::query()
            ->with(['template', 'uploads', 'images', 'bulkOrders'])
            ->where('product_type', 'Giveaway')
            ->whereHas('uploads')
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get()
            ->map(fn (Product $product) => $this->formatGiveawayProduct($product))
            ->values();

        return view('customer.orderflow.giveaways', [
            'order' => $order,
            'orderSummary' => $orderSummary,
            'initialCatalog' => $initialCatalog,
        ]);
    }

    private function normalizeDesignAutosavePayload(array $design, array $placeholders): array
    {
        $updatedAt = Arr::get($design, 'updated_at');
        if (!is_string($updatedAt) || trim($updatedAt) === '') {
            $updatedAt = Carbon::now()->toIso8601String();
        }

        $sides = [];
        $rawSides = Arr::get($design, 'sides', []);
        if (is_array($rawSides)) {
            foreach ($rawSides as $key => $side) {
                if (!is_array($side)) {
                    continue;
                }

                $normalizedSide = array_filter([
                    'svg' => is_string($side['svg'] ?? null) ? $side['svg'] : null,
                    'preview' => is_string($side['preview'] ?? null) ? $side['preview'] : null,
                ], fn ($value) => is_string($value) && trim($value) !== '');

                if (!empty($normalizedSide)) {
                    $sides[(string) $key] = $normalizedSide;
                }
            }
        }

        $textEntries = [];
        foreach ((array) Arr::get($design, 'texts', []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $textEntries[] = [
                'key' => (string) ($entry['key'] ?? ''),
                'label' => (string) ($entry['label'] ?? ''),
                'value' => (string) ($entry['value'] ?? ''),
                'defaultValue' => (string) ($entry['defaultValue'] ?? ''),
            ];
        }

        $imageEntries = [];
        foreach ((array) Arr::get($design, 'images', []) as $image) {
            if (!is_array($image)) {
                continue;
            }

            $imageEntries[] = array_filter([
                'key' => $image['key'] ?? null,
                'href' => $image['href'] ?? null,
                'x' => $image['x'] ?? null,
                'y' => $image['y'] ?? null,
                'width' => $image['width'] ?? null,
                'height' => $image['height'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        $canvas = null;
        $rawCanvas = Arr::get($design, 'canvas');
        if (is_array($rawCanvas)) {
            $canvas = array_filter([
                'width' => isset($rawCanvas['width']) ? (float) $rawCanvas['width'] : null,
                'height' => isset($rawCanvas['height']) ? (float) $rawCanvas['height'] : null,
                'unit' => is_string($rawCanvas['unit'] ?? null) ? $rawCanvas['unit'] : null,
                'shape' => is_string($rawCanvas['shape'] ?? null) ? $rawCanvas['shape'] : null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return array_filter([
            'updated_at' => $updatedAt,
            'sides' => $sides ?: null,
            'texts' => $textEntries,
            'images' => $imageEntries,
            'canvas' => $canvas,
            'placeholders' => $placeholders,
        ], function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }

            return $value !== null && $value !== '';
        });
    }

    private function deriveDesignPreviewImages(array $preview, array $designMeta): array
    {
        $candidates = [];

        $primary = Arr::get($preview, 'image');
        if (is_string($primary) && trim($primary) !== '') {
            $candidates[] = trim($primary);
        }

        foreach ((array) Arr::get($preview, 'images', []) as $image) {
            if (is_string($image) && trim($image) !== '') {
                $candidates[] = trim($image);
            }
        }

        foreach ((array) Arr::get($designMeta, 'sides', []) as $side) {
            if (!is_array($side)) {
                continue;
            }

            $previewValue = $side['preview'] ?? null;
            if (is_string($previewValue) && trim($previewValue) !== '') {
                $candidates[] = trim($previewValue);
            }
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            if (!in_array($candidate, $unique, true)) {
                $unique[] = $candidate;
            }
        }

        return $unique;
    }

    private function sanitizeSummaryPayload(array $payload): array
    {
        $summary = Arr::only($payload, [
            'orderId',
            'orderNumber',
            'orderStatus',
            'paymentStatus',
            'productId',
            'productName',
            'quantity',
            'unitPrice',
            'subtotalAmount',
            'taxAmount',
            'shippingFee',
            'totalAmount',
            'previewImages',
            'previewImage',
            'invitationImage',
            'paperStockId',
            'paperStockName',
            'paperStockPrice',
            'addons',
            'addonIds',
            'metadata',
            'extras',
            'envelope',
            'giveaway',
            'placeholders',
            'previewSelections',
        ]);

        if (isset($summary['quantity'])) {
            $summary['quantity'] = max(0, (int) $summary['quantity']);
        }

        foreach (['unitPrice', 'subtotalAmount', 'taxAmount', 'shippingFee', 'totalAmount', 'paperStockPrice'] as $field) {
            if (isset($summary[$field]) && $summary[$field] !== null && $summary[$field] !== '') {
                $summary[$field] = (float) $summary[$field];
            }
        }

        if (isset($summary['addonIds']) && is_array($summary['addonIds'])) {
            $summary['addonIds'] = array_values(array_filter(array_map(function ($id) {
                if (is_int($id)) {
                    return $id;
                }

                if (is_string($id) && is_numeric($id)) {
                    return (int) $id;
                }

                if (is_float($id) || is_numeric($id)) {
                    return (int) $id;
                }

                return null;
            }, $summary['addonIds'])));
        }

        if (isset($summary['addons']) && is_array($summary['addons'])) {
            $summary['addons'] = array_values(array_filter(array_map(function ($addon) {
                if (!is_array($addon)) {
                    return null;
                }

                if (isset($addon['id']) && is_numeric($addon['id'])) {
                    $addon['id'] = (int) $addon['id'];
                }

                if (isset($addon['price']) && $addon['price'] !== null && $addon['price'] !== '') {
                    $addon['price'] = (float) $addon['price'];
                }

                return $addon;
            }, $summary['addons'])));
        } else {
            unset($summary['addons']);
        }

        if (isset($summary['metadata']) && !is_array($summary['metadata'])) {
            unset($summary['metadata']);
        }

        if (isset($summary['previewImages']) && !is_array($summary['previewImages'])) {
            unset($summary['previewImages']);
        }

        if (isset($summary['placeholders']) && !is_array($summary['placeholders'])) {
            unset($summary['placeholders']);
        }

        if (isset($summary['previewSelections']) && !is_array($summary['previewSelections'])) {
            unset($summary['previewSelections']);
        }

        if (array_key_exists('extras', $summary)) {
            $summary['extras'] = $this->sanitizeExtras($summary['extras']);
        }

        $envelope = Arr::get($summary, 'envelope');
        if (is_array($envelope)) {
            $summary['envelope'] = $this->sanitizeLineSummary($envelope);
        } else {
            unset($summary['envelope']);
        }

        $giveaway = Arr::get($summary, 'giveaway');
        if (is_array($giveaway)) {
            $summary['giveaway'] = $this->sanitizeLineSummary($giveaway);
        } else {
            unset($summary['giveaway']);
        }

        return $summary;
    }

    private function sanitizeExtras($extras): array
    {
        if (!is_array($extras)) {
            return [
                'paper' => 0.0,
                'addons' => 0.0,
                'envelope' => 0.0,
                'giveaway' => 0.0,
            ];
        }

        $normalized = [
            'paper' => (float) ($extras['paper'] ?? 0),
            'addons' => (float) ($extras['addons'] ?? 0),
            'envelope' => (float) ($extras['envelope'] ?? 0),
            'giveaway' => (float) ($extras['giveaway'] ?? 0),
        ];

        if (isset($extras['ink']) && is_array($extras['ink'])) {
            $ink = $extras['ink'];
            if (isset($ink['usage_per_invite_ml'])) {
                $ink['usage_per_invite_ml'] = (float) $ink['usage_per_invite_ml'];
            }
            if (isset($ink['unit_price_per_ml'])) {
                $ink['unit_price_per_ml'] = (float) $ink['unit_price_per_ml'];
            }
            if (isset($ink['total'])) {
                $ink['total'] = (float) $ink['total'];
            }
            $normalized['ink'] = $ink;
        }

        return $normalized;
    }

    private function sanitizeLineSummary(array $meta): array
    {
        $line = $meta;

        if (isset($line['qty'])) {
            $qty = (int) $line['qty'];
            $line['qty'] = $qty > 0 ? $qty : 0;
        }

        if (isset($line['quantity'])) {
            $qty = (int) $line['quantity'];
            $line['quantity'] = $qty > 0 ? $qty : 0;
        }

        foreach (['price', 'unit_price', 'unitPrice', 'total', 'total_price'] as $key) {
            if (isset($line[$key]) && $line[$key] !== null && $line[$key] !== '') {
                $line[$key] = (float) $line[$key];
            }
        }

        if (isset($line['addons']) && is_array($line['addons'])) {
            $line['addons'] = array_values(array_filter(array_map(function ($addon) {
                if (!is_array($addon)) {
                    return null;
                }

                if (isset($addon['id']) && is_numeric($addon['id'])) {
                    $addon['id'] = (int) $addon['id'];
                }

                if (isset($addon['price']) && $addon['price'] !== null && $addon['price'] !== '') {
                    $addon['price'] = (float) $addon['price'];
                }

                return $addon;
            }, $line['addons'])));
        }

        return array_filter($line, static fn ($value) => $value !== null && $value !== '');
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

    private function notifyTeamOfNewOrder(Order $order): void
    {
        try {
            $order->loadMissing(['customerOrder', 'customer', 'user']);
        } catch (\Throwable $e) {
            // Ignore load failures and proceed with available data.
        }

        $customerName = trim((string) ($order->customerOrder->name ?? ''));

        if ($customerName === '') {
            $customerName = trim(implode(' ', array_filter([
                $order->customer->first_name ?? null,
                $order->customer->last_name ?? null,
            ])));
        }

        if ($customerName === '' && $order->user) {
            $customerName = (string) ($order->user->name ?? '');
        }

        if ($customerName === '') {
            $customerName = 'Customer';
        }

        try {
            $orderSummaryUrl = route('admin.ordersummary.index', ['order' => $order->order_number ?? $order->id]);
        } catch (\Throwable $e) {
            $orderSummaryUrl = url('/admin/ordersummary/' . ($order->order_number ?? $order->id));
        }

        $recipients = User::query()
            ->whereIn('role', ['admin', 'owner'])
            ->where('status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $totalAmount = (float) ($order->total_amount ?? 0);

        Notification::send(
            $recipients,
            new NewOrderPlaced($order->id, $order->order_number, $customerName, $totalAmount, $orderSummaryUrl)
        );
    }

    private function resolvePickupDate(?Order $order, ?array $summaryPayload, Carbon $minDate, Carbon $maxDate): Carbon
    {
        $candidate = null;

        if ($order && $order->date_needed instanceof Carbon) {
            $candidate = $order->date_needed->copy();
        }

        if (!$candidate && $order) {
            $metadata = $order->metadata;
            if (is_string($metadata)) {
                $decoded = json_decode($metadata, true);
                $metadata = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($metadata)) {
                $metadata = [];
            }

            $metadataCandidate = Arr::get($metadata, 'final_step.estimated_date')
                ?? Arr::get($metadata, 'final_step.metadata.estimated_date')
                ?? Arr::get($metadata, 'delivery.estimated_pickup_date')
                ?? Arr::get($metadata, 'delivery.estimated_ship_date');

            if ($metadataCandidate) {
                try {
                    $candidate = Carbon::parse($metadataCandidate);
                } catch (\Throwable $e) {
                    $candidate = null;
                }
            }
        }

        if (!$candidate && $summaryPayload) {
            $summaryCandidate = Arr::get($summaryPayload, 'dateNeeded')
                ?? Arr::get($summaryPayload, 'estimatedDate')
                ?? Arr::get($summaryPayload, 'estimated_date')
                ?? Arr::get($summaryPayload, 'metadata.final_step.estimated_date')
                ?? Arr::get($summaryPayload, 'metadata.final_step.metadata.estimated_date');

            if ($summaryCandidate) {
                try {
                    $candidate = Carbon::parse($summaryCandidate);
                } catch (\Throwable $e) {
                    $candidate = null;
                }
            }
        }

        if (!$candidate) {
            $candidate = Carbon::now()->addMonth();
        }

        $candidate = $candidate->copy()->startOfDay();

        if ($candidate->lt($minDate)) {
            $candidate = $minDate->copy();
        }

        if ($candidate->gt($maxDate)) {
            $candidate = $maxDate->copy();
        }

        return $candidate;
    }

    private function readInlineSvg(?string $path): array
    {
        $result = [
            'content' => null,
            'analysis' => null,
        ];

        if (!$path || !Str::endsWith(Str::lower($path), '.svg')) {
            return $result;
        }

        $raw = null;

        if (Str::startsWith($path, ['http://', 'https://'])) {
            try {
                $raw = @file_get_contents($path);
            } catch (\Throwable $e) {
                return $result;
            }
        } else {
            $normalized = str_replace('\\', '/', $path);
            $variants = [
                $normalized,
                ltrim($normalized, '/'),
            ];

            if (Str::contains($normalized, 'ink-wise/')) {
                $variants[] = Str::after($normalized, 'ink-wise/');
            }
            if (Str::contains($normalized, 'public/')) {
                $variants[] = Str::after($normalized, 'public/');
            }
            if (Str::contains($normalized, 'storage/')) {
                $variants[] = Str::after($normalized, 'storage/');
            }

            $variants = array_filter(array_unique($variants), static fn ($value) => is_string($value) && $value !== '');
            $candidates = [];

            foreach ($variants as $variant) {
                if (Storage::disk('public')->exists($variant)) {
                    $candidates[] = Storage::disk('public')->path($variant);
                }

                $candidates[] = public_path($variant);
                $candidates[] = public_path('storage/' . ltrim($variant, '/'));
                $candidates[] = base_path($variant);
            }

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && is_file($candidate)) {
                    $raw = @file_get_contents($candidate);
                    if ($raw !== false) {
                        break;
                    }
                }
            }
        }

        if (!$raw) {
            return $result;
        }

        $raw = preg_replace('/<\?xml[^>]*\?>/i', '', $raw);
        $raw = preg_replace('/<!DOCTYPE[^>]*>/i', '', $raw);
        $raw = trim($raw ?? '');

        if ($raw === '') {
            return $result;
        }

        $processedResult = null;

        // Process SVG with auto-parser if it's from Figma (check for Figma patterns)
        $isFigmaImport = strpos($raw, 'figma') !== false ||
            strpos($raw, 'Figma') !== false ||
            preg_match('/<!--\s*Generated\s*by\s*Figma/i', $raw);

        try {
            $svgParser = app(\App\Services\SvgAutoParser::class);

            if ($isFigmaImport) {
                $processedResult = $svgParser->processFigmaImportedSvg($raw);
            } else {
                $processedResult = $svgParser->processSvgContent($raw);
            }

            if (isset($processedResult['content']) && !empty($processedResult['content'])) {
                $raw = $processedResult['content'];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::info('SVG processing failed in readInlineSvg: ' . $e->getMessage());
        }

        $previous = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($raw, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);

        if (!$loaded) {
            libxml_use_internal_errors($previous);
            libxml_clear_errors();

            $result['content'] = trim($raw);
            if (is_array($processedResult)) {
                $result['analysis'] = [
                    'metadata' => $processedResult['metadata'] ?? [],
                    'text_elements' => $processedResult['text_elements'] ?? [],
                    'image_elements' => $processedResult['image_elements'] ?? [],
                    'changeable_images' => $processedResult['changeable_images'] ?? [],
                ];
            }

            return $result;
        }

        $svg = $dom->documentElement;
        if ($svg && $svg->nodeName === 'svg') {
            $scripts = [];
            foreach ($svg->getElementsByTagName('script') as $script) {
                $scripts[] = $script;
            }
            foreach ($scripts as $script) {
                if ($script->parentNode) {
                    $script->parentNode->removeChild($script);
                }
            }

            if (!$svg->hasAttribute('data-inline-template')) {
                $svg->setAttribute('data-inline-template', 'true');
            }

            $raw = $dom->saveXML($svg) ?: $raw;
            $raw = preg_replace('/<\?xml[^>]*\?>/i', '', $raw);
        }

        libxml_use_internal_errors($previous);
        libxml_clear_errors();

        $result['content'] = trim($raw);
        if (is_array($processedResult)) {
            $result['analysis'] = [
                'metadata' => $processedResult['metadata'] ?? [],
                'text_elements' => $processedResult['text_elements'] ?? [],
                'image_elements' => $processedResult['image_elements'] ?? [],
                'changeable_images' => $processedResult['changeable_images'] ?? [],
            ];
        }

        return $result;
    }

    private function buildTemplateParserSummary(?Product $product, array $frontSvgData, array $backSvgData): array
    {
        $templateMetadata = $product?->template?->metadata ?? [];
        $figmaProcessing = Arr::get($templateMetadata, 'figma_processing', []);

        $front = $this->buildSideParserSummary('front', $frontSvgData, Arr::get($figmaProcessing, 'front', []));
        $back = $this->buildSideParserSummary('back', $backSvgData, Arr::get($figmaProcessing, 'back', []));

        $warnings = array_values(array_unique(array_filter(array_merge(
            $front['warnings'],
            $back['warnings'],
        ))));

        return [
            'front' => $front,
            'back' => $back,
            'warnings' => $warnings,
            'has_front' => $front['has_content'],
            'has_back' => $back['has_content'],
        ];
    }

    private function buildSideParserSummary(string $side, array $svgData, array $storedMetadata): array
    {
        $content = $svgData['content'] ?? null;
        $analysis = $svgData['analysis'] ?? [];

        $inlineMeta = Arr::get($analysis, 'metadata', []);
        $textElements = $this->normalizeParserList($analysis['text_elements'] ?? Arr::get($storedMetadata, 'text_elements', []));
        $changeableImages = $this->normalizeParserList($analysis['changeable_images'] ?? Arr::get($storedMetadata, 'changeable_images', []));
        $imageElements = $this->normalizeParserList($analysis['image_elements'] ?? Arr::get($storedMetadata, 'image_elements', []));

        $textCount = (int) ($inlineMeta['text_count'] ?? $storedMetadata['text_count'] ?? count($textElements));
        $changeableCount = (int) ($inlineMeta['changeable_count'] ?? $storedMetadata['changeable_count'] ?? count($changeableImages));
        $imageCount = (int) ($inlineMeta['image_count'] ?? $storedMetadata['image_count'] ?? count($imageElements));

        $shouldWarn = (bool) ($content || $textCount || $changeableCount || $imageCount);

        $warnings = [];
        if ($shouldWarn) {
            if ($textCount <= 0) {
                $warnings[] = ucfirst($side) . ' design has no editable text layers detected.';
            }
            if ($changeableCount <= 0) {
                $warnings[] = ucfirst($side) . ' design has no replaceable image areas from the import.';
            }
        }

        return [
            'text_count' => $textCount,
            'changeable_count' => $changeableCount,
            'image_count' => $imageCount,
            'text_elements' => $textElements,
            'changeable_images' => $changeableImages,
            'image_elements' => $imageElements,
            'vector_shapes_converted' => $storedMetadata['vector_shapes_converted'] ?? $inlineMeta['vector_shapes_converted'] ?? null,
            'processing_type' => $storedMetadata['processing_type'] ?? $inlineMeta['processing_type'] ?? null,
            'has_content' => (bool) $content,
            'warnings' => $warnings,
        ];
    }

    private function normalizeParserList($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value instanceof \Traversable) {
            return iterator_to_array($value, false);
        }

        return [];
    }

    private function redirectToCatalog(): RedirectResponse
    {
        return redirect()->route('templates.wedding.invitations')
            ->with('status', 'Start a new invitation to continue through the order flow.');
    }

    public function payRemainingBalance(Request $request, Order $order): RedirectResponse|ViewContract
    {
        // Ensure the order belongs to the authenticated user
        if ($order->customer_id !== Auth::user()->customer_id) {
            abort(403, 'Unauthorized access to order.');
        }

        // Check if the order is in a state where remaining balance can be paid
        if (!in_array($order->status, ['processing', 'in_production', 'confirmed'], true)) {
            return redirect()->route('customer.checkout')->with('error', 'This order is not eligible for remaining balance payment.');
        }

        // Calculate payment amounts
        $metadata = $order->metadata ?? [];
        $payments = collect($metadata['payments'] ?? []);
        $paidAmount = round($payments
            ->filter(fn ($payment) => ($payment['status'] ?? null) === 'paid')
            ->sum(fn ($payment) => (float) ($payment['amount'] ?? 0)), 2);

        $totalAmount = round($order->total_amount ?? 0, 2);
        $balanceDue = round(max($totalAmount - $paidAmount, 0), 2);

        // If no balance is due, redirect back
        if ($balanceDue <= 0) {
            return redirect()->route('customer.checkout')->with('status', 'No remaining balance due for this order.');
        }

        $paymongoMeta = $metadata['paymongo'] ?? [];

        return view('customer.orderflow.pay-remaining-balance', [
            'order' => $order->loadMissing(['items.product', 'items.addons', 'customerOrder']),
            'paidAmount' => $paidAmount,
            'balanceDue' => $balanceDue,
            'totalAmount' => $totalAmount,
            'paymentRecords' => $payments->values()->all(),
            'paymongoMeta' => $paymongoMeta,
        ]);
    }
}
