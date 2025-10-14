<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductBulkOrder;
use App\Models\ProductColor;
use App\Models\ProductMaterial;
use App\Models\ProductPaperStock;
use App\Models\ProductEnvelope;
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
                'materials.material.inventory',
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
                'materials.material.inventory',
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

        // Consider explicit product->image (legacy field) as a valid candidate before falling back
        if (!$front && !empty($product->image)) {
            $front = $resolve($product->image);
        }

        if (!$back && !empty($product->image)) {
            $back = $resolve($product->image);
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

    public function primaryInvitationItem(Order $order): ?OrderItem
    {
        $order->loadMissing('items');

        return $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_INVITATION)
            ?? $order->items->first();
    }

    protected function upsertLineItem(Order $order, string $lineType, array $attributes): OrderItem
    {
        $payload = array_merge(['line_type' => $lineType], $attributes);

        $item = $order->items()->firstOrNew(['line_type' => $lineType]);
        $item->fill($payload);

        if (array_key_exists('design_metadata', $payload) && $payload['design_metadata'] === null) {
            $item->design_metadata = null;
        }

        $item->save();

        return $item->fresh([
            'addons',
            'paperStockSelection',
            'bulkSelections',
            'colors',
        ]);
    }

    protected function removeLineItem(Order $order, string $lineType): void
    {
        $item = $order->items()->where('line_type', $lineType)->first();
        if (!$item) {
            return;
        }

        $item->addons()->delete();
        $item->bulkSelections()->delete();
        $item->paperStockSelection()->delete();
        $item->colors()->delete();
        $item->delete();
    }

    protected function calculateItemTotal(?OrderItem $item): float
    {
        if (!$item) {
            return 0.0;
        }

        $baseSubtotal = $item->subtotal !== null
            ? (float) $item->subtotal
            : round(($item->unit_price ?? 0) * ($item->quantity ?? 0), 2);

        $paperPrice = (float) ($item->paperStockSelection?->price ?? 0);
        $addonTotal = (float) ($item->addons?->sum('addon_price') ?? 0);

        return round($baseSubtotal + $paperPrice + $addonTotal, 2);
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

    protected function syncPaperAndAddonsFromSummary(OrderItem $orderItem, array $summary): void
    {
        $previewSelections = Arr::get($summary, 'previewSelections', Arr::get($summary, 'metadata.final_step.preview_selections', []));

        $castMoney = static function ($value): ?float {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value)) {
                $numeric = preg_replace('/[^0-9.\-]/', '', $value);
                if ($numeric === '' || $numeric === null) {
                    return null;
                }

                return (float) $numeric;
            }

            return null;
        };

        $paperStockId = Arr::get($summary, 'paperStockId')
            ?? Arr::get($summary, 'metadata.final_step.paper_stock_id');
        $paperStockName = Arr::get($summary, 'paperStockName')
            ?? Arr::get($summary, 'metadata.final_step.paper_stock_name');
        $paperStockPrice = Arr::get($summary, 'paperStockPrice');
        if ($paperStockPrice === null) {
            $paperStockPrice = Arr::get($summary, 'metadata.final_step.paper_stock_price');
        }

        if (is_array($previewSelections)) {
            $previewPaper = Arr::get($previewSelections, 'paper_stock');
            if (is_array($previewPaper)) {
                if (!$paperStockId && isset($previewPaper['id']) && $previewPaper['id'] !== '') {
                    $paperStockId = is_numeric($previewPaper['id']) ? (int) $previewPaper['id'] : $previewPaper['id'];
                }

                if ((!$paperStockName || $paperStockName === '') && isset($previewPaper['name'])) {
                    $paperStockName = $previewPaper['name'];
                }

                if (($paperStockPrice === null || (is_numeric($paperStockPrice) && (float) $paperStockPrice <= 0)) && array_key_exists('price', $previewPaper)) {
                    $paperStockPrice = $castMoney($previewPaper['price']);
                }
            }
        }

        if ($paperStockId || $paperStockName || $paperStockPrice !== null) {
            $orderItem->paperStockSelection()->updateOrCreate([], [
                'paper_stock_id' => $paperStockId,
                'paper_stock_name' => $paperStockName,
                'price' => $paperStockPrice !== null ? (float) $paperStockPrice : null,
            ]);
        } else {
            $orderItem->paperStockSelection()->delete();
        }

        $addons = Arr::get($summary, 'addons', []);
        $addonIds = Arr::get($summary, 'addonIds', []);

        if (empty($addons)) {
            $finalStepAddons = Arr::get($summary, 'metadata.final_step.addons');
            if (is_array($finalStepAddons) && !empty($finalStepAddons)) {
                $addons = $finalStepAddons;
            }
        }

        if (empty($addons) && !empty($addonIds)) {
            $addons = ProductAddon::query()
                ->whereIn('id', collect($addonIds)->filter()->map(fn ($id) => (int) $id)->all())
                ->get()
                ->map(function (ProductAddon $addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->name ?? 'Add-on',
                        'price' => $addon->price ?? 0,
                        'type' => $addon->addon_type,
                    ];
                })
                ->values()
                ->all();
        }

        $normaliseAddon = static function ($addon) use ($castMoney) {
            if (!is_array($addon)) {
                return null;
            }

            $id = $addon['id'] ?? $addon['addon_id'] ?? null;
            if ($id !== null && is_numeric($id)) {
                $id = (int) $id;
            } else {
                $id = null;
            }

            $type = $addon['type'] ?? $addon['addon_type'] ?? $addon['group'] ?? $addon['category'] ?? null;
            $type = $type ? Str::snake((string) $type) : null;

            $name = $addon['name'] ?? $addon['label'] ?? $addon['value'] ?? null;
            if ($name === null) {
                return null;
            }

            $price = $addon['price'] ?? $addon['amount'] ?? $addon['total'] ?? null;
            $price = $castMoney($price) ?? 0.0;

            return [
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'price' => $price,
            ];
        };

        $previewAddonCandidates = [];
        if (is_array($previewSelections) && !empty($previewSelections)) {
            foreach ($previewSelections as $group => $payload) {
                if (!is_array($payload)) {
                    continue;
                }

                $normalizedGroup = Str::snake((string) $group);
                if (in_array($normalizedGroup, ['paper_stock', 'color', 'colors', 'foil', 'foil_color', 'ink_color', 'embossed_powder', 'metallic_powder'], true)) {
                    continue;
                }

                $candidate = [
                    'id' => $payload['id'] ?? null,
                    'type' => $payload['type'] ?? $normalizedGroup,
                    'name' => $payload['name'] ?? $payload['label'] ?? $payload['value'] ?? Str::headline(str_replace('_', ' ', (string) $group)),
                    'price' => $payload['price'] ?? $payload['amount'] ?? $payload['total'] ?? null,
                ];

                $normalisedCandidate = $normaliseAddon($candidate);
                if ($normalisedCandidate) {
                    $previewAddonCandidates[] = $normalisedCandidate;
                }
            }
        }

        $combinedAddons = [];
        $seenKeys = [];

        foreach ($addons as $addon) {
            $normalised = $normaliseAddon($addon);
            if (!$normalised) {
                continue;
            }

            $key = $normalised['id'] !== null
                ? 'id:' . $normalised['id']
                : 'name:' . Str::snake($normalised['type'] ?? 'addon') . ':' . Str::slug((string) $normalised['name']);

            if (isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;
            $combinedAddons[] = $normalised;
        }

        foreach ($previewAddonCandidates as $addon) {
            $key = $addon['id'] !== null
                ? 'id:' . $addon['id']
                : 'name:' . Str::snake($addon['type'] ?? 'addon') . ':' . Str::slug((string) $addon['name']);

            if (isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;
            $combinedAddons[] = $addon;
        }

        $addons = $combinedAddons;

        $orderItem->addons()->delete();

        foreach ($addons as $addon) {
            if (!is_array($addon)) {
                continue;
            }

            $orderItem->addons()->create([
                'addon_id' => $addon['id'] ?? null,
                'addon_type' => $addon['type'] ?? null,
                'addon_name' => $addon['name'] ?? 'Add-on',
                'addon_price' => isset($addon['price']) ? (float) $addon['price'] : 0,
            ]);
        }
    }

    protected function resolveBulkTierForQuantity(?Product $product, int $quantity): ?ProductBulkOrder
    {
        if (!$product) {
            return null;
        }

        $product->loadMissing(['bulkOrders']);

        $bulkOrders = $product->bulkOrders;
        if (!$bulkOrders || $bulkOrders->isEmpty()) {
            return null;
        }

        $bulkOrders = $bulkOrders->sortBy('min_qty')->values();

        $matchingTier = $bulkOrders->first(function (ProductBulkOrder $tier) use ($quantity) {
            $min = $tier->min_qty !== null ? (int) $tier->min_qty : null;
            $max = $tier->max_qty !== null ? (int) $tier->max_qty : null;

            $minMatches = $min === null || $quantity >= $min;
            $maxMatches = $max === null || $quantity <= $max;

            return $minMatches && $maxMatches;
        });

        return $matchingTier ?? $bulkOrders->last();
    }

    protected function syncBulkSelection(OrderItem $orderItem, ?Product $product, int $quantity, float $unitPrice): void
    {
        $orderItem->bulkSelections()->delete();

        if ($quantity <= 0) {
            return;
        }

        $bulkTier = $this->resolveBulkTierForQuantity($product, $quantity);

        $orderItem->bulkSelections()->create([
            'product_bulk_order_id' => $bulkTier?->id,
            'qty_selected' => $quantity,
            'price_per_unit' => $bulkTier?->price_per_unit ?? $unitPrice,
        ]);
    }

    protected function syncColorSelections(OrderItem $orderItem, array $previewSelections, ?Product $product = null): void
    {
        $orderItem->colors()->delete();

        if (!$previewSelections || !is_array($previewSelections)) {
            return;
        }

        if ($product) {
            $product->loadMissing(['colors']);
        }

        $recognizedGroups = [
            'color',
            'colors',
            'foil',
            'foil_color',
            'ink_color',
            'embossed_powder',
        ];

        $records = [];

        foreach ($previewSelections as $group => $payload) {
            $normalizedGroup = Str::snake((string) $group);
            if (!in_array($normalizedGroup, $recognizedGroups, true)) {
                continue;
            }

            if (!is_array($payload)) {
                continue;
            }

            $candidateId = $payload['id'] ?? null;
            $color = null;
            $colorId = null;

            if ($candidateId !== null && is_numeric($candidateId)) {
                $colorId = (int) $candidateId;
                $color = $product?->colors?->firstWhere('id', $colorId) ?? ProductColor::find($colorId);
            } elseif ($product?->colors) {
                $color = $product->colors->first(function ($model) use ($payload) {
                    return isset($payload['name'])
                        && isset($model->name)
                        && strcasecmp($model->name, (string) $payload['name']) === 0;
                });
                $colorId = $color?->id;
            }

            $colorName = $color?->name ?? ($payload['name'] ?? null);
            $colorCode = $color?->color_code ?? ($payload['color_code'] ?? null);
            $imagePath = $payload['image'] ?? null;

            if ($colorId === null && $colorName === null) {
                continue;
            }

            $key = ($colorId !== null ? $colorId : 'null') . ':' . strtolower((string) $colorName);
            $records[$key] = [
                'color_id' => $colorId,
                'color_name' => $colorName,
                'color_code' => $colorCode,
                'image_path' => $imagePath,
            ];
        }

        foreach ($records as $record) {
            $orderItem->colors()->create($record);
        }
    }

    protected function upsertEnvelopeOrderItem(Order $order, array $meta): OrderItem
    {
        $quantity = (int) ($meta['qty'] ?? $meta['quantity'] ?? 0);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $unitPrice = (float) ($meta['price'] ?? $meta['unit_price'] ?? 0);
        $total = $meta['total'] ?? $meta['subtotal'] ?? round($unitPrice * $quantity, 2);

        $designMeta = is_array($meta) ? $meta : [];

        $productId = $meta['product_id'] ?? $meta['id'] ?? null;
        $product = $productId ? Product::with(['bulkOrders', 'paperStocks', 'addons', 'colors'])->find($productId) : null;

        $orderItem = $this->upsertLineItem($order, OrderItem::LINE_TYPE_ENVELOPE, [
            'product_id' => $product?->id ?? $productId,
            'product_name' => $meta['name'] ?? $product?->name ?? 'Envelope',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round((float) $total, 2),
            'design_metadata' => array_filter($designMeta, function ($value) {
                if (is_array($value)) {
                    return !empty($value);
                }

                return $value !== null && $value !== '';
            }),
        ]);

        $this->syncEnvelopeAssociations($orderItem, $product, $meta);

        return $orderItem->fresh([
            'addons',
            'paperStockSelection',
            'bulkSelections',
            'colors',
        ]);
    }

    protected function upsertGiveawayOrderItem(Order $order, array $meta): OrderItem
    {
        $quantity = (int) ($meta['qty'] ?? $meta['quantity'] ?? 0);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $unitPrice = (float) ($meta['price'] ?? $meta['unit_price'] ?? 0);
        $total = $meta['total'] ?? $meta['subtotal'] ?? round($unitPrice * $quantity, 2);

        $designMeta = is_array($meta) ? $meta : [];

        return $this->upsertLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY, [
            'product_id' => $meta['product_id'] ?? $meta['id'] ?? null,
            'product_name' => $meta['name'] ?? 'Giveaway',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round((float) $total, 2),
            'design_metadata' => array_filter($designMeta, function ($value) {
                if (is_array($value)) {
                    return !empty($value);
                }

                return $value !== null && $value !== '';
            }),
        ]);
    }

    public function initializeOrderFromSummary(Order $order, array $summary): Order
    {
        $product = $this->resolveProduct(null, $summary['productId'] ?? null);
        $quantity = max(1, (int) ($summary['quantity'] ?? 1));
        $unitPrice = (float) ($summary['unitPrice'] ?? 0);

        $invitationItem = $this->upsertLineItem($order, OrderItem::LINE_TYPE_INVITATION, [
            'product_id' => $summary['productId'] ?? null,
            'product_name' => $summary['productName'] ?? 'Custom Invitation',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($unitPrice * $quantity, 2),
            'design_metadata' => Arr::get($summary, 'metadata.design'),
        ]);

        $this->syncPaperAndAddonsFromSummary($invitationItem, $summary);
        $this->syncBulkSelection($invitationItem, $product, $quantity, $unitPrice);

        $previewSelections = Arr::get($summary, 'previewSelections', Arr::get($summary, 'metadata.final_step.preview_selections', []));
        if (!is_array($previewSelections)) {
            $previewSelections = [];
        }
        $this->syncColorSelections($invitationItem, $previewSelections, $product);

        if (!empty($summary['envelope']) && is_array($summary['envelope'])) {
            $this->upsertEnvelopeOrderItem($order, $summary['envelope']);
        } else {
            $this->removeLineItem($order, OrderItem::LINE_TYPE_ENVELOPE);
        }

        if (!empty($summary['giveaway']) && is_array($summary['giveaway'])) {
            $this->upsertGiveawayOrderItem($order, $summary['giveaway']);
        } else {
            $this->removeLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY);
        }

        $initialized = $order->fresh([
            'items.addons',
            'items.paperStockSelection',
            'items.bulkSelections',
            'items.colors',
        ]);

        $this->syncMaterialUsage($initialized);

        return $initialized;
    }

    protected function syncEnvelopeAssociations(OrderItem $orderItem, ?Product $product, array $meta): void
    {
        $quantity = max(1, (int) ($meta['qty'] ?? $meta['quantity'] ?? $orderItem->quantity ?? 1));
        $unitPrice = (float) ($meta['price'] ?? $meta['unit_price'] ?? $orderItem->unit_price ?? 0);

        // Envelope selections do not populate ancillary tables (addons, paper stock, colors, bulk)
        $orderItem->bulkSelections()->delete();
        $orderItem->paperStockSelection()->delete();
        $orderItem->colors()->delete();
        $orderItem->addons()->delete();
    }

    public function buildInitialOrderMetadata(array $summary): array
    {
        $metadata = [
            'source' => 'design_editor',
            'initiated_at' => now()->toIso8601String(),
        ];

        $designMeta = Arr::get($summary, 'metadata.design');
        if ($designMeta) {
            $metadata['design'] = $designMeta;
        }

        $inkMeta = Arr::get($summary, 'metadata.ink');
        if ($inkMeta) {
            $metadata['ink'] = $inkMeta;
        }

        $finalStepMeta = Arr::get($summary, 'metadata.final_step');
        if ($finalStepMeta) {
            $metadata['final_step'] = $finalStepMeta;
        }

        if (!empty($summary['envelope']) && is_array($summary['envelope'])) {
            $metadata['envelope'] = $summary['envelope'];
        }

        if (!empty($summary['giveaway']) && is_array($summary['giveaway'])) {
            $metadata['giveaway'] = $summary['giveaway'];
        }

        return array_filter($metadata, function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }

            return $value !== null && $value !== '';
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
            'items.bulkSelections',
            'items.colors',
            'customerOrder',
        ]);

        $invitationItem = $this->primaryInvitationItem($order);
        $product = optional($invitationItem)->product;
        $images = $product ? $this->resolveProductImages($product) : $this->placeholderImages();

        $envelopeItem = $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_ENVELOPE);
        $giveawayItem = $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_GIVEAWAY);

        $summary = [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'orderStatus' => $order->status,
            'paymentStatus' => $order->payment_status,
            'productId' => $product?->id,
            'productName' => $invitationItem?->product_name ?? 'Custom invitation',
            'quantity' => $invitationItem?->quantity ?? 0,
            'quantityOptions' => $this->buildQuantityOptions($product, $invitationItem?->quantity),
            'quantityLabel' => $invitationItem && $invitationItem->quantity ? number_format($invitationItem->quantity) : null,
            'unitPrice' => $invitationItem?->unit_price ?? 0,
            'subtotalAmount' => $order->subtotal_amount,
            'taxAmount' => $order->tax_amount,
            'shippingFee' => $order->shipping_fee,
            'totalAmount' => $order->total_amount,
            'previewImages' => $images['all'],
            'previewImage' => $images['front'],
            'invitationImage' => $images['front'],
            'paperStockId' => $invitationItem?->paperStockSelection?->paper_stock_id,
            'paperStockName' => $invitationItem?->paperStockSelection?->paper_stock_name,
            'paperStockPrice' => $invitationItem?->paperStockSelection?->price,
            'addons' => $invitationItem?->addons?->map(function ($addon) {
                return [
                    'id' => $addon->addon_id,
                    'name' => $addon->addon_name,
                    'price' => $addon->addon_price,
                    'type' => $addon->addon_type,
                ];
            })->values()->all(),
            'addonIds' => $invitationItem?->addons?->pluck('addon_id')->filter()->values()->all(),
            'bulkSelection' => optional($invitationItem?->bulkSelections?->first(), function ($bulk) {
                return array_filter([
                    'product_bulk_order_id' => $bulk->product_bulk_order_id,
                    'qty_selected' => $bulk->qty_selected,
                    'price_per_unit' => $bulk->price_per_unit,
                ], fn ($value) => $value !== null && $value !== '');
            }),
            'colorSelections' => $invitationItem?->colors?->map(function ($color) {
                return array_filter([
                    'color_id' => $color->color_id,
                    'color_name' => $color->color_name,
                    'color_code' => $color->color_code,
                    'image_path' => $color->image_path,
                ], fn ($value) => $value !== null && $value !== '');
            })->values()->all(),
            'colorIds' => $invitationItem?->colors?->pluck('color_id')->filter()->values()->all(),
            'metadata' => $order->metadata,
        ];

        if ($invitationItem?->design_metadata) {
            $summary['placeholders'] = Arr::get($invitationItem->design_metadata, 'placeholders', []);
        }

        $envelopeTotal = $this->calculateItemTotal($envelopeItem);
        $giveawayTotal = $this->calculateItemTotal($giveawayItem);

        $summary['extras'] = [
            'paper' => $invitationItem?->paperStockSelection?->price ?? 0,
            'addons' => $invitationItem?->addons?->sum('addon_price') ?? 0,
            'envelope' => $envelopeItem ? $envelopeTotal : (float) Arr::get($order->metadata, 'envelope.total', Arr::get($order->metadata, 'envelope.price', 0)),
            'giveaway' => $giveawayItem ? $giveawayTotal : (float) Arr::get($order->metadata, 'giveaway.total', Arr::get($order->metadata, 'giveaway.price', 0)),
        ];

        // Provide detailed ink breakdown if present in metadata
        $inkMetaSummary = Arr::get($order->metadata, 'ink', []);
        $inkUsageSummary = (float) Arr::get($inkMetaSummary, 'usage_per_invite_ml', Arr::get($inkMetaSummary, 'usage_ml', 0));
        $inkUnitPriceSummary = (float) Arr::get($inkMetaSummary, 'unit_price', Arr::get($inkMetaSummary, 'price', 0));
        $inkTotalSummary = 0.0;
        if ($inkUnitPriceSummary > 0 && $inkUsageSummary > 0) {
            $inkTotalSummary = round($inkUsageSummary * $inkUnitPriceSummary * ($invitationItem?->quantity ?? 0), 2);
        } else {
            $inkTotalSummary = (float) Arr::get($inkMetaSummary, 'total', 0);
        }

        $summary['extras']['ink'] = [
            'usage_per_invite_ml' => $inkUsageSummary,
            'unit_price_per_ml' => $inkUnitPriceSummary,
            'total' => $inkTotalSummary,
        ];

        $envelopeMeta = Arr::get($order->metadata, 'envelope');
        if (!$envelopeMeta && $envelopeItem) {
            $envelopeMeta = array_merge(
                is_array($envelopeItem->design_metadata) ? $envelopeItem->design_metadata : [],
                [
                    'qty' => $envelopeItem->quantity,
                    'price' => (float) $envelopeItem->unit_price,
                    'total' => $envelopeTotal,
                ]
            );
        }

        $hasEnvelope = !empty($envelopeMeta);
        $summary['hasEnvelope'] = $hasEnvelope;
        if ($hasEnvelope) {
            $summary['envelope'] = $envelopeMeta;
        }

        $giveawayMeta = Arr::get($order->metadata, 'giveaway');
        if (!$giveawayMeta && $giveawayItem) {
            $giveawayMeta = array_merge(
                is_array($giveawayItem->design_metadata) ? $giveawayItem->design_metadata : [],
                [
                    'qty' => $giveawayItem->quantity,
                    'price' => (float) $giveawayItem->unit_price,
                    'total' => $giveawayTotal,
                ]
            );
        }

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
        $order->loadMissing([
            'items.addons',
            'items.paperStockSelection',
            'items.bulkSelections',
        ]);

        if ($order->items->isEmpty()) {
            return;
        }

        $meta = $order->metadata ?? [];
        $baseSubtotal = 0.0;

        $invitationItem = $this->primaryInvitationItem($order);

        foreach ($order->items as $lineItem) {
            $storedSubtotal = $lineItem->subtotal !== null
                ? (float) $lineItem->subtotal
                : round(($lineItem->unit_price ?? 0) * ($lineItem->quantity ?? 0), 2);

            if ($lineItem->subtotal === null || abs(((float) $lineItem->subtotal) - $storedSubtotal) > 0.009) {
                $lineItem->forceFill([
                    'subtotal' => $storedSubtotal,
                ])->save();
            }

            $baseSubtotal += $storedSubtotal;

            if ($lineItem->line_type === OrderItem::LINE_TYPE_ENVELOPE) {
                $meta['envelope'] = array_merge(
                    is_array($lineItem->design_metadata) ? $lineItem->design_metadata : [],
                    [
                        'qty' => $lineItem->quantity,
                        'price' => (float) $lineItem->unit_price,
                        'total' => $storedSubtotal,
                    ]
                );
            }

            if ($lineItem->line_type === OrderItem::LINE_TYPE_GIVEAWAY) {
                $meta['giveaway'] = array_merge(
                    is_array($lineItem->design_metadata) ? $lineItem->design_metadata : [],
                    [
                        'qty' => $lineItem->quantity,
                        'price' => (float) $lineItem->unit_price,
                        'total' => $storedSubtotal,
                    ]
                );
            }
        }

        if (!$order->items->firstWhere('line_type', OrderItem::LINE_TYPE_ENVELOPE)) {
            unset($meta['envelope']);
        }

        if (!$order->items->firstWhere('line_type', OrderItem::LINE_TYPE_GIVEAWAY)) {
            unset($meta['giveaway']);
        }

        $inkMeta = Arr::get($meta, 'ink', Arr::get($order->metadata ?? [], 'ink', []));
        $inkUsagePerInvite = (float) Arr::get($inkMeta, 'usage_per_invite_ml', Arr::get($inkMeta, 'usage_ml', 0));
        $inkUnitPrice = Arr::get($inkMeta, 'unit_price', Arr::get($inkMeta, 'price', null));
        $inkTotal = 0.0;
        if ($inkUnitPrice !== null && $inkUsagePerInvite > 0) {
            $inkTotal = round($inkUsagePerInvite * (float) $inkUnitPrice * ($invitationItem?->quantity ?? 0), 2);
        } else {
            $inkTotal = (float) Arr::get($inkMeta, 'total', (float) Arr::get($inkMeta, 'price', 0));
        }

        if (is_array($inkMeta)) {
            $inkMeta['total'] = $inkTotal;
            $meta['ink'] = $inkMeta;
        }

    $paperTotal = (float) ($invitationItem?->paperStockSelection?->price ?? 0);
    $addonsTotal = (float) ($invitationItem?->addons?->sum(fn($addon) => $addon->addon_price * $invitationItem->quantity) ?? 0);
    $extrasTotal = round($paperTotal + $addonsTotal + $inkTotal, 2);

    $subtotal = round($baseSubtotal + $extrasTotal, 2);
        $tax = 0.0;
        $shipping = $order->shipping_fee !== null ? (float) $order->shipping_fee : static::DEFAULT_SHIPPING_FEE;
    $total = round($subtotal + $shipping, 2);

        $order->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'shipping_fee' => $shipping,
            'total_amount' => $total,
            'metadata' => $meta,
        ]);
    }

    public function applyFinalSelections(Order $order, array $payload): Order
    {
        $order->loadMissing(['items.product', 'items.addons', 'items.paperStockSelection']);
        $item = $this->primaryInvitationItem($order);

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

        $this->syncBulkSelection($item, $product, $quantity, $unitPrice);
        $this->syncColorSelections($item, $previewSelections, $product);

        $item->load(['addons', 'paperStockSelection', 'bulkSelections', 'colors']);

        $meta = $order->metadata ?? [];
        $bulkSelection = $item->bulkSelections->first();
        $meta['final_step'] = [
            'quantity' => $item->quantity,
            'paper_stock_id' => $item->paperStockSelection?->paper_stock_id,
            'paper_stock_name' => $item->paperStockSelection?->paper_stock_name,
            'paper_stock_price' => $item->paperStockSelection?->price,
            'addon_ids' => $item->addons->pluck('addon_id')->filter()->values()->all(),
            'bulk' => $bulkSelection ? [
                'product_bulk_order_id' => $bulkSelection->product_bulk_order_id,
                'qty_selected' => $bulkSelection->qty_selected,
                'price_per_unit' => $bulkSelection->price_per_unit,
            ] : [
                'product_bulk_order_id' => null,
                'qty_selected' => $item->quantity,
                'price_per_unit' => $item->unit_price,
            ],
            'color_ids' => $item->colors->pluck('color_id')->filter()->values()->all(),
            'colors' => $item->colors->map(function ($color) {
                return array_filter([
                    'color_id' => $color->color_id,
                    'color_name' => $color->color_name,
                    'color_code' => $color->color_code,
                    'image_path' => $color->image_path,
                ], fn ($value) => $value !== null && $value !== '');
            })->values()->all(),
            'metadata' => $metadataPayload,
            'preview_selections' => $previewSelections,
            'updated_at' => now()->toIso8601String(),
        ];

        $order->update(['metadata' => $meta]);

        $order->refresh();
        $this->recalculateOrderTotals($order);

        $order->refresh();
        $this->syncMaterialUsage($order);

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

        $envelopeMeta = array_filter([
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

        $meta['envelope'] = $envelopeMeta;
        $order->update(['metadata' => $meta]);

        $this->upsertEnvelopeOrderItem($order, $envelopeMeta);

        $order->refresh();
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

        $giveawayMeta = array_filter([
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

        $meta['giveaway'] = $giveawayMeta;
        $order->update(['metadata' => $meta]);

        $this->upsertGiveawayOrderItem($order, $giveawayMeta);

        $order->refresh();
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

        $this->removeLineItem($order, OrderItem::LINE_TYPE_ENVELOPE);

        $order->refresh();

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

        $this->removeLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY);

        $order->refresh();

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

        $primaryItem = $this->primaryInvitationItem($order);
        if ($primaryItem instanceof OrderItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }

        return $summary;
    }

    /**
     * Sync inventory levels with the material usage implied by the current order configuration.
     * Applies differential adjustments so materials are only deducted once and restored on changes.
     */
    public function syncMaterialUsage(Order $order): void
    {
        $order->loadMissing([
            'items.product',
            'items.paperStockSelection',
            'items.addons',
        ]);

        $materialTotals = [];
        $materialCache = [];
        $paperStockCache = [];
        $envelopeCache = [];
        $addonCache = [];
        $materialNameCache = [];

        $resolveMaterialByName = function (?string $name) use (&$materialNameCache) {
            if (!$name) {
                return null;
            }

            $key = Str::lower(trim($name));
            if ($key === '') {
                return null;
            }

            if (!array_key_exists($key, $materialNameCache)) {
                $material = Material::query()
                    ->with('inventory')
                    ->whereRaw('LOWER(material_name) = ?', [$key])
                    ->first();

                $materialNameCache[$key] = $material ?: false;
            }

            $cached = $materialNameCache[$key];

            return $cached instanceof Material ? $cached : null;
        };

        $accumulateMaterial = function (OrderItem $item, ?Material $material, float $perUnitQty, array $meta = []) use (&$materialTotals, &$materialCache) {
            if (!$material || $perUnitQty <= 0) {
                return;
            }

            $materialId = $material->getKey();
            if (!$materialId) {
                return;
            }

            if (!isset($materialCache[$materialId])) {
                if (!$material->relationLoaded('inventory')) {
                    $material->loadMissing('inventory');
                }
                $materialCache[$materialId] = $material;
            } else {
                $material = $materialCache[$materialId];
            }

            $quantityMode = $meta['quantity_mode'] ?? 'per_item';
            unset($meta['quantity_mode']);

            $orderQty = max(0, (int) $item->quantity);
            $requiredQty = match ($quantityMode) {
                'per_order' => $perUnitQty,
                default => $perUnitQty * $orderQty,
            };
            if ($requiredQty <= 0) {
                return;
            }

            if (!isset($materialTotals[$materialId])) {
                $materialTotals[$materialId] = [
                    'material' => $material,
                    'per_unit_qty' => $perUnitQty,
                    'required' => 0.0,
                    'components' => [],
                ];
            }

            $materialTotals[$materialId]['required'] += $requiredQty;
            $materialTotals[$materialId]['per_unit_qty'] = max($materialTotals[$materialId]['per_unit_qty'], $perUnitQty);
            $materialTotals[$materialId]['components'][] = array_merge([
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'line_type' => $item->line_type,
                'order_quantity' => $orderQty,
                'required_qty' => $requiredQty,
                'per_unit_qty' => $perUnitQty,
                'quantity_mode' => $quantityMode,
            ], $meta);
        };

        foreach ($order->items as $item) {
            if ($item->product_id) {
                $productMaterials = ProductMaterial::query()
                    ->with(['material.inventory'])
                    ->where('product_id', $item->product_id)
                    ->get();

                foreach ($productMaterials as $productMaterial) {
                    $perUnitQty = (float) ($productMaterial->qty ?? 0);
                    if ($perUnitQty <= 0) {
                        continue;
                    }

                    $accumulateMaterial($item, $productMaterial->material, $perUnitQty, [
                        'product_material_id' => $productMaterial->id,
                        'source' => 'product_material',
                    ]);
                }
            }

            if ($item->line_type === OrderItem::LINE_TYPE_INVITATION) {
                $paperStock = null;
                $selection = $item->paperStockSelection;

                if ($selection && $selection->paper_stock_id) {
                    $paperStock = $paperStockCache[$selection->paper_stock_id] ??= ProductPaperStock::query()
                        ->with(['material.inventory'])
                        ->find($selection->paper_stock_id);
                }

                if (!$paperStock && $item->product_id) {
                    $fallbackKey = 'product_' . $item->product_id;
                    if (!isset($paperStockCache[$fallbackKey])) {
                        $paperStockCache[$fallbackKey] = ProductPaperStock::query()
                            ->with(['material.inventory'])
                            ->where('product_id', $item->product_id)
                            ->orderBy('id')
                            ->first();
                    }
                    $paperStock = $paperStockCache[$fallbackKey];
                }

                if ($paperStock && $paperStock->material) {
                    $accumulateMaterial($item, $paperStock->material, 1.0, [
                        'paper_stock_id' => $paperStock->id,
                        'source' => 'paper_stock',
                    ]);
                }
            }

            if ($item->line_type === OrderItem::LINE_TYPE_ENVELOPE && $item->product_id) {
                $envelope = $envelopeCache[$item->product_id] ??= ProductEnvelope::query()
                    ->with(['material.inventory'])
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($envelope && $envelope->material) {
                    $accumulateMaterial($item, $envelope->material, 1.0, [
                        'envelope_id' => $envelope->id,
                        'source' => 'envelope',
                    ]);
                }
            }

            if ($item->addons && $item->addons->isNotEmpty()) {
                foreach ($item->addons as $addonSelection) {
                    $addon = null;
                    if ($addonSelection->addon_id) {
                        $addon = $addonCache[$addonSelection->addon_id] ??= ProductAddon::query()->find($addonSelection->addon_id);
                    }

                    $addonName = $addon?->name ?? $addonSelection->addon_name ?? null;
                    $material = $resolveMaterialByName($addonName);

                    if (!$material) {
                        continue;
                    }

                    $accumulateMaterial($item, $material, 1.0, [
                        'addon_id' => $addonSelection->addon_id,
                        'addon_name' => $addonName,
                        'source' => 'addon',
                        'quantity_mode' => 'per_order',
                    ]);
                }
            }
        }

        $metadata = $order->metadata ?? [];
        $previousUsage = Arr::get($metadata, 'materials.usage', []);
        $now = now()->toIso8601String();

        if (empty($materialTotals)) {
            if (!empty($previousUsage)) {
                foreach ($previousUsage as $materialId => $usage) {
                    $previousTotal = (float) Arr::get($usage, 'total_used', 0);
                    if ($previousTotal <= 0) {
                        continue;
                    }

                    $material = Material::query()
                        ->with('inventory')
                        ->whereKey($materialId)
                        ->first();

                    if ($material) {
                        $this->adjustMaterialStock($material, -$previousTotal);
                    }
                }
            }

            if (isset($metadata['materials'])) {
                unset($metadata['materials']);
                $order->update(['metadata' => $metadata]);
            }

            return;
        }

        $updatedUsage = [];

        foreach ($materialTotals as $materialId => $aggregate) {
            /** @var Material $material */
            $material = $aggregate['material'];
            if (!$material->relationLoaded('inventory')) {
                $material->loadMissing('inventory');
            }

            $previousTotal = (float) Arr::get($previousUsage, $materialId . '.total_used', 0);
            $requiredTotal = (float) $aggregate['required'];
            $diff = $requiredTotal - $previousTotal;

            $adjustment = [
                'applied' => 0.0,
                'shortage' => (float) Arr::get($previousUsage, $materialId . '.pending_shortage', 0),
                'remaining_stock' => [
                    'inventory' => $material->inventory?->stock_level,
                    'material' => $material->stock_qty,
                ],
            ];

            if (abs($diff) > 0.00001) {
                $adjustment = $this->adjustMaterialStock($material, $diff);
            }

            $updatedUsage[$materialId] = [
                'material_id' => $material->getKey(),
                'material_name' => $material->material_name ?? null,
                'sku' => $material->sku ?? null,
                'qty_per_unit' => $aggregate['per_unit_qty'],
                'total_used' => $requiredTotal,
                'components' => $aggregate['components'],
                'pending_shortage' => $adjustment['shortage'] ?? 0,
                'remaining_stock' => $adjustment['remaining_stock'] ?? [
                    'inventory' => $material->inventory?->stock_level,
                    'material' => $material->stock_qty,
                ],
                'applied_delta' => $diff,
                'synced_at' => $now,
            ];
        }

        foreach ($previousUsage as $materialId => $usage) {
            if (isset($updatedUsage[$materialId])) {
                continue;
            }

            $previousTotal = (float) Arr::get($usage, 'total_used', 0);
            if ($previousTotal <= 0) {
                continue;
            }

            $material = Material::query()
                ->with('inventory')
                ->whereKey($materialId)
                ->first();

            if ($material) {
                $this->adjustMaterialStock($material, -$previousTotal);
            }
        }

        if (!empty($updatedUsage)) {
            $metadata['materials'] = [
                'usage' => $updatedUsage,
                'last_synced_at' => $now,
            ];
        } else {
            unset($metadata['materials']);
        }

        $order->update(['metadata' => $metadata]);
    }

    /**
     * Apply a stock delta to a material (and linked inventory row) while preventing negative levels.
     * Returns the applied delta, any shortage encountered, and the remaining stock snapshot.
     */
    protected function adjustMaterialStock(Material $material, float $delta): array
    {
        $materialRecord = Material::query()
            ->whereKey($material->getKey())
            ->lockForUpdate()
            ->with(['inventory' => function ($query) {
                $query->lockForUpdate();
            }])
            ->first();

        if ($materialRecord) {
            $material = $materialRecord;
        } else {
            $material->loadMissing('inventory');
        }

        $inventory = $material->inventory;
        $currentInventory = $inventory?->stock_level ?? 0;
        $currentMaterialStock = $material->stock_qty ?? $currentInventory;

        $newInventoryLevel = $inventory ? $currentInventory - $delta : null;
        $newMaterialStock = $currentMaterialStock - $delta;

        $shortage = 0.0;

        if ($newInventoryLevel !== null && $newInventoryLevel < 0) {
            $shortage = max($shortage, abs($newInventoryLevel));
            $newInventoryLevel = 0;
        }

        if ($newMaterialStock < 0) {
            $shortage = max($shortage, abs($newMaterialStock));
            $newMaterialStock = 0;
        }

        if ($inventory && $newInventoryLevel !== null) {
            $inventory->stock_level = (int) round($newInventoryLevel);
            $inventory->save();
        }

        $material->stock_qty = (int) round($newMaterialStock);
        $material->save();

        return [
            'applied' => $delta,
            'shortage' => $shortage,
            'remaining_stock' => [
                'inventory' => $inventory ? (int) round($newInventoryLevel) : null,
                'material' => (int) round($newMaterialStock),
            ],
        ];
    }
}
