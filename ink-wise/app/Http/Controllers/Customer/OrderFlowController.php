<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderFlowController extends Controller
{
    private const SESSION_ORDER_ID = 'current_order_id';
    private const SESSION_SUMMARY_KEY = 'order_summary_payload';
    private const DEFAULT_TAX_RATE = 0.12;
    private const DEFAULT_SHIPPING_FEE = 250;

    public function edit(Request $request, ?Product $product = null): ViewContract
    {
        $product = $this->resolveProduct($product, $request);
        $images = $product ? $this->resolveProductImages($product) : $this->placeholderImages();

        return view('customer.Invitations.editing', [
            'product' => $product,
            'frontImage' => $images['front'],
            'backImage' => $images['back'],
            'previewImages' => $images['all'],
            'defaultQuantity' => $product ? $this->defaultQuantityFor($product) : 50,
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

        $quantity = $data['quantity'] ?? $this->defaultQuantityFor($product);
        $unitPrice = $this->unitPriceFor($product);
        $subtotal = round($unitPrice * $quantity, 2);
        $taxAmount = round($subtotal * static::DEFAULT_TAX_RATE, 2);
        $shippingFee = static::DEFAULT_SHIPPING_FEE;
        $total = round($subtotal + $taxAmount + $shippingFee, 2);

        $order = null;

        DB::transaction(function () use (&$order, $product, $quantity, $unitPrice, $subtotal, $taxAmount, $shippingFee, $total) {
            $this->clearExistingOrder();

            $customerOrder = $this->createCustomerOrder();

            $order = $customerOrder->orders()->create([
                'customer_id' => $customerOrder->customer_id,
                'user_id' => optional(Auth::user())->user_id,
                'order_number' => $this->generateOrderNumber(),
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

            $designMetadata = $this->buildDesignMetadata($product);

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name ?? 'Custom Invitation',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'design_metadata' => $designMetadata,
            ]);

            $this->attachOptionalSelections($orderItem, $product);

            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $orderItem),
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
        $images = $product ? $this->resolveProductImages($product) : $this->placeholderImages();
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

    $images = $product ? $this->resolveProductImages($product) : $this->placeholderImages();
    $selectedQuantity = $item?->quantity;
    $selectedPaperStockId = $item?->paperStockSelection?->paper_stock_id;
    $selectedAddonIds = $item?->addons?->pluck('addon_id')->filter()->values()->all();

    $quantityOptions = $this->buildQuantityOptions($product, $selectedQuantity);
    $paperStockOptions = $this->buildPaperStockOptions($product, $selectedPaperStockId);
    $addonGroups = $this->buildAddonGroups($product, $selectedAddonIds);

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

    private function resolveProduct(?Product $product, Request $request): ?Product
    {
        $productId = $product?->id ?? $request->integer('product_id');

        if (!$productId) {
            $productId = Product::query()
                ->where('product_type', 'Invitation')
                ->orderByDesc('updated_at')
                ->value('id');
        }

        if (!$productId) {
            return null;
        }

        if (!$product || $product->id !== $productId) {
            $product = Product::with([
                'template',
                'uploads',
                'images',
                'paperStocks',
                'addons',
                'colors',
                'bulkOrders',
            ])->find($productId);
        } else {
            $product->loadMissing([
                'template',
                'uploads',
                'images',
                'paperStocks',
                'addons',
                'colors',
                'bulkOrders',
            ]);
        }

        return $product;
    }

    private function resolveProductImages(Product $product): array
    {
        $fallback = asset('images/placeholder.png');
        $front = null;
        $back = null;

        $resolve = function ($candidate) {
            if (!$candidate) {
                return null;
            }

            if (preg_match('/^(https?:)?\/\//i', $candidate) || str_starts_with($candidate, '/')) {
                return $candidate;
            }

            try {
                return Storage::url($candidate);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $images = $product->product_images ?? $product->images;
        $template = $product->template;
        $uploads = $product->uploads ?? collect();

        if ($images) {
            $front = $resolve($images->final_front ?? $images->front ?? $images->preview ?? null);
            $back = $resolve($images->final_back ?? $images->back ?? null);
        }

        if (!$front && $template) {
            $front = $resolve($template->preview_front ?? $template->front_image ?? $template->preview ?? $template->image ?? null);
        }

        if (!$back && $template) {
            $back = $resolve($template->preview_back ?? $template->back_image ?? null);
        }

        if (!$front && $uploads->isNotEmpty()) {
            $primary = $uploads->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
            if ($primary) {
                $front = asset('storage/uploads/products/' . $product->id . '/' . $primary->filename);
            }
        }

        if (!$back && $uploads->count() > 1) {
            $secondary = $uploads->skip(1)->firstWhere(fn ($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
            if ($secondary) {
                $back = asset('storage/uploads/products/' . $product->id . '/' . $secondary->filename);
            }
        }

        $front = $front ?? $fallback;
        $back = $back ?? $front;

        $collection = array_values(array_unique(array_filter([$front, $back])));

        return [
            'front' => $front,
            'back' => $back,
            'all' => $collection,
        ];
    }

    private function placeholderImages(): array
    {
        $fallback = asset('images/placeholder.png');

        return [
            'front' => $fallback,
            'back' => $fallback,
            'all' => [$fallback],
        ];
    }

    private function defaultQuantityFor(?Product $product): int
    {
        if ($product && $product->bulkOrders->isNotEmpty()) {
            return (int) $product->bulkOrders->sortBy('min_qty')->first()->min_qty;
        }

        return 50;
    }

    private function unitPriceFor(?Product $product): float
    {
        if ($product && $product->base_price) {
            return (float) $product->base_price;
        }

        $bulk = $product?->bulkOrders?->sortBy('min_qty')->first();
        if ($bulk && $bulk->price_per_unit) {
            return (float) $bulk->price_per_unit;
        }

        return 120.0;
    }

    private function createCustomerOrder(): CustomerOrder
    {
        $user = Auth::user();
        $customer = $user?->customer;

        $nameParts = array_filter([
            $customer?->first_name,
            $customer?->last_name,
        ]);

        $fallbackName = $nameParts ? implode(' ', $nameParts) : 'Sample Customer';

        return CustomerOrder::create([
            'customer_id' => $customer->customer_id ?? null,
            'user_id' => $user->user_id ?? null,
            'name' => $fallbackName,
            'email' => $user->email ?? 'sample.customer@example.com',
            'phone' => $customer?->contact_number ?? '09171234567',
            'company' => $customer?->company ?? 'InkWise Studio',
            'address' => '123 Sample Street, Barangay Urdaneta',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'postal_code' => '1200',
            'additional_instructions' => 'Auto-generated from the design editor flow.',
        ]);
    }

    private function generateOrderNumber(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }

    private function buildDesignMetadata(Product $product): array
    {
        $images = $this->resolveProductImages($product);

        return [
            'template' => [
                'id' => $product->template_id,
                'name' => optional($product->template)->name,
            ],
            'preview_images' => $images['all'],
            'primary_image' => $images['front'],
            'secondary_image' => $images['back'],
            'placeholders' => [
                'Front: BROOKLYN, NY',
                'Front: KENDRA AND ANDREW',
                'Front: 06.28.26',
            ],
        ];
    }

    private function attachOptionalSelections(OrderItem $orderItem, Product $product): void
    {
        $bulkTier = $product->bulkOrders->sortBy('min_qty')->first();
        if ($bulkTier) {
            $orderItem->bulkSelections()->create([
                'product_bulk_order_id' => $bulkTier->id,
                'qty_selected' => $bulkTier->min_qty,
                'price_per_unit' => $bulkTier->price_per_unit ?? $this->unitPriceFor($product),
            ]);
        }

        $paperStock = $product->paperStocks->first();
        if ($paperStock) {
            $orderItem->paperStockSelection()->create([
                'paper_stock_id' => $paperStock->id,
                'paper_stock_name' => $paperStock->name,
                'price' => $paperStock->price,
            ]);
        }

        $product->addons->take(2)->each(function ($addon) use ($orderItem) {
            $orderItem->addons()->create([
                'addon_id' => $addon->id,
                'addon_type' => $addon->addon_type,
                'addon_name' => $addon->name,
                'addon_price' => $addon->price,
            ]);
        });
    }

    private function buildSummarySnapshot(Order $order, OrderItem $item): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'product' => [
                'id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
            ],
            'pricing' => [
                'unit_price' => $item->unit_price,
                'subtotal' => $order->subtotal_amount,
                'tax' => $order->tax_amount,
                'shipping' => $order->shipping_fee,
                'total' => $order->total_amount,
            ],
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    private function buildQuantityOptions(?Product $product, ?int $selectedQuantity): array
    {
        $basePrice = $this->unitPriceFor($product);

        if (!$product || $product->bulkOrders->isEmpty()) {
            return collect(range(1, 20))->map(function ($step) use ($basePrice) {
                $qty = $step * 10;

                return [
                    'label' => $qty . ' Invitations',
                    'value' => $qty,
                    'price' => round($qty * $basePrice, 2),
                ];
            })->values()->all();
        }

        $bulkOrders = $product->bulkOrders->sortBy('min_qty')->values();

        $startQty = $bulkOrders
            ->pluck('min_qty')
            ->filter(fn ($qty) => $qty !== null && $qty > 0)
            ->min();

        $startQty = $startQty ? (int) ceil($startQty / 10) * 10 : 10;
        $startQty = max(10, $startQty);

        $maxQty = $bulkOrders
            ->map(function ($tier) {
                if ($tier->max_qty !== null && $tier->max_qty > 0) {
                    return (int) $tier->max_qty;
                }

                if ($tier->min_qty !== null && $tier->min_qty > 0) {
                    return (int) $tier->min_qty;
                }

                return null;
            })
            ->filter()
            ->max();

        if (!$maxQty || $maxQty < $startQty) {
            $maxQty = $startQty;
        }

        $maxQty = (int) ceil($maxQty / 10) * 10;

        $quantities = collect(range($startQty, $maxQty, 10));

        $options = $quantities->map(function ($qty) use ($bulkOrders, $basePrice) {
            $matchingTier = $bulkOrders->first(function ($tier) use ($qty) {
                $min = $tier->min_qty !== null ? (int) $tier->min_qty : null;
                $max = $tier->max_qty !== null ? (int) $tier->max_qty : null;

                $minMatches = $min === null || $qty >= $min;
                $maxMatches = $max === null || $qty <= $max;

                return $minMatches && $maxMatches;
            });

            $unitPrice = $matchingTier && $matchingTier->price_per_unit
                ? (float) $matchingTier->price_per_unit
                : $basePrice;

            return [
                'label' => $qty . ' Invitations',
                'value' => $qty,
                'price' => round($qty * $unitPrice, 2),
            ];
        });

        if ($selectedQuantity && !$options->contains(fn ($option) => $option['value'] === $selectedQuantity)) {
            $matchingTier = $bulkOrders->first(function ($tier) use ($selectedQuantity) {
                $min = $tier->min_qty !== null ? (int) $tier->min_qty : null;
                $max = $tier->max_qty !== null ? (int) $tier->max_qty : null;

                $minMatches = $min === null || $selectedQuantity >= $min;
                $maxMatches = $max === null || $selectedQuantity <= $max;

                return $minMatches && $maxMatches;
            });

            $unitPrice = $matchingTier && $matchingTier->price_per_unit
                ? (float) $matchingTier->price_per_unit
                : $basePrice;

            $options->push([
                'label' => $selectedQuantity . ' Invitations',
                'value' => $selectedQuantity,
                'price' => round($selectedQuantity * $unitPrice, 2),
            ]);

            $options = $options->sortBy('value')->values();
        }

        return $options->values()->all();
    }

    private function buildPaperStockOptions(?Product $product, $selectedId = null): array
    {
        if (!$product) {
            return [];
        }

        $fallbackImage = asset('images/placeholder.png');
        $selectedKey = $selectedId !== null ? (string) $selectedId : null;

        return $product->paperStocks
            ->sortBy(fn ($stock) => $stock->name ?? '')
            ->map(function ($stock) use ($fallbackImage, $selectedKey) {
                $stockId = (string) $stock->id;
                return [
                    'id' => $stockId,
                    'name' => $stock->name ?? 'Paper Stock',
                    'price' => $stock->price !== null ? (float) $stock->price : null,
                    'image' => $this->resolveMediaPath($stock->image_path, $fallbackImage),
                    'selected' => $selectedKey !== null && $selectedKey === $stockId,
                ];
            })
            ->values()
            ->all();
    }

    private function buildAddonGroups(?Product $product, array $selectedIds = []): array
    {
        if (!$product) {
            return [];
        }

        $fallbackImage = asset('images/placeholder.png');
        $selectedKeys = collect($selectedIds)->filter(fn ($id) => $id !== null && $id !== '')->map(fn ($id) => (string) $id)->values()->all();

        return $product->addons
            ->groupBy(fn ($addon) => $addon->addon_type ?: 'additional')
            ->map(function ($group, $type) use ($fallbackImage, $selectedKeys) {
                $normalisedType = $type ?: 'additional';
                $label = Str::of($normalisedType)->headline();

                if ($label->isEmpty()) {
                    $label = Str::of('Additional Options');
                }

                return [
                    'type' => $normalisedType,
                    'label' => (string) $label,
                    'items' => $group->sortBy('name')->map(function ($addon) use ($fallbackImage, $normalisedType, $selectedKeys) {
                        $addonId = (string) $addon->id;
                        return [
                            'id' => $addonId,
                            'name' => $addon->name ?? 'Add-on',
                            'price' => $addon->price !== null ? (float) $addon->price : null,
                            'image' => $this->resolveMediaPath($addon->image_path, $fallbackImage),
                            'type' => $addon->addon_type ?: $normalisedType,
                            'selected' => in_array($addonId, $selectedKeys, true),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveMediaPath(?string $path, ?string $fallback = null): ?string
    {
        if (!$path) {
            return $fallback;
        }

        if (preg_match('/^(https?:)?\/\//i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        try {
            return Storage::url($path);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function buildAddonOptions(?Product $product): array
    {
        if (!$product) {
            return [
                ['label' => 'Address Printing', 'value' => 'address_printing', 'price' => 18],
                ['label' => 'Envelopes', 'value' => 'envelopes', 'price' => 12],
                ['label' => 'Wax Seal Kit', 'value' => 'wax_seal', 'price' => 20],
            ];
        }

        return $product->addons->map(function ($addon) {
            return [
                'label' => $addon->name,
                'value' => Str::slug($addon->name, '_'),
                'price' => $addon->price ?? 0,
            ];
        })->values()->all();
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
        $order->loadMissing([
            'items.product.template',
            'items.product.images',
            'items.product.uploads',
            'items.paperStockSelection',
            'items.addons',
            'customerOrder',
        ]);

        $item = $order->items->first();
        $product = optional($item)->product;
        $images = $product ? $this->resolveProductImages($product) : $this->placeholderImages();

        $summary = [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'orderStatus' => $order->status,
            'paymentStatus' => $order->payment_status,
            'productId' => $product?->id,
            'productName' => $item?->product_name ?? 'Custom invitation',
            'quantity' => $item?->quantity ?? 0,
            'quantityOptions' => $this->buildQuantityOptions($product, $item?->quantity),
            'quantityLabel' => $item && $item->quantity ? sprintf('%s Invitations', number_format($item->quantity)) : null,
            'unitPrice' => $item?->unit_price ?? 0,
            'subtotalAmount' => $order->subtotal_amount,
            'taxAmount' => $order->tax_amount,
            'shippingFee' => $order->shipping_fee,
            'totalAmount' => $order->total_amount,
            'previewImages' => $images['all'],
            'previewImage' => $images['front'],
            'invitationImage' => $images['front'],
            'paperStockId' => $item?->paperStockSelection?->paper_stock_id,
            'paperStockName' => $item?->paperStockSelection?->paper_stock_name,
            'paperStockPrice' => $item?->paperStockSelection?->price,
            'addons' => $item?->addons?->map(function ($addon) {
                return [
                    'name' => $addon->addon_name,
                    'price' => $addon->addon_price,
                    'type' => $addon->addon_type,
                ];
            })->values()->all(),
            'addonIds' => $item?->addons?->pluck('addon_id')->filter()->values()->all(),
            'editUrl' => $product ? route('design.edit', ['product' => $product->id]) : route('design.edit'),
            'checkoutUrl' => route('customer.checkout'),
            'giveawaysUrl' => route('order.giveaways'),
            'envelopeUrl' => route('order.envelope'),
            'summaryUrl' => route('order.summary'),
            'metadata' => $order->metadata,
        ];

        if ($item?->design_metadata) {
            $summary['placeholders'] = Arr::get($item->design_metadata, 'placeholders', []);
        }

        $payments = Arr::get($order->metadata, 'payments', []);
        if ($payments) {
            $summary['payments'] = $payments;
        }

        session()->put(static::SESSION_SUMMARY_KEY, $summary);
    }

    private function redirectToCatalog(): RedirectResponse
    {
        return redirect()->route('templates.wedding.invitations')
            ->with('status', 'Start a new invitation to continue through the order flow.');
    }
}
