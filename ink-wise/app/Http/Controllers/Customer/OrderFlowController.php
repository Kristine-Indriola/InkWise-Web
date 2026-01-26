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
use App\Models\Template;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerReview;
use App\Models\CustomerFinalized;
use App\Models\CustomerTemplateCustom;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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

    public function storeDesignSelection(Request $request): RedirectResponse|JsonResponse
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
        ])->findOrFail($data['product_id']);
        $product->setRelation('bulkOrders', collect());

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
            $summary['shippingFee'] = static::DEFAULT_SHIPPING_FEE;
            $summary['totalAmount'] = $summary['totalAmount'] ?? $summary['total_amount'] ?? round(($summary['subtotalAmount'] ?? $subtotal) + $summary['taxAmount'], 2);
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
                'shippingFee' => static::DEFAULT_SHIPPING_FEE,
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
        try {
            Log::info('Autosave design called', ['payload' => $request->all()]);

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
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'template_id' => ['nullable', 'integer'],
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
        // Keep session light; store a stripped version while persisting full payload elsewhere
        $summary['metadata']['design'] = $this->stripHeavyDesignFields($designMeta);

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

        $order = $this->currentOrder(false);

        $product = $order?->items->first()?->product;
        if (!$product) {
            $productId = Arr::get($payload, 'product_id') ?? $summary['productId'] ?? null;
            if ($productId) {
                $product = $this->orderFlow->resolveProduct(null, (int) $productId);
            }
        }

        if ($product) {
            $summary['productId'] = $summary['productId'] ?? $product->id;
            $summary['id'] = $summary['id'] ?? $product->template_id;
            $summary['template_id'] = $summary['template_id'] ?? $product->template_id;
        } else {
            $templateId = Arr::get($payload, 'template_id');
            if ($templateId) {
                $summary['id'] = $summary['id'] ?? $templateId;
                $summary['template_id'] = $summary['template_id'] ?? $templateId;
            }
            if (!empty($payload['product_id'])) {
                $summary['productId'] = $summary['productId'] ?? (int) $payload['product_id'];
            }
        }

        session()->put(static::SESSION_SUMMARY_KEY, $summary);

        if ($product) {
            try {
                $this->orderFlow->persistDesignDraft($product, [
                    'design' => $designMeta,
                    'placeholders' => $placeholders,
                    'preview_image' => $primaryPreview,
                    'preview_images' => $previewImages,
                    'summary' => $summary,
                    'status' => $summary['orderStatus'] ?? 'draft',
                    'is_locked' => false,
                    'order_id' => $order?->id,
                    'order_item_id' => $order?->items->first()?->id,
                    'last_edited_at' => $designMeta['updated_at'] ?? Carbon::now()->toIso8601String(),
                ], Auth::user());
            } catch (\Throwable $e) {
                Log::warning('persistDesignDraft failed during autosave', ['error' => $e->getMessage()]);
            }
        }

        if ($order) {
            try {
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
            } catch (\Throwable $e) {
                Log::warning('applyDesignAutosave failed', ['error' => $e->getMessage()]);
            }
        }

        // Persist design into the active cart item when columns exist
        $cartItem = CartItem::query()
            ->where('session_id', session()->getId())
            ->latest()
            ->first();

        if ($cartItem) {
            try {
                $firstSideKey = array_key_first($designMeta['sides'] ?? []) ?? 'front';
                $firstSide = $designMeta['sides'][$firstSideKey] ?? [];
                $designSvg = is_string($firstSide['svg'] ?? null) ? $firstSide['svg'] : null;
                $designJsonEncoded = $designMeta ? json_encode($designMeta) : null;
                $canvasWidth = data_get($designMeta, 'canvas.width');
                $canvasHeight = data_get($designMeta, 'canvas.height');
                $backgroundColor = data_get($designMeta, 'canvas.background')
                    ?? data_get($designMeta, 'canvas.background_color')
                    ?? data_get($designMeta, 'background_color');

                if (Schema::hasColumn($cartItem->getTable(), 'design_svg') && $designSvg) {
                    $cartItem->design_svg = $designSvg;
                }
                if (Schema::hasColumn($cartItem->getTable(), 'design_json') && $designJsonEncoded) {
                    $cartItem->design_json = $designJsonEncoded;
                }
                if (Schema::hasColumn($cartItem->getTable(), 'canvas_width') && $canvasWidth !== null) {
                    $cartItem->canvas_width = $canvasWidth;
                }
                if (Schema::hasColumn($cartItem->getTable(), 'canvas_height') && $canvasHeight !== null) {
                    $cartItem->canvas_height = $canvasHeight;
                }
                if (Schema::hasColumn($cartItem->getTable(), 'background_color') && $backgroundColor) {
                    $cartItem->background_color = $backgroundColor;
                }
                if (Schema::hasColumn($cartItem->getTable(), 'preview_image') && $primaryPreview) {
                    $cartItem->preview_image = $primaryPreview;
                }
                $cartItem->save();
            } catch (\Throwable $e) {
                Log::warning('cartItem save failed during autosave', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'Design saved.',
            'saved_at' => $designMeta['updated_at'] ?? Carbon::now()->toIso8601String(),
            'order_id' => $order?->id,
            'summary' => $summary,
            'review_url' => route('order.review'),
        ]);
        } catch (\Throwable $e) {
            Log::error('Autosave design failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Do not block the UX; acknowledge receipt so user can continue.
            return response()->json([
                'message' => 'Design saved (degraded mode).',
                'review_url' => route('order.review'),
            ], 200);
        }
    }

    /**
     * Upload a design image for the customer studio and return its stored path/URL.
     * No file size limit enforced (front-end can send large assets); validation only checks mime type.
     */
    public function uploadDesignImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,svg,webp'],
        ]);

        $path = $request->file('image')->store('customer/designs/uploads', 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'path' => $path,
            'url' => $url,
        ]);
    }

    public function saveReviewDesign(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('saveReviewDesign called', ['request_all' => $request->all()]);

        $validated = $request->validate([
            'template_id' => ['required', 'integer'],
            'design_svg' => ['nullable', 'string'],
            'design_json' => ['nullable'],
            'preview_image' => ['nullable', 'string'],
            'preview_images' => ['nullable', 'array'],
            'canvas_width' => ['nullable', 'integer'],
            'canvas_height' => ['nullable', 'integer'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'order_item_id' => ['nullable', 'integer'],
        ]);

        $designJson = $validated['design_json'];
        if (is_string($designJson)) {
            $decoded = json_decode($designJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Invalid design_json payload.'], 422);
            }
            $designJson = $decoded;
        } elseif ($designJson === null) {
            $designJson = [];
        } elseif (!is_array($designJson)) {
            return response()->json(['message' => 'design_json must be an object or array.'], 422);
        }

        $designJson = $this->stripHeavyDesignFields($designJson);

        $incomingPreview = $validated['preview_image'] ?? null;
        if (!$incomingPreview) {
            $incomingPreview = $this->extractPreviewFromDesign($designJson);
        }

        $designSvg = $this->normalizeDesignSvg($validated['design_svg'] ?? '');

        if ($designSvg === '') {
            $designSvg = null;
        }

        $user = Auth::user();
        $customerId = $user?->customer?->customer_id;
        $orderItemId = $validated['order_item_id'] ? (int) $validated['order_item_id'] : null;

        $match = [];
        if ($orderItemId) {
            $match['order_item_id'] = $orderItemId;
        } else {
            $match['template_id'] = (int) $validated['template_id'];
            if ($customerId) {
                $match['customer_id'] = $customerId;
            }
        }

        $review = CustomerReview::query()->firstOrNew($match ?: ['template_id' => (int) $validated['template_id']]);

        // Save the preview image (PNG/SVG data URL) to file
        try {
            $previewImage = $this->persistReviewPreview($incomingPreview, $review->preview_image ?? null);
        } catch (\Throwable $e) {
            report($e);
            $previewImage = $review->preview_image ?? null;
        }

        // Handle multiple preview images (front/back) if provided - these should now be PNG data URLs from frontend
        $incomingPreviewImages = $validated['preview_images'] ?? [];
        if (is_array($incomingPreviewImages) && count($incomingPreviewImages)) {
            $processedImages = [];
            foreach ($incomingPreviewImages as $key => $imageData) {
                if (!empty($imageData)) {
                    try {
                        $processedImages[$key] = $this->persistDataUrl(
                            $imageData,
                            $key === 0 ? 'templates/review_front_png' : 'templates/review_back_png',
                            'png',
                            null,
                            'preview_images.' . $key
                        );
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
            if (!empty($processedImages)) {
                $review->preview_images = $processedImages;
            }
        }

        // Note: Server-side PNG generation from SVG is disabled since frontend now sends PNG previews
        // The following code block is kept for reference but should not execute
        $generatedPreviewImages = [];
        /*
        try {
            $sides = $designJson['sides'] ?? [];

            // Front side
            $frontSvg = null;
            if (!empty($sides['front']['svg']) && is_string($sides['front']['svg'])) {
                $frontSvg = $sides['front']['svg'];
            } elseif ($designSvg && trim($designSvg) !== '') {
                // legacy fallback: use single designSvg as front
                $frontSvg = $designSvg;
            }

            if ($frontSvg) {
                try {
                    // Decode if data URL
                    if (Str::startsWith(trim($frontSvg), 'data:')) {
                        $frontSvg = $this->decodeDataUrl($frontSvg);
                    }

                    $frontPngPath = $this->orderFlow->persistSvgAsPng($frontSvg, 'templates/review_front_png', $review->preview_images[0] ?? null, 'front_preview');
                    if ($frontPngPath) {
                        $generatedPreviewImages[0] = $frontPngPath;
                        $previewImage = $frontPngPath;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to persist front SVG as PNG', ['error' => $e->getMessage()]);
                }
            }

            // Back side
            if (!empty($sides['back']['svg']) && is_string($sides['back']['svg'])) {
                $backSvg = $sides['back']['svg'];
                try {
                    if (Str::startsWith(trim($backSvg), 'data:')) {
                        $backSvg = $this->decodeDataUrl($backSvg);
                    }
                    $backPngPath = $this->orderFlow->persistSvgAsPng($backSvg, 'templates/review_back_png', $review->preview_images[1] ?? null, 'back_preview');
                    if ($backPngPath) {
                        $generatedPreviewImages[1] = $backPngPath;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to persist back SVG as PNG', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to generate PNG previews from SVG design', ['error' => $e->getMessage()]);
        }
        */

        // If we successfully generated PNG previews, use them as the review previews
        if (!empty($generatedPreviewImages)) {
            // Ensure ordering: front at index 0, back at index 1
            ksort($generatedPreviewImages);
            $review->preview_images = $generatedPreviewImages;
            if (!empty($generatedPreviewImages[0])) {
                $review->preview_image = $generatedPreviewImages[0];
            }
        }

        $review->fill([
            'customer_id' => $customerId,
            'template_id' => (int) $validated['template_id'],
            'order_item_id' => $orderItemId,
            'design_svg' => $designSvg,
            'design_json' => $designJson,
            'preview_image' => $previewImage,
            'canvas_width' => $validated['canvas_width'] ?? null,
            'canvas_height' => $validated['canvas_height'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
        ]);

        \Illuminate\Support\Facades\Log::info('About to save CustomerReview', [
            'review_id' => $review->id,
            'customer_id' => $customerId,
            'template_id' => $validated['template_id'],
            'order_item_id' => $orderItemId,
            'design_svg_length' => is_string($designSvg) ? strlen($designSvg) : 0,
            'design_json_size' => is_array($designJson) ? count($designJson) : strlen(json_encode($designJson)),
        ]);

        try {
            $review->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save CustomerReview', [
                'error' => $e->getMessage(),
                'review_id' => $review->id,
                'customer_id' => $customerId,
                'template_id' => $validated['template_id'],
            ]);
            report($e);

            return response()->json([
                'message' => 'Unable to save your design. Please try again.',
            ], 500);
        }

        // Generate and save SVG files for front and back
        try {
            $template = Template::find($validated['template_id']);
            $sides = $designJson['sides'] ?? [];
            
            \Log::debug('SVG Save Debug', [
                'has_front_svg' => !empty($sides['front']['svg']),
                'has_back_svg' => !empty($sides['back']['svg']),
                'front_svg_length' => isset($sides['front']['svg']) ? strlen($sides['front']['svg']) : 0,
                'back_svg_length' => isset($sides['back']['svg']) ? strlen($sides['back']['svg']) : 0,
                'template_has_back' => $template && $template->back_svg_path ? true : false,
            ]);
            
            // Save front SVG - prioritize design_svg (latest changes), then sides, then template
            $frontSvg = null;
            if (!empty($designSvg)) {
                $frontSvg = $designSvg;
            } elseif (!empty($sides['front']['svg']) && is_string($sides['front']['svg'])) {
                $frontSvg = $sides['front']['svg'];
            } elseif ($template && $template->svg_path) {
                // Fallback to template's front SVG
                try {
                    $frontSvg = \Illuminate\Support\Facades\Storage::disk('public')->get($template->svg_path);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to load template front SVG', ['path' => $template->svg_path, 'error' => $e->getMessage()]);
                }
            }
            
            if ($frontSvg) {
                if (Str::startsWith($frontSvg, 'data:image/svg+xml')) {
                    $frontSvg = $this->decodeDataUrl($frontSvg);
                }
                $frontSvgPath = 'templates/review_front_SVG/template_' . Str::uuid() . '.svg';
                \Illuminate\Support\Facades\Storage::disk('public')->put($frontSvgPath, $frontSvg);
                $review->front_svg_path = $frontSvgPath;
            }
            
            // Save back SVG
            $backSvg = null;
            if (!empty($sides['back']['svg']) && is_string($sides['back']['svg'])) {
                $backSvg = $sides['back']['svg'];
            } elseif ($template && $template->back_svg_path) {
                // Fallback to template's back SVG
                try {
                    $backSvg = \Illuminate\Support\Facades\Storage::disk('public')->get($template->back_svg_path);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to load template back SVG', ['path' => $template->back_svg_path, 'error' => $e->getMessage()]);
                }
            }
            
            if ($backSvg) {
                if (Str::startsWith($backSvg, 'data:image/svg+xml')) {
                    $backSvg = $this->decodeDataUrl($backSvg);
                }
                $backSvgPath = 'templates/review_back_SVG/template_' . Str::uuid() . '.svg';
                \Illuminate\Support\Facades\Storage::disk('public')->put($backSvgPath, $backSvg);
                $review->back_svg_path = $backSvgPath;
            }
            
            // Save the updated paths
            $review->save();
            
            \Log::debug('SVG Paths Saved', [
                'review_id' => $review->id,
                'front_svg_path' => $review->front_svg_path,
                'back_svg_path' => $review->back_svg_path,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to save SVG files for review', [
                'error' => $e->getMessage(),
                'review_id' => $review->id,
            ]);
            // Don't fail the request if SVG saving fails
        }

        $previewUrl = $this->resolvePreviewAsset($previewImage);

        return response()->json([
            'message' => 'Design saved for review.',
            'review_id' => $review->id,
            'order_item_id' => $review->order_item_id,
            'preview_image' => $previewUrl,
        ]);
    }

    public function continueReview(Request $request): JsonResponse
    {
        $order = $this->currentOrder(false);
        $summary = $request->input('summary');

        if (is_string($summary)) {
            $decoded = json_decode($summary, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $summary = $decoded;
            }
        }

        if (!is_array($summary) || empty($summary)) {
            $summary = session(static::SESSION_SUMMARY_KEY);
        }

        if (!is_array($summary) || empty($summary)) {
            return response()->json([
                'message' => 'No order summary found to continue.',
            ], 422);
        }

        session()->put(static::SESSION_SUMMARY_KEY, $summary);

        $productId = $summary['productId'] ?? $summary['product_id'] ?? null;
        $product = $productId ? $this->orderFlow->resolveProduct(null, (int) $productId) : null;
        if (!$product && $order) {
            $product = optional($order->items->first())->product;
        }

        if (!$product) {
            return response()->json([
                'message' => 'Unable to locate the product for this review.',
            ], 422);
        }

        try {
            $record = $this->orderFlow->persistFinalizedSelection($order, $summary, $product);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to save your review progress. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Review saved.',
            'redirect' => route('order.finalstep'),
            'customer_order_item_id' => $record->id ?? null,
        ]);
    }

    public function saveAsTemplate(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('saveAsTemplate called', ['request_data' => $request->all()]);

        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'design' => 'required|array',
            'preview_image' => 'nullable|string',
            'preview_images' => 'nullable|array',
        ]);

        \Illuminate\Support\Facades\Log::info('saveAsTemplate validation passed', ['template_name' => $validated['template_name']]);

        $user = Auth::user();
        $summary = session(static::SESSION_SUMMARY_KEY) ?? [];
        $product = null;

        if (!empty($summary['productId'])) {
            $product = $this->orderFlow->resolveProduct(null, (int) $summary['productId']);
        }

        \Illuminate\Support\Facades\Log::info('saveAsTemplate product resolved', ['product_id' => $product?->id]);

        // Use CustomerTemplateCustom instead of Template for customer templates
        $template = new CustomerTemplateCustom();
        $template->name = $validated['template_name'];
        $template->design = $validated['design'];
        $template->status = 'draft'; // Customer templates are drafts
        // If the request comes from an unauthenticated session, allow saving
        // the template without a user association instead of throwing a fatal error.
        $template->user_id = $user?->id ?? null; // Associate with the customer who created it (if any)
        $template->customer_id = $user?->customer?->customer_id ?? null; // Associate with customer record (if any)

        if ($product) {
            $template->product_id = $product->id;
            $template->template_id = $product->template_id;
        }

        \Illuminate\Support\Facades\Log::info('saveAsTemplate template object created', ['template_name' => $template->name, 'user_id' => $template->user_id]);

        // Handle preview images
        if (!empty($validated['preview_image'])) {
            $template->preview_image = $this->persistDataUrl(
                $validated['preview_image'],
                'customer/designs/preview',
                'png',
                null,
                'preview_image'
            );
            \Illuminate\Support\Facades\Log::info('saveAsTemplate preview image saved', ['preview_image' => $template->preview_image]);
        }

        if (!empty($validated['preview_images']) && is_array($validated['preview_images'])) {
            // Handle multiple previews if needed
            $processedImages = [];
            foreach ($validated['preview_images'] as $key => $imageData) {
                if (!empty($imageData)) {
                    $processedImages[$key] = $this->persistDataUrl(
                        $imageData,
                        'customer/designs/preview',
                        'png',
                        null,
                        'preview_images.' . $key
                    );
                }
            }
            $template->preview_images = $processedImages;
        }

        // Set placeholders if available
        $placeholders = [];
        if (isset($validated['design']['placeholders']) && is_array($validated['design']['placeholders'])) {
            $placeholders = $validated['design']['placeholders'];
        }
        $template->placeholders = $placeholders;

        try {
            $saved = $template->save();
            \Illuminate\Support\Facades\Log::info('saveAsTemplate template saved', ['saved' => $saved, 'template_id' => $template->id]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('saveAsTemplate save failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to save template: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Template saved successfully!',
            'template_id' => $template->id,
            'template_name' => $template->name,
        ]);
    }

    public function review(): RedirectResponse|ViewContract
    {
        Log::debug('Review method called');
        
        $order = $this->currentOrder();

        // If there's no persisted order, allow rendering the review page from a
        // session-only summary created in the editor flow.
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if (!$summary || !is_array($summary)) {
                $summary = [];
            }

            $productId = $summary['productId'] ?? null;
            $product = $productId ? $this->orderFlow->resolveProduct(null, $productId) : null;

            $storedDraft = $product ? $this->orderFlow->loadDesignDraft($product, Auth::user()) : null;

            if (!$product && $storedDraft && !empty($storedDraft['product_id'])) {
                $product = $this->orderFlow->resolveProduct(null, (int) $storedDraft['product_id']);
                $summary['productId'] = $summary['productId'] ?? $storedDraft['product_id'];
            }

            // Always prioritize the latest stored draft data over session data for design and previews
            if ($storedDraft) {
                if (!empty($storedDraft['design'])) {
                    $summary['metadata']['design'] = $storedDraft['design'];
                }

                if (!empty($storedDraft['placeholders'])) {
                    $summary['placeholders'] = $storedDraft['placeholders'];
                }

                // Always use the stored draft's preview images as they are the most recent
                if (!empty($storedDraft['preview_images'])) {
                    $summary['preview_images'] = $this->resolvePreviewAssets($storedDraft['preview_images']);
                    $summary['previewImages'] = $summary['preview_images'];
                }

                if (!empty($storedDraft['preview_image'])) {
                    $summary['previewImage'] = $this->resolvePreviewAsset($storedDraft['preview_image']);
                    $summary['invitationImage'] = $this->resolvePreviewAsset($storedDraft['preview_image']);
                    if (empty($summary['preview_images'])) {
                        $summary['preview_images'] = [$summary['previewImage']];
                    }
                    $summary['previewImages'] = $summary['previewImages'] ?? $summary['preview_images'] ?? [];
                }

                if (!empty($storedDraft['status'])) {
                    $summary['orderStatus'] = $storedDraft['status'];
                }

                // Update session with the latest draft data
                session()->put(static::SESSION_SUMMARY_KEY, $summary);
            }

            $summary['metadata'] = is_array($summary['metadata'] ?? null) ? $summary['metadata'] : [];
            $summary['metadata']['design'] = $summary['metadata']['design'] ?? ($storedDraft['design'] ?? []);

            if (!$product) {
                return $this->redirectToCatalog();
            }

            $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
            $placeholderItems = collect($summary['placeholders'] ?? []);

            // Debug: Log user info before loading
            $user = Auth::user();
            Log::debug('Review page - User info', [
                'user_id' => $user?->id,
                'customer_id' => $user?->customer?->customer_id ?? 'NO CUSTOMER',
                'template_id' => $product->template_id ?? null,
            ]);

            $customerReview = $this->orderFlow->loadCustomerReview($product->template_id, Auth::user(), $summary['order_item_id'] ?? null);

            // Debug: Log what we're loading
            Log::debug('Review page loading', [
                'template_id' => $product->template_id ?? null,
                'customerReview_exists' => $customerReview ? 'YES' : 'NO',
                'customerReview_id' => $customerReview?->id,
                'design_svg_length' => $customerReview ? strlen($customerReview->design_svg ?? '') : 0,
            ]);

            // If the customer review contains multiple preview images use them (front/back)
            if ($customerReview && !empty($customerReview->preview_images) && is_array($customerReview->preview_images)) {
                $resolved = $this->resolvePreviewAssets($customerReview->preview_images);
                if (!empty($resolved)) {
                    $images['all'] = $resolved;
                    $images['front'] = $resolved[0] ?? null;
                    $images['back'] = $resolved[1] ?? null;
                    // Also promote to summary so view-level session fallback works
                    $summary['preview_images'] = $resolved;
                    $summary['previewImages'] = $resolved;
                }
            }

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

            $reviewSummary = $summary;
            $reviewSummary['design'] = $reviewSummary['metadata']['design']
                ?? $reviewSummary['design']
                ?? ($storedDraft['design'] ?? []);

            $normalizedPreviewImages = $this->resolvePreviewAssets(
                $reviewSummary['previewImages']
                    ?? $reviewSummary['preview_images']
                    ?? ($images['all'] ?? [])
            );

            if (!empty($normalizedPreviewImages)) {
                $reviewSummary['previewImages'] = $normalizedPreviewImages;
                $reviewSummary['preview_images'] = $normalizedPreviewImages;
                $reviewSummary['previewImage'] = $reviewSummary['previewImage'] ?? $normalizedPreviewImages[0] ?? null;
                $reviewSummary['preview_image'] = $reviewSummary['preview_image'] ?? $reviewSummary['previewImage'] ?? $normalizedPreviewImages[0] ?? null;
            }

            // Load SVGs from customerReview for display
            $frontSvg = null;
            $backSvg = null;
            if ($customerReview && !empty($customerReview->front_svg_path)) {
                try {
                    $frontSvgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($customerReview->front_svg_path);
                    if (!empty($frontSvgContent)) {
                        $frontSvg = 'data:image/svg+xml;base64,' . base64_encode($frontSvgContent);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to load front SVG from path', ['path' => $customerReview->front_svg_path, 'error' => $e->getMessage()]);
                }
            }
            if ($customerReview && !empty($customerReview->back_svg_path)) {
                try {
                    $backSvgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($customerReview->back_svg_path);
                    if (!empty($backSvgContent)) {
                        $backSvg = 'data:image/svg+xml;base64,' . base64_encode($backSvgContent);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to load back SVG from path', ['path' => $customerReview->back_svg_path, 'error' => $e->getMessage()]);
                }
            }
            
            \Log::debug('Review SVG Loading', [
                'customerReview_id' => $customerReview?->id,
                'front_svg_path' => $customerReview?->front_svg_path,
                'back_svg_path' => $customerReview?->back_svg_path,
                'frontSvg_loaded' => $frontSvg ? 'YES' : 'NO',
                'backSvg_loaded' => $backSvg ? 'YES' : 'NO',
            ]);

            // Override images with SVGs if available
            if ($frontSvg) {
                $images['front'] = $frontSvg;
            }
            if ($backSvg) {
                $images['back'] = $backSvg;
            }

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
                'orderSummary' => $reviewSummary,
                'customerReview' => $customerReview,
                'lastEditedAt' => $storedDraft['last_edited_at'] ?? null,
                'frontSvg' => $frontSvg,
                'backSvg' => $backSvg,
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

        // Always check for the latest stored draft and use it for design data and previews
        $storedDraft = $product ? $this->orderFlow->loadDesignDraft($product, Auth::user()) : null;
        if ($storedDraft) {
            $designMeta = $storedDraft['design'] ?? $designMeta;
            $placeholderItems = collect($storedDraft['placeholders'] ?? $placeholderItems);

            if (!empty($storedDraft['preview_images'])) {
                $resolvedDraftImages = $this->resolvePreviewAssets($storedDraft['preview_images']);
                if (!empty($resolvedDraftImages)) {
                    $images['all'] = $resolvedDraftImages;
                    $images['front'] = $resolvedDraftImages[0] ?? ($images['front'] ?? null);
                    if (!empty($resolvedDraftImages[1])) {
                        $images['back'] = $resolvedDraftImages[1];
                    }
                }
            }

            if (!empty($storedDraft['preview_image'])) {
                $resolvedPreview = $this->resolvePreviewAsset($storedDraft['preview_image']);
                if ($resolvedPreview) {
                    $images['front'] = $resolvedPreview;
                    if (empty($images['all'])) {
                        $images['all'] = [$resolvedPreview];
                    } else {
                        $images['all'][0] = $resolvedPreview;
                    }
                }
            }
        }

        $customerReview = $product ? $this->orderFlow->loadCustomerReview($product->template_id, Auth::user(), $item?->id) : null;

        $orderSummary = session(static::SESSION_SUMMARY_KEY);
        $orderSummary = is_array($orderSummary) ? $orderSummary : [];
        $orderSummary['design'] = $orderSummary['metadata']['design'] ?? $orderSummary['design'] ?? $designMeta ?? [];

        $normalizedPreviewImages = $this->resolvePreviewAssets(
            $orderSummary['previewImages']
                ?? $orderSummary['preview_images']
                ?? ($images['all'] ?? [])
        );

        if (!empty($normalizedPreviewImages)) {
            $orderSummary['previewImages'] = $normalizedPreviewImages;
            $orderSummary['preview_images'] = $normalizedPreviewImages;
            $orderSummary['previewImage'] = $orderSummary['previewImage'] ?? $normalizedPreviewImages[0] ?? null;
            $orderSummary['preview_image'] = $orderSummary['preview_image'] ?? $orderSummary['previewImage'] ?? $normalizedPreviewImages[0] ?? null;
        }

        // Load SVGs from customerReview for display
        $frontSvg = null;
        $backSvg = null;
        if ($customerReview && !empty($customerReview->front_svg_path)) {
            try {
                $frontSvgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($customerReview->front_svg_path);
                if (!empty($frontSvgContent)) {
                    $frontSvg = 'data:image/svg+xml;base64,' . base64_encode($frontSvgContent);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to load front SVG from path', ['path' => $customerReview->front_svg_path, 'error' => $e->getMessage()]);
            }
        }
        if ($customerReview && !empty($customerReview->back_svg_path)) {
            try {
                $backSvgContent = \Illuminate\Support\Facades\Storage::disk('public')->get($customerReview->back_svg_path);
                if (!empty($backSvgContent)) {
                    $backSvg = 'data:image/svg+xml;base64,' . base64_encode($backSvgContent);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to load back SVG from path', ['path' => $customerReview->back_svg_path, 'error' => $e->getMessage()]);
            }
        }

        // Override images with SVGs if available
        if ($frontSvg) {
            $images['front'] = $frontSvg;
        }
        if ($backSvg) {
            $images['back'] = $backSvg;
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
            'orderSummary' => $orderSummary,
            'customerReview' => $customerReview,
            'lastEditedAt' => $storedDraft['last_edited_at'] ?? null,
            'frontSvg' => $frontSvg,
            'backSvg' => $backSvg,
        ]);
    }

    public function addToCart(Request $request): mixed
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

        $applyFinalizedPreview = function (?CustomerFinalized $finalized, array &$summary, array &$images): void {
            if (!$finalized) {
                return;
            }

            $finalizedPreviews = array_values(array_filter($finalized->preview_images ?? []));
            $primaryPreview = $finalized->preview_image ?? ($finalizedPreviews[0] ?? null);

            if (!empty($finalizedPreviews)) {
                $images['all'] = $finalizedPreviews;
                $images['front'] = $finalizedPreviews[0] ?? ($images['front'] ?? null);
                if (!empty($finalizedPreviews[1])) {
                    $images['back'] = $finalizedPreviews[1];
                }
            } elseif ($primaryPreview) {
                $images['front'] = $primaryPreview;
                $images['all'] = [$primaryPreview];
            }

            if ($primaryPreview && empty($summary['previewImage'])) {
                $summary['previewImage'] = $primaryPreview;
            }

            if (!empty($finalizedPreviews) && empty($summary['previewImages'])) {
                $summary['previewImages'] = $finalizedPreviews;
            }

            if (!empty($finalized->design) && empty(data_get($summary, 'metadata.design'))) {
                $summary['metadata']['design'] = $finalized->design;
            }

            if (!empty($finalized->design) && empty($summary['design'])) {
                $summary['design'] = $finalized->design;
            }
        };

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
                ]);
                $product->setRelation('bulkOrders', collect());
            }

            $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
            $selectedQuantity = $summary['quantity'] ?? $request->input('quantity') ?? null;
            $selectedPaperStockId = $summary['paperStockId'] ?? null;
            $selectedAddonIds = $summary['addonIds'] ?? [];

            // Always check for the latest stored draft and use it for design data and previews
            $storedDraft = $product ? $this->orderFlow->loadDesignDraft($product, Auth::user()) : null;
            if ($storedDraft) {
                if (!empty($storedDraft['preview_images'])) {
                    $resolvedDraftImages = $this->resolvePreviewAssets($storedDraft['preview_images']);
                    if (!empty($resolvedDraftImages)) {
                        $images['all'] = $resolvedDraftImages;
                        $images['front'] = $resolvedDraftImages[0] ?? ($images['front'] ?? null);
                        if (!empty($resolvedDraftImages[1])) {
                            $images['back'] = $resolvedDraftImages[1];
                        }
                    }
                }

                if (!empty($storedDraft['preview_image'])) {
                    $resolvedPreview = $this->resolvePreviewAsset($storedDraft['preview_image']);
                    if ($resolvedPreview) {
                        $images['front'] = $resolvedPreview;
                        if (empty($images['all'])) {
                            $images['all'] = [$resolvedPreview];
                        } else {
                            $images['all'][0] = $resolvedPreview;
                        }
                    }
                }

                // Also update the summary with stored draft preview images for JavaScript access
                if (!empty($storedDraft['preview_images'])) {
                    $images['preview_images'] = $this->resolvePreviewAssets($storedDraft['preview_images']);
                }
                if (!empty($storedDraft['preview_image'])) {
                    $images['previewImage'] = $this->resolvePreviewAsset($storedDraft['preview_image']);
                }
            }

            $finalized = $this->orderFlow->findFinalizedSelection($order, $product);
            $applyFinalizedPreview($finalized, $summary, $images);
            session()->put(static::SESSION_SUMMARY_KEY, $summary);

            $quantityOptions = $this->orderFlow->buildQuantityOptions($product, $selectedQuantity);
            $paperStockOptions = $this->orderFlow->buildPaperStockOptions($product, $selectedPaperStockId);
            $addonGroups = $this->orderFlow->buildAddonGroups($product, $selectedAddonIds);

            // Bulk orders removed; default quantity bounds
            $bulkOrders = collect();
            $minQty = 20;
            $maxQty = null;

            $orderPlaceholder = (object) ['items' => collect()];

            $minPickupDate = Carbon::tomorrow();
            $maxPickupDate = Carbon::now()->addMonths(2);
            $resolvedPickupDate = $this->resolvePickupDate(null, $summary, $minPickupDate, $maxPickupDate);

            // Calculate totals for session-only order
            $totals = $this->orderFlow->calculateTotalsFromSummary($summary);
            $summary = array_merge($summary, $totals);

            // Load customer review for SVG display
            $customerReview = $this->orderFlow->loadCustomerReview($product->template_id, Auth::user(), $summary['order_item_id'] ?? null);

            return view('customer.orderflow.finalstep', [
                'customerReview' => $customerReview,
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
                'itemTotal' => $summary['totalAmount'] ?? 0,
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
            ]);
            $product->setRelation('bulkOrders', collect());
        }

        $images = $product ? $this->orderFlow->resolveProductImages($product) : $this->orderFlow->placeholderImages();
        $selectedQuantity = $item?->quantity;
        $selectedPaperStockId = $item?->paperStockSelection?->paper_stock_id;
        $selectedAddonIds = $item?->addons?->pluck('size_id')->filter()->values()->all();

        $summaryPayload = session(static::SESSION_SUMMARY_KEY) ?: [];

        // Always check for the latest stored draft and use it for design data and previews
        $storedDraft = $product ? $this->orderFlow->loadDesignDraft($product, Auth::user()) : null;
        if ($storedDraft) {
            if (!empty($storedDraft['preview_images'])) {
                $resolvedDraftImages = $this->resolvePreviewAssets($storedDraft['preview_images']);
                if (!empty($resolvedDraftImages)) {
                    $images['all'] = $resolvedDraftImages;
                    $images['front'] = $resolvedDraftImages[0] ?? ($images['front'] ?? null);
                    if (!empty($resolvedDraftImages[1])) {
                        $images['back'] = $resolvedDraftImages[1];
                    }
                    $summaryPayload['preview_images'] = $resolvedDraftImages;
                    $summaryPayload['previewImages'] = $resolvedDraftImages;
                }
            }

            if (!empty($storedDraft['preview_image'])) {
                $resolvedPreview = $this->resolvePreviewAsset($storedDraft['preview_image']);
                if ($resolvedPreview) {
                    $images['front'] = $resolvedPreview;
                    if (empty($images['all'])) {
                        $images['all'] = [$resolvedPreview];
                    } else {
                        $images['all'][0] = $resolvedPreview;
                    }
                    $summaryPayload['previewImage'] = $resolvedPreview;
                    if (empty($summaryPayload['preview_images'])) {
                        $summaryPayload['preview_images'] = [$resolvedPreview];
                    }
                    $summaryPayload['previewImages'] = $summaryPayload['previewImages'] ?? $summaryPayload['preview_images'] ?? [];
                }
            }
        }
        $finalized = $this->orderFlow->findFinalizedSelection($order, $product);
        $applyFinalizedPreview($finalized, $summaryPayload, $images);
        session()->put(static::SESSION_SUMMARY_KEY, $summaryPayload);

        $quantityOptions = $this->orderFlow->buildQuantityOptions($product, $selectedQuantity);
        $paperStockOptions = $this->orderFlow->buildPaperStockOptions($product, $selectedPaperStockId);
        $addonGroups = $this->orderFlow->buildAddonGroups($product, $selectedAddonIds);

        // Bulk orders removed; default quantity bounds
        $bulkOrders = collect();
        $minQty = 20;
        $maxQty = null;

        $minPickupDate = Carbon::tomorrow();
        $maxPickupDate = Carbon::now()->addMonths(2);
        $resolvedPickupDate = $this->resolvePickupDate($order, $summaryPayload, $minPickupDate, $maxPickupDate);

        // Calculate detailed totals breakdown for the view
        if ($summaryPayload && isset($summaryPayload['productId'])) {
            $totals = $this->orderFlow->calculateTotalsFromSummary($summaryPayload);
            $summaryPayload = array_merge($summaryPayload, $totals);
        }

        // Get quantity and total from orderSummary to match the summary page
        $productId = $product->id ?? null;
        $itemFromSummary = $productId ? collect($summaryPayload['items'] ?? [])->first(function ($i) use ($productId) {
            return ($i['product_id'] ?? $i['id'] ?? $i['productId']) == $productId;
        }) : null;
        if ($itemFromSummary) {
            $selectedQuantity = $itemFromSummary['quantity'] ?? $itemFromSummary['qty'] ?? $selectedQuantity;
            $itemTotal = $itemFromSummary['total'] ?? $itemFromSummary['totalAmount'] ?? $itemFromSummary['total_amount'] ?? $itemFromSummary['price'] ?? 0;
        } else {
            $itemTotal = $item?->total_amount ?? 0;
        }

        // Load customer review for SVG display
        $customerReview = $this->orderFlow->loadCustomerReview($product?->template_id, Auth::user(), $item?->id);

        return view('customer.orderflow.finalstep', [
            'customerReview' => $customerReview,
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
            'itemTotal' => $itemTotal,
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
                    'shipping_fee' => static::DEFAULT_SHIPPING_FEE,
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

        // Persist finalized snapshot for reporting/reference
        $latestSummary = session(static::SESSION_SUMMARY_KEY) ?? [];
        try {
            $this->orderFlow->persistFinalizedSelection($order, is_array($latestSummary) ? $latestSummary : [], $product ?? null);
        } catch (\Throwable $e) {
            report($e);
        }

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
            $adminRedirect = route('admin.ordersummary.show', ['order' => $order->id]);
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
                'envelopeCatalog' => $this->cachedEnvelopeOptions(),
            ]);
        }

        $this->updateSessionSummary($order);

        return view('customer.Envelope.Envelope', [
            'order' => $order,
            'orderSummary' => session(static::SESSION_SUMMARY_KEY),
            'envelopeCatalog' => $this->cachedEnvelopeOptions(),
        ]);
    }

    public function envelopeOptions(): JsonResponse
    {
        return response()->json($this->cachedEnvelopeOptions());
    }

    private function cachedEnvelopeOptions(): array
    {
        $cacheKey = 'envelope_options_api_v4'; // Updated cache key to force refresh
        $cacheDuration = 60; // 60 seconds to keep stock changes fresh

        $orderFlow = $this->orderFlow;

        return Cache::remember($cacheKey, $cacheDuration, function () use ($orderFlow) {
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

                    // If material inventory exists, surface it for front-end validation
                    $materialInventory = $envelope->material?->inventory;
                    $stockQty = $materialInventory?->quantity_available ?? $availableStock;

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
                        'material_type' => $envelope->material?->material_type,
                        'material_id' => $envelope->material_id,
                        'min_qty' => $minQty,
                        'max_qty' => $maxQuantity,
                        'available_stock' => $availableStock,
                        'stock_qty' => $stockQty,
                        'updated_at' => $envelope->updated_at?->toIso8601String(),
                    ];
                })
                // Hide out-of-stock materials
                ->filter(function (array $item) {
                    if (!array_key_exists('stock_qty', $item)) return true;
                    if ($item['stock_qty'] === null) return true;
                    return $item['stock_qty'] > 0;
                })
                ->values()
                ->toArray();
        });
    }

    public function giveawayOptions(): JsonResponse
    {
        $fallbackImage = asset('images/placeholder.png');

        $giveaways = Product::query()
            ->with([
                'template',
                'uploads',
                'images',
                'materials.material.inventory',
            ])
            ->whereRaw('LOWER(product_type) = ?', ['giveaway'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Product $product) use ($fallbackImage) {
                $product->setRelation('bulkOrders', collect());
                $payload = $this->formatGiveawayProduct($product, $fallbackImage);
                $material = $product->materials->first()?->material;
                $stockQty = $material?->inventory?->quantity_available ?? $material?->stock_qty;

                // Do not cap by bulk rules; use stock only if available
                $resolvedMax = $stockQty !== null ? (int) $stockQty : null;

                $payload['metadata'] = [
                    'id' => $payload['product_id'],
                    'name' => $payload['name'],
                    'price' => $payload['price'],
                    'image' => $payload['image'],
                    'min_qty' => $payload['min_qty'],
                    'max_qty' => $resolvedMax,
                    'step' => $payload['step'],
                    'material' => $material?->material_name,
                    'material_type' => $material?->material_type,
                    'stock_qty' => $stockQty,
                ];

                $payload['material'] = $material?->material_name;
                $payload['material_type'] = $material?->material_type;
                $payload['stock_qty'] = $stockQty;
                $payload['max_qty'] = $resolvedMax;

                return $payload;
            })
            ->filter(function (array $item) {
                // Keep all products visible, including out-of-stock ones for pre-order
                return true;
            })
            ->values();

        return response()->json($giveaways);
    }

    private function formatGiveawayProduct(Product $product, ?string $fallbackImage = null): array
    {
        $images = $this->orderFlow->resolveProductImages($product);
        $unitPrice = $this->orderFlow->unitPriceFor($product);
        $bulkTier = null;
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

        $defaultQty = max($this->orderFlow->defaultQuantityFor($product), 1);

        $tiers = [];

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
            'min_qty' => $defaultQty,
            'max_qty' => null,
            'step' => max(1, 5),
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

            // Support multiple envelope selections; keep legacy single "envelope" for backward compatibility
            $envelopes = $summary['envelopes'] ?? [];
            if (empty($envelopes) && !empty($summary['envelope'])) {
                $envelopes[] = $summary['envelope'];
            }

            // Replace existing entry by id or append
            $existingIndex = null;
            foreach ($envelopes as $idx => $envelopeRow) {
                if (($envelopeRow['id'] ?? null) === ($meta['id'] ?? null)) {
                    $existingIndex = $idx;
                    break;
                }
            }
            if ($existingIndex !== null) {
                $envelopes[$existingIndex] = $meta;
            } else {
                $envelopes[] = $meta;
            }

            $summary['envelopes'] = array_values($envelopes);
            $summary['envelope'] = $summary['envelopes'][0] ?? $meta; // legacy consumers
            $summary['hasEnvelope'] = !empty($summary['envelopes']);

            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['envelope'] = (float) collect($summary['envelopes'])->sum(fn ($row) => (float) ($row['total'] ?? 0));

            // update totals
            $summary['subtotalAmount'] = ($summary['subtotalAmount'] ?? 0);
            $summary['taxAmount'] = round(($summary['subtotalAmount']) * static::DEFAULT_TAX_RATE, 2);
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

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

    public function clearEnvelope(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $order = $this->currentOrder();
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY) ?? [];

            if ($productId) {
                $envelopes = $summary['envelopes'] ?? [];
                // migrate legacy single envelope into array if needed
                if (empty($envelopes) && !empty($summary['envelope'])) {
                    $envelopes[] = $summary['envelope'];
                }

                $envelopes = collect($envelopes)
                    ->filter(fn ($env) => ($env['id'] ?? null) != $productId && ($env['product_id'] ?? null) != $productId)
                    ->values()
                    ->all();

                if (empty($envelopes)) {
                    unset($summary['envelopes'], $summary['envelope']);
                } else {
                    $summary['envelopes'] = $envelopes;
                    $summary['envelope'] = $envelopes[0];
                }
            } else {
                unset($summary['envelope'], $summary['envelopes']);
            }

            $summary['extras'] = $summary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
            $summary['extras']['envelope'] = (float) collect($summary['envelopes'] ?? [])->sum(fn ($row) => (float) ($row['total'] ?? 0));
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
            $product = $productId ? Product::with(['template', 'uploads', 'images'])->find($productId) : null;
            if ($product) {
                $product->setRelation('bulkOrders', collect());
            }

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
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

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
            $summary['totalAmount'] = round(($summary['subtotalAmount'] + $summary['taxAmount'] + ($summary['extras']['envelope'] ?? 0) + ($summary['extras']['giveaway'] ?? 0)), 2);

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
        // sessionStorage (order_summary_payload) to supply the draft payload.

        if ($order) {
            $this->updateSessionSummary($order);

            // Update summary snapshot to reflect the latest totals
            $primaryItem = $this->orderFlow->primaryInvitationItem($order);
            if ($primaryItem instanceof OrderItem) {
                $order->update([
                    'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                ]);
            }
        }

        $summary = session(static::SESSION_SUMMARY_KEY) ?? null;

        // Always check for the latest stored draft and use it for design data and previews
        if ($summary && is_array($summary)) {
            $productId = $summary['productId'] ?? null;
            $product = $productId ? $this->orderFlow->resolveProduct(null, $productId) : null;
            $storedDraft = $product ? $this->orderFlow->loadDesignDraft($product, Auth::user()) : null;

            if ($storedDraft) {
                // Always prioritize the latest stored draft data over session data for design and previews
                if (!empty($storedDraft['design'])) {
                    $summary['metadata']['design'] = $storedDraft['design'];
                }

                if (!empty($storedDraft['placeholders'])) {
                    $summary['placeholders'] = $storedDraft['placeholders'];
                }

                // Always use the stored draft's preview images as they are the most recent
                if (!empty($storedDraft['preview_images'])) {
                    $summary['preview_images'] = $this->resolvePreviewAssets($storedDraft['preview_images']);
                    $summary['previewImages'] = $summary['preview_images'];
                }

                if (!empty($storedDraft['preview_image'])) {
                    $summary['previewImage'] = $this->resolvePreviewAsset($storedDraft['preview_image']);
                    $summary['invitationImage'] = $this->resolvePreviewAsset($storedDraft['preview_image']);
                    if (empty($summary['preview_images'])) {
                        $summary['preview_images'] = [$summary['previewImage']];
                    }
                    $summary['previewImages'] = $summary['previewImages'] ?? $summary['preview_images'] ?? [];
                }

                if (!empty($storedDraft['status'])) {
                    $summary['orderStatus'] = $storedDraft['status'];
                }

                // Update session with the latest draft data
                session()->put(static::SESSION_SUMMARY_KEY, $summary);
            }
        }

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
                    ]);
                    $product->setRelation('bulkOrders', collect());
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

            // Always calculate totals to ensure they are up to date
            $totals = $this->orderFlow->calculateTotalsFromSummary($summary);
            $summary = array_merge($summary, $totals);
            session()->put(static::SESSION_SUMMARY_KEY, $summary);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'order_id' => $order?->id ?? null,
                'order_number' => $order?->order_number ?? null,
                'data' => $summary,
                'updated_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        // Load customer review for SVG display
        $productId = $summary['productId'] ?? null;
        $product = $productId ? $this->orderFlow->resolveProduct(null, $productId) : null;
        $customerReview = $product ? $this->orderFlow->loadCustomerReview($product->template_id, Auth::user()) : null;

        return view('customer.orderflow.mycart', [
            'order' => $order ?? null,
            'orderSummary' => $summary,
            'customerReview' => $customerReview,
        ]);
    }

    public function summaryJson(): JsonResponse
    {
        $order = $this->currentOrder();
        if (!$order) {
            $summary = session(static::SESSION_SUMMARY_KEY);
            if ($summary) {
                return response()->json([
                    'order_id' => null,
                    'order_number' => null,
                    'data' => $summary,
                    'updated_at' => Carbon::now()->toIso8601String(),
                ]);
            }

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

        // Calculate and add totals to the summary
        $totals = $this->orderFlow->calculateTotalsFromSummary($base);
        $base = array_merge($base, $totals);
        session()->put(static::SESSION_SUMMARY_KEY, $base);

        $order = $this->currentOrder();
        if ($order) {
            DB::transaction(function () use (&$order, $base) {
                $order = $this->orderFlow->initializeOrderFromSummary($order, $base);
                $this->orderFlow->recalculateOrderTotals($order);
                $order->refresh();
                $primaryItem = $this->orderFlow->primaryInvitationItem($order);
                if ($primaryItem instanceof OrderItem) {
                    $order->update([
                        'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                    ]);
                }
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
     * Update quantities for invitation, envelope, and/or giveaway items.
     * This recalculates totals and syncs them to the database.
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invitationQty' => ['nullable', 'integer', 'min:10'],
            'envelopeQty' => ['nullable', 'integer', 'min:0'],
            'giveawayQty' => ['nullable', 'integer', 'min:0'],
            'envelopes' => ['nullable', 'array'],
            'envelopes.*.index' => ['required_with:envelopes', 'integer', 'min:0'],
            'envelopes.*.qty' => ['required_with:envelopes', 'integer', 'min:10'],
            'giveaways' => ['nullable', 'array'],
            'giveaways.*.index' => ['required_with:giveaways', 'integer', 'min:0'],
            'giveaways.*.qty' => ['required_with:giveaways', 'integer', 'min:10'],
        ]);

        $summary = session(static::SESSION_SUMMARY_KEY) ?? [];
        if (empty($summary)) {
            return response()->json(['error' => 'No order summary found.'], 400);
        }

        // Update invitation quantity
        if (isset($data['invitationQty'])) {
            $summary['quantity'] = max(10, (int) $data['invitationQty']);
        }

        // Update single envelope quantity (legacy support)
        if (isset($data['envelopeQty']) && isset($summary['envelope']) && is_array($summary['envelope'])) {
            $summary['envelope']['qty'] = max(10, (int) $data['envelopeQty']);
            $summary['envelope']['quantity'] = $summary['envelope']['qty'];
        }

        // Update multiple envelopes by index
        if (!empty($data['envelopes'])) {
            $envelopes = $summary['envelopes'] ?? [];
            if (empty($envelopes) && isset($summary['envelope'])) {
                $envelopes = [$summary['envelope']];
            }
            foreach ($data['envelopes'] as $update) {
                $idx = (int) $update['index'];
                if (isset($envelopes[$idx])) {
                    $envelopes[$idx]['qty'] = max(10, (int) $update['qty']);
                    $envelopes[$idx]['quantity'] = $envelopes[$idx]['qty'];
                    // Recalculate total for this envelope
                    $unitPrice = (float) ($envelopes[$idx]['unit_price'] ?? $envelopes[$idx]['price'] ?? 0);
                    $envelopes[$idx]['total'] = round($unitPrice * $envelopes[$idx]['qty'], 2);
                }
            }
            $summary['envelopes'] = $envelopes;
            // Also update legacy envelope field if single envelope
            if (count($envelopes) === 1) {
                $summary['envelope'] = $envelopes[0];
            }
        }

        // Update single giveaway quantity (legacy support)
        if (isset($data['giveawayQty']) && isset($summary['giveaway']) && is_array($summary['giveaway'])) {
            $summary['giveaway']['qty'] = max(10, (int) $data['giveawayQty']);
            $summary['giveaway']['quantity'] = $summary['giveaway']['qty'];
        }

        // Update multiple giveaways by index
        if (!empty($data['giveaways'])) {
            $giveaways = $summary['giveaways'] ?? [];
            if (empty($giveaways) && isset($summary['giveaway'])) {
                $giveaways = [$summary['giveaway']];
            }
            foreach ($data['giveaways'] as $update) {
                $idx = (int) $update['index'];
                if (isset($giveaways[$idx])) {
                    $giveaways[$idx]['qty'] = max(10, (int) $update['qty']);
                    $giveaways[$idx]['quantity'] = $giveaways[$idx]['qty'];
                    // Recalculate total for this giveaway
                    $unitPrice = (float) ($giveaways[$idx]['unit_price'] ?? $giveaways[$idx]['price'] ?? 0);
                    $giveaways[$idx]['total'] = round($unitPrice * $giveaways[$idx]['qty'], 2);
                }
            }
            $summary['giveaways'] = $giveaways;
            // Also update legacy giveaway field if single giveaway
            if (count($giveaways) === 1) {
                $summary['giveaway'] = $giveaways[0];
            }
        }

        // Recalculate totals
        $totals = $this->orderFlow->calculateTotalsFromSummary($summary);
        $summary = array_merge($summary, $totals);
        session()->put(static::SESSION_SUMMARY_KEY, $summary);

        // Update database order if exists
        $order = $this->currentOrder();
        if ($order) {
            DB::transaction(function () use (&$order, $summary) {
                // Update the primary invitation item quantity
                if (isset($summary['quantity'])) {
                    $primaryItem = $this->orderFlow->primaryInvitationItem($order);
                    if ($primaryItem) {
                        $unitPrice = (float) ($primaryItem->unit_price ?? 0);
                        $qty = (int) $summary['quantity'];
                        $primaryItem->update([
                            'quantity' => $qty,
                            'subtotal' => round($unitPrice * $qty, 2),
                        ]);
                    }
                }

                // Update envelope items
                $envelopeItems = $order->items()->where('line_type', OrderItem::LINE_TYPE_ENVELOPE)->get();
                $envelopes = $summary['envelopes'] ?? (isset($summary['envelope']) && !empty($summary['envelope']) ? [$summary['envelope']] : []);
                foreach ($envelopeItems as $idx => $item) {
                    if (isset($envelopes[$idx])) {
                        $qty = (int) ($envelopes[$idx]['qty'] ?? $envelopes[$idx]['quantity'] ?? $item->quantity);
                        $unitPrice = (float) $item->unit_price;
                        $item->update([
                            'quantity' => $qty,
                            'subtotal' => round($unitPrice * $qty, 2),
                        ]);
                    }
                }

                // Update giveaway items
                $giveawayItems = $order->items()->where('line_type', OrderItem::LINE_TYPE_GIVEAWAY)->get();
                $giveaways = $summary['giveaways'] ?? (isset($summary['giveaway']) && !empty($summary['giveaway']) ? [$summary['giveaway']] : []);
                foreach ($giveawayItems as $idx => $item) {
                    if (isset($giveaways[$idx])) {
                        $qty = (int) ($giveaways[$idx]['qty'] ?? $giveaways[$idx]['quantity'] ?? $item->quantity);
                        $unitPrice = (float) $item->unit_price;
                        $item->update([
                            'quantity' => $qty,
                            'subtotal' => round($unitPrice * $qty, 2),
                        ]);
                    }
                }

                // Refresh order items to get updated quantities before recalculating totals
                $order->load(['items.addons', 'items.paperStockSelection']);

                // Recalculate and update order totals
                $this->orderFlow->recalculateOrderTotals($order);
                $order->refresh();

                // Update summary snapshot
                $primaryItem = $this->orderFlow->primaryInvitationItem($order);
                if ($primaryItem instanceof OrderItem) {
                    $order->update([
                        'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                    ]);
                }

                $this->updateSessionSummary($order);
            });
        }

        // Get updated summary from session
        $updatedSummary = session(static::SESSION_SUMMARY_KEY);

        return response()->json([
            'message' => 'Quantities updated successfully.',
            'order_id' => $order?->id,
            'totalAmount' => $updatedSummary['totalAmount'] ?? 0,
            'subtotalAmount' => $updatedSummary['subtotalAmount'] ?? 0,
            'extras' => $updatedSummary['extras'] ?? [],
            'summary' => $updatedSummary,
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
            // Recalculate totals to ensure they are up to date
            $this->orderFlow->recalculateOrderTotals($order);
            $order->refresh();

            // Update summary snapshot to reflect recalculated totals
            $primaryItem = $this->orderFlow->primaryInvitationItem($order);
            if ($primaryItem instanceof OrderItem) {
                $order->update([
                    'summary_snapshot' => $this->orderFlow->buildSummarySnapshot($order, $primaryItem),
                ]);
            }
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
                    'shipping_fee' => static::DEFAULT_SHIPPING_FEE,
                    'total_amount' => $summary['totalAmount'] ?? 0,
                    'shipping_option' => 'standard',
                    'payment_method' => null,
                    'payment_status' => 'pending',
                    'summary_snapshot' => null,
                    'metadata' => $metadata,
                ]);

                $this->orderFlow->logActivity($order, 'order_created', ['order_number' => $order->order_number]);

                $orderJustCreated = true;

                $order = $this->orderFlow->initializeOrderFromSummary($order, $summary);

                if (!$this->orderFlow->checkInkStock($order)) {
                    throw new \Exception('Insufficient ink stock for this order.');
                }

                $this->orderFlow->logActivity($order, 'order_initialized', ['items_count' => $order->items()->count()]);

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

        $balanceDue = round(max(($order->grandTotalAmount() ?? 0) - $paidAmount, 0), 2);
        $defaultDeposit = round(max($order->grandTotalAmount() / 2, 0), 2);
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
            'amount' => $order->grandTotalAmount(),
            'status' => 'paid',
            'recorded_at' => now()->toIso8601String(),
        ];

        $order->update([
            'payment_status' => 'paid',
            'metadata' => $metadata,
        ]);

        // Deduct ink stock when payment is completed
        $this->orderFlow->deductInkStock($order);

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

        // Restore ink stock if order was previously paid
        if ($order->getOriginal('payment_status') === 'paid') {
            $this->orderFlow->restoreInkStock($order);
        }

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

        $initialCatalog = $this->cachedGiveawayOptions();

        return view('customer.orderflow.giveaways', [
            'order' => $order,
            'orderSummary' => $orderSummary,
            'initialCatalog' => $initialCatalog,
        ]);
    }

    private function cachedGiveawayOptions(): array
    {
        $cacheKey = 'giveaway_options_api_v3';
        $cacheDuration = 60; // keep stock fresh but avoid repeat queries per minute

        $fallbackImage = asset('images/placeholder.png');

        return Cache::remember($cacheKey, $cacheDuration, function () use ($fallbackImage) {
            return Product::query()
                ->with([
                    'template',
                    'uploads',
                    'images',
                    'materials.material.inventory',
                ])
                ->whereRaw('LOWER(product_type) = ?', ['giveaway'])
                ->orderByDesc('updated_at')
                ->limit(120)
                ->get()
                ->map(function (Product $product) use ($fallbackImage) {
                    $product->setRelation('bulkOrders', collect());
                    $payload = $this->formatGiveawayProduct($product, $fallbackImage);

                    $material = $product->materials->first()?->material;
                    $stockQty = $material?->inventory?->quantity_available ?? $material?->stock_qty;

                    // Stock-only cap (bulk limits ignored)
                    $resolvedMax = $stockQty !== null ? (int) $stockQty : null;

                    $payload['metadata'] = [
                        'id' => $payload['product_id'],
                        'name' => $payload['name'],
                        'price' => $payload['price'],
                        'image' => $payload['image'],
                        'min_qty' => $payload['min_qty'],
                        'max_qty' => $resolvedMax,
                        'step' => $payload['step'],
                        'material' => $material?->material_name,
                        'material_type' => $material?->material_type,
                        'stock_qty' => $stockQty,
                    ];

                    $payload['material'] = $material?->material_name;
                    $payload['material_type'] = $material?->material_type;
                    $payload['stock_qty'] = $stockQty;
                    $payload['max_qty'] = $resolvedMax;

                    return $payload;
                })
                ->filter(function (array $item) {
                    // Keep all products visible, including out-of-stock ones for pre-order
                    return true;
                })
                ->values()
                ->toArray();
        });
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
            'giveaways',
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
                'items.paperStockSelection.paperStock',
                'items.addons.productSize',
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
            $orderSummaryUrl = route('admin.ordersummary.show', ['order' => $order->id]);
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

    /**
     * Normalize preview asset paths to safe, browser-consumable URLs without double-prefixing.
     */
    private function resolvePreviewAsset(?string $value): ?string
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (Str::startsWith($trimmed, ['data:', 'http://', 'https://'])) {
            return $trimmed;
        }

        $normalized = ltrim(str_replace('\\', '/', $trimmed), '/');
        $normalized = preg_replace('#^storage/#i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('#^public/#i', '', $normalized) ?? $normalized;

        return Storage::url($normalized);
    }

    private function resolvePreviewAssets($values): array
    {
        // Accept JSON-encoded arrays or raw scalars and normalize to a flat array of strings
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = [$values];
            }
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        $flattened = [];
        foreach ($values as $value) {
            // Handle associative entries like ['url' => '...'] or objects with common keys
            if (is_array($value)) {
                $value = $value['url']
                    ?? $value['path']
                    ?? $value['preview']
                    ?? $value['image']
                    ?? null;
            } elseif (is_object($value)) {
                $value = $value->url
                    ?? $value->path
                    ?? $value->preview
                    ?? $value->image
                    ?? null;
            }

            if (is_string($value)) {
                $flattened[] = $this->resolvePreviewAsset($value);
            }
        }

        return array_values(array_filter($flattened));
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

    protected function persistDataUrl(string $dataUrl, string $directory, string $extension, ?string $existingPath, string $field): string
    {
        \Illuminate\Support\Facades\Log::info('persistDataUrl called', ['directory' => $directory, 'extension' => $extension, 'field' => $field, 'dataUrl_length' => strlen($dataUrl)]);
        if (trim((string) $dataUrl) === '') {
            \Illuminate\Support\Facades\Log::warning('persistDataUrl: empty dataUrl', ['field' => $field]);
            if ($existingPath) {
                return $existingPath;
            }
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Missing data payload.',
            ]);
        }

        try {
            $contents = $this->decodeDataUrl($dataUrl);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('persistDataUrl: decode failed', ['field' => $field, 'error' => $e->getMessage()]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Invalid data payload provided.',
            ]);
        }

        $normalizedExistingPath = null;
        if ($existingPath) {
            $normalizedExistingPath = ltrim(str_replace('\\', '/', (string) $existingPath), '/');
            $normalizedExistingPath = preg_replace('#^/?storage/#i', '', $normalizedExistingPath) ?? $normalizedExistingPath;
        }

        if ($normalizedExistingPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedExistingPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($normalizedExistingPath);
        }

        $directory = trim($directory, '/');
        if ($directory !== '') {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);
        }

        $filename = ($directory ? $directory . '/' : '') . 'template_' . \Illuminate\Support\Str::uuid() . '.' . $extension;

        $stored = \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $contents);

        if (!$stored) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Failed to persist exported asset on disk.',
            ]);
        }

        return $filename;
    }

    private function persistReviewPreview(?string $previewImage, ?string $existingPath = null): ?string
    {
        if (!$previewImage || trim($previewImage) === '') {
            return $existingPath;
        }

        if (Str::startsWith($previewImage, 'data:')) {
            $extension = str_contains(strtolower($previewImage), 'svg') ? 'svg' : 'png';
            return $this->persistDataUrl($previewImage, 'customer/reviews', $extension, $existingPath, 'preview_image');
        }

        return $previewImage;
    }

    private function normalizeDesignSvg(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (Str::startsWith($trimmed, 'data:image/svg+xml')) {
            try {
                return $this->decodeDataUrl($trimmed);
            } catch (\Throwable $e) {
                return $trimmed;
            }
        }

        return $trimmed;
    }

    private function extractPreviewFromDesign(array $design): ?string
    {
        $sides = Arr::get($design, 'sides', []);
        if (!is_array($sides)) {
            return null;
        }

        foreach ($sides as $side) {
            if (!is_array($side)) {
                continue;
            }
            $preview = $side['preview'] ?? null;
            if (is_string($preview) && trim($preview) !== '') {
                return $preview;
            }
        }

        return null;
    }

    private function stripHeavyDesignFields(array $design): array
    {
        if (!isset($design['sides']) || !is_array($design['sides'])) {
            return $design;
        }

        foreach ($design['sides'] as $key => $side) {
            if (!is_array($side)) {
                continue;
            }

            if (isset($side['svg'])) {
                unset($design['sides'][$key]['svg']);
            }

            if (isset($side['preview']) && is_string($side['preview']) && str_starts_with($side['preview'], 'data:')) {
                unset($design['sides'][$key]['preview']);
            }
        }

        return $design;
    }

    protected function decodeDataUrl(string $dataUrl): string
    {
        if (!\Illuminate\Support\Str::startsWith($dataUrl, 'data:')) {
            throw new \InvalidArgumentException('Invalid data URL header.');
        }

        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid data URL structure.');
        }

        [$meta, $payload] = $parts;

        if (str_contains($meta, ';base64')) {
            $decoded = base64_decode($payload, true);
        } else {
            $decoded = rawurldecode($payload);
        }

        if ($decoded === false || $decoded === null) {
            throw new \RuntimeException('Unable to decode payload.');
        }

        return $decoded;
    }
}
