<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductPaperStock;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderFlowService
{
    public const DEFAULT_TAX_RATE = 0.12;
    public const DEFAULT_SHIPPING_FEE = 250;

    public function resolveProduct(?Product $product, ?int $productId = null): ?Product
    {
        $productId = $product?->id ?? $productId;

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

    public function resolveProductImages(Product $product): array
    {
        $fallback = asset('images/placeholder.png');
        $front = null;
        $back = null;

        $resolve = function ($candidate) {
            if (!$candidate) {
                return null;
            }

            if (is_string($candidate)) {
                $candidate = trim($candidate);
            }

            if (preg_match('/^(https?:)?\/\//i', (string) $candidate) || str_starts_with((string) $candidate, '/')) {
                return (string) $candidate;
            }

            try {
                $normalized = str_replace('\\', '/', (string) $candidate);
                $normalized = ltrim($normalized, '/');

                if (Storage::disk('public')->exists($normalized)) {
                    return Storage::url($normalized);
                }

                if (Storage::exists($normalized)) {
                    return Storage::url($normalized);
                }

                return asset($normalized);
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

    public function placeholderImages(): array
    {
        $fallback = asset('images/placeholder.png');

        return [
            'front' => $fallback,
            'back' => $fallback,
            'all' => [$fallback],
        ];
    }

    public function defaultQuantityFor(?Product $product): int
    {
        if ($product && $product->bulkOrders->isNotEmpty()) {
            return (int) $product->bulkOrders->sortBy('min_qty')->first()->min_qty;
        }

        return 50;
    }

    public function unitPriceFor(?Product $product): float
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

    public function unitPriceForQuantity(?Product $product, int $quantity, ?float $fallback = null): float
    {
        if (!$product) {
            return $fallback ?? 120.0;
        }

        $bulkOrders = $product->bulkOrders;
        if ($bulkOrders && $bulkOrders->isNotEmpty()) {
            $matchingTier = $bulkOrders->first(function ($tier) use ($quantity) {
                $min = $tier->min_qty !== null ? (int) $tier->min_qty : null;
                $max = $tier->max_qty !== null ? (int) $tier->max_qty : null;

                $minMatches = $min === null || $quantity >= $min;
                $maxMatches = $max === null || $quantity <= $max;

                return $minMatches && $maxMatches;
            });

            if ($matchingTier && $matchingTier->price_per_unit) {
                return (float) $matchingTier->price_per_unit;
            }
        }

        if ($product->base_price !== null) {
            return (float) $product->base_price;
        }

        if ($product->unit_price !== null) {
            return (float) $product->unit_price;
        }

        return $fallback ?? 120.0;
    }

    public function createCustomerOrder(?Authenticatable $user = null): CustomerOrder
    {
        $user = $user ?? Auth::user();
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

    public function generateOrderNumber(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }

    public function buildDesignMetadata(Product $product): array
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

    public function attachOptionalSelections(OrderItem $orderItem, Product $product): void
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

    public function buildSummarySnapshot(Order $order, OrderItem $item): array
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

    public function buildQuantityOptions(?Product $product, ?int $selectedQuantity): array
    {
        $basePrice = $this->unitPriceFor($product);

        if (!$product || $product->bulkOrders->isEmpty()) {
            return collect(range(1, 20))->map(function ($step) use ($basePrice) {
                $qty = $step * 10;

                return [
                    'label' => number_format($qty),
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
                'label' => number_format($qty),
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
                'label' => number_format($selectedQuantity),
                'value' => $selectedQuantity,
                'price' => round($selectedQuantity * $unitPrice, 2),
            ]);

            $options = $options->sortBy('value')->values();
        }

        return $options->values()->all();
    }

    public function buildPaperStockOptions(?Product $product, $selectedId = null): array
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

    public function buildAddonGroups(?Product $product, array $selectedIds = []): array
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

    public function buildAddonOptions(?Product $product): array
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

    public function resolveMediaPath(?string $path, ?string $fallback = null): ?string
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

    public function buildSummary(Order $order): array
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
            'quantityLabel' => $item && $item->quantity ? number_format($item->quantity) : null,
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
                    'id' => $addon->addon_id,
                    'name' => $addon->addon_name,
                    'price' => $addon->addon_price,
                    'type' => $addon->addon_type,
                ];
            })->values()->all(),
            'addonIds' => $item?->addons?->pluck('addon_id')->filter()->values()->all(),
            'metadata' => $order->metadata,
        ];

        if ($item?->design_metadata) {
            $summary['placeholders'] = Arr::get($item->design_metadata, 'placeholders', []);
        }

        $summary['extras'] = [
            'paper' => $item?->paperStockSelection?->price ?? 0,
            'addons' => $item?->addons?->sum('addon_price') ?? 0,
            'envelope' => (float) Arr::get($order->metadata, 'envelope.total', Arr::get($order->metadata, 'envelope.price', 0)),
            'giveaway' => (float) Arr::get($order->metadata, 'giveaway.total', Arr::get($order->metadata, 'giveaway.price', 0)),
        ];

        $envelopeMeta = Arr::get($order->metadata, 'envelope');
        $hasEnvelope = !empty($envelopeMeta);
        $summary['hasEnvelope'] = $hasEnvelope;
        if ($hasEnvelope) {
            $summary['envelope'] = $envelopeMeta;
        }

        $giveawayMeta = Arr::get($order->metadata, 'giveaway');
        $hasGiveaway = !empty($giveawayMeta);
        $summary['hasGiveaway'] = $hasGiveaway;
        if ($hasGiveaway) {
            $rawGallery = Arr::get($giveawayMeta, 'images', []);
            $normalizedGallery = is_array($rawGallery)
                ? array_values(array_filter($rawGallery, fn ($src) => is_string($src) && trim($src) !== ''))
                : [];

            $primaryImage = Arr::get($giveawayMeta, 'image');
            $giveawayProductId = Arr::get($giveawayMeta, 'product_id') ?? Arr::get($giveawayMeta, 'id');
            $resolvedImages = null;

            if (!$normalizedGallery || !$primaryImage) {
                $giveawayProduct = $giveawayProductId
                    ? Product::with(['template', 'uploads', 'images'])->find($giveawayProductId)
                    : null;

                $resolvedImages = $giveawayProduct
                    ? $this->resolveProductImages($giveawayProduct)
                    : $this->placeholderImages();

                if (!$normalizedGallery) {
                    $normalizedGallery = array_values(array_filter(
                        $resolvedImages['all'] ?? [],
                        fn ($src) => is_string($src) && trim($src) !== ''
                    ));
                }

                if (!$primaryImage) {
                    $primaryImage = $normalizedGallery[0]
                        ?? ($resolvedImages['front'] ?? null)
                        ?? ($resolvedImages['all'][0] ?? null);
                }
            } else {
                $resolvedImages = $normalizedGallery ? ['front' => $normalizedGallery[0], 'all' => $normalizedGallery] : null;
            }

            if (!$normalizedGallery) {
                $placeholders = $resolvedImages ?? $this->placeholderImages();
                $normalizedGallery = array_values(array_filter($placeholders['all'] ?? [], fn ($src) => is_string($src) && trim($src) !== ''));
            }

            if (!$primaryImage) {
                $primaryImage = $normalizedGallery[0] ?? null;
            }

            $giveawayMeta['images'] = $normalizedGallery;
            $giveawayMeta['image'] = $primaryImage;

            $summary['giveaway'] = $giveawayMeta;
        }

        $payments = Arr::get($order->metadata, 'payments', []);
        if ($payments) {
            $summary['payments'] = $payments;
        }

        return $summary;
    }

    public function recalculateOrderTotals(Order $order): void
    {
        $order->loadMissing(['items.addons', 'items.paperStockSelection']);

        $item = $order->items->first();
        if (!$item) {
            return;
        }

        $baseSubtotal = round(($item->unit_price ?? 0) * ($item->quantity ?? 0), 2);
        $paperPrice = (float) ($item->paperStockSelection?->price ?? 0);
        $addonTotal = (float) $item->addons?->sum('addon_price') ?? 0;
        $envelopeTotal = (float) Arr::get($order->metadata, 'envelope.total', Arr::get($order->metadata, 'envelope.price', 0));
    $giveawayTotal = (float) Arr::get($order->metadata, 'giveaway.total', Arr::get($order->metadata, 'giveaway.price', 0));

    $subtotal = round($baseSubtotal + $paperPrice + $addonTotal + $envelopeTotal + $giveawayTotal, 2);
        $tax = round($subtotal * static::DEFAULT_TAX_RATE, 2);
        $shipping = static::DEFAULT_SHIPPING_FEE;
        $total = round($subtotal + $tax + $shipping, 2);

        $order->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'shipping_fee' => $shipping,
            'total_amount' => $total,
        ]);
    }

    public function applyFinalSelections(Order $order, array $payload): Order
    {
        $order->loadMissing(['items.product', 'items.addons', 'items.paperStockSelection']);
        $item = $order->items->first();

        if (!$item) {
            throw new \RuntimeException('Unable to apply final selections without an order item.');
        }

        $product = $item->product ?? $this->resolveProduct(null, $item->product_id);

        $quantity = max(1, (int) ($payload['quantity'] ?? $item->quantity ?? 1));
        $paperStockId = $payload['paper_stock_id'] ?? null;
        $paperStockPrice = $payload['paper_stock_price'] ?? null;
        $paperStockName = $payload['paper_stock_name'] ?? null;
        $addonIds = collect($payload['addons'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $metadataPayload = $payload['metadata'] ?? [];
        $previewSelections = $payload['preview_selections'] ?? [];

        $unitPrice = $this->unitPriceForQuantity($product, $quantity, $item->unit_price);
        $item->fill([
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($unitPrice * $quantity, 2),
        ])->save();

        if ($paperStockId) {
            $paperStock = ProductPaperStock::find($paperStockId);
            $item->paperStockSelection()->updateOrCreate([], [
                'paper_stock_id' => $paperStock?->id,
                'paper_stock_name' => $paperStock?->name ?? $paperStockName,
                'price' => $paperStock?->price ?? ($paperStockPrice !== null ? (float) $paperStockPrice : null),
            ]);
        } else {
            $item->paperStockSelection()->delete();
        }

        $item->addons()->whereNotIn('addon_id', $addonIds->all())->delete();

        if ($addonIds->isNotEmpty()) {
            $addons = ProductAddon::query()->whereIn('id', $addonIds)->get();
            foreach ($addons as $addon) {
                $item->addons()->updateOrCreate(
                    ['addon_id' => $addon->id],
                    [
                        'addon_type' => $addon->addon_type,
                        'addon_name' => $addon->name ?? 'Add-on',
                        'addon_price' => $addon->price ?? 0,
                    ]
                );
            }
        }

        $item->load(['addons', 'paperStockSelection']);

        $meta = $order->metadata ?? [];
        $meta['final_step'] = [
            'quantity' => $item->quantity,
            'paper_stock_id' => $item->paperStockSelection?->paper_stock_id,
            'paper_stock_name' => $item->paperStockSelection?->paper_stock_name,
            'paper_stock_price' => $item->paperStockSelection?->price,
            'addon_ids' => $item->addons->pluck('addon_id')->filter()->values()->all(),
            'metadata' => $metadataPayload,
            'preview_selections' => $previewSelections,
            'updated_at' => now()->toIso8601String(),
        ];

        $order->update(['metadata' => $meta]);

        $this->recalculateOrderTotals($order);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function applyEnvelopeSelection(Order $order, array $payload): Order
    {
        $meta = $order->metadata ?? [];

        $quantity = max(1, (int) ($payload['quantity'] ?? 0));
        $unitPrice = (float) ($payload['unit_price'] ?? 0);
        $total = $payload['total_price'] ?? null;
        if ($total === null) {
            $total = $quantity * $unitPrice;
        }

        $envelopeMeta = $payload['metadata'] ?? [];

        $minQuantity = (int) ($envelopeMeta['min_qty'] ?? Arr::get($payload, 'metadata.min_qty') ?? 10);
        if ($minQuantity < 1) {
            $minQuantity = 10;
        }
        if ($minQuantity % 10 !== 0) {
            $minQuantity = (int) (ceil($minQuantity / 10) * 10);
        }

        $maxQuantity = $envelopeMeta['max_qty'] ?? Arr::get($payload, 'metadata.max_qty');
        if ($maxQuantity !== null) {
            $maxQuantity = (int) $maxQuantity;
            if ($maxQuantity < $minQuantity) {
                $maxQuantity = $minQuantity;
            }
        }

        if ($maxQuantity !== null && $maxQuantity < $quantity) {
            $maxQuantity = $quantity;
        }

        $meta['envelope'] = array_filter([
            'id' => $payload['envelope_id'] ?? $envelopeMeta['id'] ?? null,
            'product_id' => $payload['product_id'] ?? null,
            'name' => $envelopeMeta['name'] ?? null,
            'price' => $unitPrice,
            'qty' => $quantity,
            'total' => (float) $total,
            'material' => $envelopeMeta['material'] ?? null,
            'image' => $envelopeMeta['image'] ?? null,
            'min_qty' => $minQuantity,
            'max_qty' => $maxQuantity,
            'updated_at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');

        $order->update(['metadata' => $meta]);

        $this->recalculateOrderTotals($order);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function applyGiveawaySelection(Order $order, array $payload): Order
    {
        $productId = (int) ($payload['product_id'] ?? 0);
        $product = Product::with(['template', 'uploads', 'images', 'bulkOrders'])->find($productId);

        if (!$product) {
            throw new \RuntimeException('Giveaway product not found.');
        }

        $meta = $order->metadata ?? [];

        $quantity = max(1, (int) ($payload['quantity'] ?? 0));
        $payloadUnitPrice = $payload['unit_price'] ?? null;
        $unitPrice = $payloadUnitPrice !== null
            ? (float) $payloadUnitPrice
            : $this->unitPriceFor($product);

        $providedTotal = $payload['total_price'] ?? null;
        $total = $providedTotal !== null ? (float) $providedTotal : round($unitPrice * $quantity, 2);

        $metadata = $payload['metadata'] ?? [];
        $resolvedImages = $this->resolveProductImages($product);
        $normalizedImages = array_values(array_filter(
            $metadata['images'] ?? $resolvedImages['all'] ?? [],
            fn ($src) => is_string($src) && trim($src) !== ''
        ));

        $meta['giveaway'] = array_filter([
            'id' => $metadata['id'] ?? $product->id,
            'product_id' => $product->id,
            'name' => $metadata['name'] ?? $product->name,
            'price' => $unitPrice,
            'qty' => $quantity,
            'total' => round($total, 2),
            'image' => $metadata['image'] ?? ($normalizedImages[0] ?? $resolvedImages['front'] ?? null),
            'images' => $normalizedImages,
            'description' => $metadata['description'] ?? ($product->description ? Str::limit(strip_tags($product->description), 220) : null),
            'max_qty' => $metadata['max_qty'] ?? null,
            'min_qty' => $metadata['min_qty'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ], function ($value, $key) {
            if ($key === 'images') {
                return is_array($value) && !empty($value);
            }

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        $order->update(['metadata' => $meta]);

        $this->recalculateOrderTotals($order);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function clearEnvelopeSelection(Order $order): Order
    {
        $meta = $order->metadata ?? [];
        unset($meta['envelope']);

        $order->update(['metadata' => $meta]);

        $this->recalculateOrderTotals($order);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function clearGiveawaySelection(Order $order): Order
    {
        $meta = $order->metadata ?? [];
        unset($meta['giveaway']);

        $order->update(['metadata' => $meta]);

        $this->recalculateOrderTotals($order);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function refreshSummary(Order $order): array
    {
        $summary = $this->buildSummary($order);

        $primaryItem = $order->items->first();
        if ($primaryItem instanceof OrderItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }

        return $summary;
    }
}
