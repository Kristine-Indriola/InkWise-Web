<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\CustomerTemplateCustom;
use App\Models\CustomerReview;
use App\Models\CustomerFinalized;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductBulkOrder;
use App\Models\ProductColor;
use App\Models\ProductMaterial;
use App\Models\InkStockMovement;
use App\Models\ProductPaperStock;
use App\Models\ProductEnvelope;
use App\Models\Ink;
use App\Models\StockMovement;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderFlowService
{
    public const DEFAULT_TAX_RATE = 0.0;
    public const DEFAULT_SHIPPING_FEE = 0.0;

    private const CMYK_RATIOS = [
        'cyan' => 0.30,
        'magenta' => 0.30,
        'yellow' => 0.30,
        'black' => 0.10,
    ];

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
                'materials.material.inventory',
            ]);
        }

        if ($product) {
            $product->setRelation('bulkOrders', collect());
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
        return 50;
    }

    public function unitPriceFor(?Product $product): float
    {
        if ($product && $product->base_price) {
            return (float) $product->base_price;
        }

        return 120.0;
    }

    public function unitPriceForQuantity(?Product $product, int $quantity, ?float $fallback = null): float
    {
        if (!$product) {
            return $fallback ?? 120.0;
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

    public function loadDesignDraft(?Product $product, ?Authenticatable $user = null): ?array
    {
        if (!$product || !$product->template_id) {
            return null;
        }

        $user = $user ?? Auth::user();
        $userId = $user?->getAuthIdentifier();
        $customerId = $user?->customer?->customer_id;

        $query = CustomerTemplateCustom::query()
            ->where('template_id', $product->template_id)
            ->when($product->id, fn ($q) => $q->where('product_id', $product->id));

        if ($userId) {
            $query->where(function ($q) use ($userId, $customerId) {
                $q->where('user_id', $userId);
                if ($customerId) {
                    $q->orWhere('customer_id', $customerId);
                }
            });
        } elseif ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $draft = $query->latest('updated_at')->first();

        if (!$draft) {
            return null;
        }

        return [
            'design' => $draft->design ?? [],
            'placeholders' => $draft->placeholders ?? [],
            'preview_image' => $draft->preview_image,
            'preview_images' => $draft->preview_images ?? [],
            'status' => $draft->status,
            'is_locked' => (bool) ($draft->is_locked ?? false),
            'summary' => $draft->summary ?? null,
            'last_edited_at' => optional($draft->last_edited_at)->toIso8601String(),
            'order_id' => $draft->order_id,
            'order_item_id' => $draft->order_item_id,
            'product_id' => $draft->product_id,
            'template_id' => $draft->template_id,
        ];
    }

    public function loadCustomerReview(?int $templateId, ?Authenticatable $user = null, ?int $orderItemId = null): ?CustomerReview
    {
        $user = $user ?? Auth::user();
        $userId = $user?->getAuthIdentifier();
        $customerId = $user?->customer?->customer_id;
        
        \Log::debug('loadCustomerReview called', [
            'templateId' => $templateId,
            'userId' => $userId,
            'customerId' => $customerId,
            'orderItemId' => $orderItemId,
        ]);
        
        $query = CustomerReview::query();

        if ($orderItemId) {
            $query->where('order_item_id', $orderItemId);
        } elseif ($templateId) {
            $query->where('template_id', $templateId);
        } else {
            return null;
        }

        if ($templateId && $orderItemId) {
            $query->where('template_id', $templateId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($userId) {
            // fallback: user-based lookup if customer record absent
            $query->whereNull('customer_id');
        }

        $result = $query->latest('updated_at')->first();
        
        \Log::debug('loadCustomerReview result', [
            'found' => $result ? 'YES' : 'NO',
            'result_id' => $result?->id,
            'result_template_id' => $result?->template_id,
            'result_customer_id' => $result?->customer_id,
            'design_svg_length' => $result ? strlen($result->design_svg ?? '') : 0,
        ]);
        
        return $result;
    }

    public function primaryInvitationItem(Order $order): ?OrderItem
    {
        $order->loadMissing('items');

        return $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_INVITATION)
            ?? $order->items->first();
    }

    protected function upsertLineItem(Order $order, string $lineType, array $attributes, array $matchAttributes = []): OrderItem
    {
        $match = array_merge(['line_type' => $lineType], $matchAttributes);
        $payload = array_merge(['line_type' => $lineType], $attributes);

        $item = $order->items()->firstOrNew($match);
        $item->fill($payload);

        if (array_key_exists('design_metadata', $payload) && $payload['design_metadata'] === null) {
            $item->design_metadata = null;
        }

        $item->save();

        return $item->fresh([
            'addons',
            'paperStockSelection',
        ]);
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
            ?? Arr::get($summary, 'paper_stock_id')
            ?? Arr::get($summary, 'metadata.final_step.paper_stock_id');
        $paperStockName = Arr::get($summary, 'paperStockName')
            ?? Arr::get($summary, 'paper_stock_name')
            ?? Arr::get($summary, 'metadata.final_step.paper_stock_name');
        $paperStockPrice = Arr::get($summary, 'paperStockPrice');
        if ($paperStockPrice === null) {
            $paperStockPrice = Arr::get($summary, 'paper_stock_price');
        }
        if ($paperStockPrice === null) {
            $paperStockPrice = Arr::get($summary, 'metadata.final_step.paper_stock_price');
        }

        $summaryQuantity = Arr::get($summary, 'quantity');
        if (!is_numeric($summaryQuantity) || (int) $summaryQuantity < 1) {
            $summaryQuantity = $orderItem->quantity ?? 1;
        }
        $summaryQuantity = max(1, (int) $summaryQuantity);

        $finalStepAddonQuantities = Arr::get($summary, 'metadata.final_step.addon_quantities', []);
        if (!is_array($finalStepAddonQuantities)) {
            $finalStepAddonQuantities = [];
        }

        $sessionAddonQuantities = Arr::get($summary, 'addonQuantities', []);
        if (!is_array($sessionAddonQuantities)) {
            $sessionAddonQuantities = [];
        }

        $addonQuantityHints = [];

        foreach ([$finalStepAddonQuantities, $sessionAddonQuantities] as $source) {
            foreach ($source as $key => $value) {
                if (!is_numeric($value)) {
                    continue;
                }

                if (!is_numeric($key) && !(is_string($key) && is_numeric($key))) {
                    continue;
                }

                $addonQuantityHints[(int) $key] = max(1, (int) $value);
            }
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
            $addons = ProductSize::query()
                ->whereIn('id', collect($addonIds)->filter()->map(fn ($id) => (int) $id)->all())
                ->get()
                ->map(function (ProductSize $addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->size ?? 'Size',
                        'price' => $addon->price ?? 0,
                        'type' => $addon->size_type,
                    ];
                })
                ->values()
                ->all();
        }

        $normaliseAddon = static function ($addon) use ($castMoney, $summaryQuantity, $addonQuantityHints) {
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

            $rawQuantity = $addon['quantity'] ?? $addon['qty'] ?? $addon['count'] ?? null;
            if ($rawQuantity === null && isset($addon['pricing_metadata']) && is_array($addon['pricing_metadata'])) {
                $rawQuantity = $addon['pricing_metadata']['quantity'] ?? null;
            }
            if ($rawQuantity === null && isset($addon['metadata']) && is_array($addon['metadata'])) {
                $rawQuantity = $addon['metadata']['quantity'] ?? null;
            }

            $quantity = is_numeric($rawQuantity) ? (int) $rawQuantity : null;
            if (($quantity === null || $quantity < 1) && $id !== null && isset($addonQuantityHints[$id])) {
                $candidate = $addonQuantityHints[$id];
                if (is_numeric($candidate)) {
                    $quantity = (int) $candidate;
                }
            }
            if ($quantity === null || $quantity < 1) {
                $quantity = $summaryQuantity;
            }

            $pricingMetadata = [];
            if (isset($addon['pricing_metadata']) && is_array($addon['pricing_metadata'])) {
                $pricingMetadata = $addon['pricing_metadata'];
            } elseif (isset($addon['metadata']) && is_array($addon['metadata'])) {
                $pricingMetadata = $addon['metadata'];
            }

            $pricingMetadata['quantity'] = $quantity;

            $pricingMode = $addon['pricing_mode'] ?? $addon['mode'] ?? null;

            return [
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'pricing_metadata' => $pricingMetadata,
                'pricing_mode' => $pricingMode,
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

            $addonQuantity = isset($addon['quantity']) && is_numeric($addon['quantity'])
                ? max(1, (int) $addon['quantity'])
                : $summaryQuantity;

            $pricingMetadata = [];
            if (isset($addon['pricing_metadata']) && is_array($addon['pricing_metadata'])) {
                $pricingMetadata = $addon['pricing_metadata'];
            }
            $pricingMetadata['quantity'] = $addonQuantity;

            $pricingMode = $addon['pricing_mode'] ?? null;

            $orderItem->addons()->create([
                'size_id' => $addon['id'] ?? null,
                'size_type' => $addon['type'] ?? null,
                'size' => $addon['name'] ?? 'Add-on',
                'size_price' => isset($addon['price']) ? (float) $addon['price'] : 0,
                'quantity' => $addonQuantity,
                'pricing_mode' => $pricingMode,
                'pricing_metadata' => $pricingMetadata,
            ]);
        }
    }

    protected function resolveBulkTierForQuantity(?Product $product, int $quantity): ?ProductBulkOrder
    {
        // Bulk tiers removed
        return null;
    }

    protected function syncBulkSelection(OrderItem $orderItem, ?Product $product, int $quantity, float $unitPrice): void
    {
        // Bulk selections removed; no-op
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

            $averageUsage = $color?->average_usage_ml ?? ($payload['average_usage_ml'] ?? null);

            if ($averageUsage === null) {
                continue;
            }

            $totalInk = $averageUsage * max(1, (int) $orderItem->quantity);

            $orderItem->inkUsage()->create([
                'color_id' => $color->id,
                'average_usage_ml' => $averageUsage,
                'total_ink_ml' => $totalInk,
            ]);
        }

        // Remove the old code
        // $key = 'usage:' . (string) $averageUsage;
        // $records[$key] = [
        //     'average_usage_ml' => $averageUsage,
        //     'total_ink_ml' => $totalInk,
        // ];
        // }

        // foreach ($records as $record) {
        //     $orderItem->colors()->create($record);
        // }
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
        $product = $productId ? Product::with(['paperStocks', 'addons', 'colors'])->find($productId) : null;
        if ($product) {
            $product->setRelation('bulkOrders', collect());
        }

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

        $productId = $meta['product_id'] ?? $meta['id'] ?? null;

        return $this->upsertLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY, [
            'product_id' => $productId,
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
        ], [
            'product_id' => $productId,
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

        $previewSelections = Arr::get($summary, 'previewSelections', Arr::get($summary, 'metadata.final_step.preview_selections', []));
        if (!is_array($previewSelections)) {
            $previewSelections = [];
        }
        $this->syncColorSelections($invitationItem, $previewSelections, $product);

        if (!empty($summary['envelope']) && is_array($summary['envelope'])) {
            $this->upsertEnvelopeOrderItem($order, $summary['envelope']);
            $this->persistEnvelopeRecord($order, $summary['envelope']);
        } else {
            $this->removeLineItem($order, OrderItem::LINE_TYPE_ENVELOPE);
            $this->deleteCustomerOrderItems($order, 'envelope');
        }

        // Handle multiple giveaways
        $giveaways = $summary['giveaways'] ?? [];
        if (empty($giveaways) && !empty($summary['giveaway'])) {
            $giveaways = [$summary['giveaway']];
        }

        if (!empty($giveaways) && is_array($giveaways)) {
            $selectedProductIds = [];
            foreach ($giveaways as $giveawayMeta) {
                if (empty($giveawayMeta) || !is_array($giveawayMeta)) continue;
                $item = $this->upsertGiveawayOrderItem($order, $giveawayMeta);
                if ($item->product_id) {
                    $selectedProductIds[] = $item->product_id;
                }
                $this->persistGiveawayRecord($order, $giveawayMeta);
            }
            
            // Remove any giveaways that are no longer in the summary
            $order->items()
                ->where('line_type', OrderItem::LINE_TYPE_GIVEAWAY)
                ->whereNotIn('product_id', $selectedProductIds)
                ->delete();

            // Remove stale giveaway snapshots from customer_order_items
            if (!empty($selectedProductIds)) {
                CustomerFinalized::query()
                    ->where('order_id', $order->id)
                    ->where('product_type', 'giveaway')
                    ->whereNotIn('product_id', $selectedProductIds)
                    ->delete();
            }
        } else {
            $this->removeLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY);
            $this->deleteCustomerOrderItems($order, 'giveaway');
        }

        $initialized = $order->fresh([
            'items.addons',
            'items.paperStockSelection',
            'items.colors',
        ]);

        $this->syncMaterialUsage($initialized);

        $pickupCandidate = Arr::get($summary, 'estimated_date')
            ?? Arr::get($summary, 'dateNeeded')
            ?? Arr::get($summary, 'metadata.final_step.estimated_date');

        if ($pickupCandidate && !$order->date_needed) {
            try {
                $pickupDate = Carbon::parse($pickupCandidate)->format('Y-m-d');
                $order->update(['date_needed' => $pickupDate]);
                $order->refresh();
            } catch (\Throwable $e) {
                // ignore parse failures
            }
        }

        return $initialized;
    }

    public function applyDesignAutosave(Order $order, array $payload): Order
    {
        $designMeta = Arr::get($payload, 'design', []);
        $placeholders = Arr::get($payload, 'placeholders', []);
        $previewImage = Arr::get($payload, 'preview_image');
        $previewImages = Arr::get($payload, 'preview_images', []);
        $updatedAt = Arr::get($designMeta, 'updated_at') ?? now()->toIso8601String();

        $firstSideKey = array_key_first($designMeta['sides'] ?? []) ?? 'front';
        $firstSide = $designMeta['sides'][$firstSideKey] ?? [];
        $designSvg = is_string($firstSide['svg'] ?? null) ? $firstSide['svg'] : null;
        $designJsonEncoded = $designMeta ? json_encode($designMeta) : null;
        $canvasWidth = Arr::get($designMeta, 'canvas.width');
        $canvasHeight = Arr::get($designMeta, 'canvas.height');
        $backgroundColor = Arr::get($designMeta, 'canvas.background')
            ?? Arr::get($designMeta, 'canvas.background_color')
            ?? Arr::get($designMeta, 'background_color');

        $metadata = $order->metadata;
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($metadata)) {
            $metadata = [];
        }

        $metadata['design'] = $designMeta;

        if ($previewImage || !empty($previewImages)) {
            $metadata['design_preview'] = array_filter([
                'image' => $previewImage,
                'images' => $previewImages,
                'updated_at' => $updatedAt,
            ], fn ($value) => $value !== null && $value !== '');
        }

        $order->update(['metadata' => $metadata]);

        $invitationItem = $this->primaryInvitationItem($order);
        if ($invitationItem) {
            $designData = $invitationItem->design_metadata;
            if (is_string($designData)) {
                $decoded = json_decode($designData, true);
                $designData = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($designData)) {
                $designData = [];
            }

            if (!empty($designMeta)) {
                $designData['design'] = $designMeta;
            }

            if (!empty($placeholders)) {
                $designData['placeholders'] = $placeholders;
            }

            $designData['snapshot'] = array_filter([
                'preview_image' => $previewImage,
                'preview_images' => $previewImages,
            ], fn ($value) => $value !== null && $value !== '');

            $designData['updated_at'] = $updatedAt;

            $invitationItem->design_metadata = $designData;

            // Persist into dedicated columns when present
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'design_svg') && $designSvg) {
                $invitationItem->design_svg = $designSvg;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'design_json') && $designJsonEncoded) {
                $invitationItem->design_json = $designJsonEncoded;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'canvas_width') && $canvasWidth !== null) {
                $invitationItem->canvas_width = $canvasWidth;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'canvas_height') && $canvasHeight !== null) {
                $invitationItem->canvas_height = $canvasHeight;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'background_color') && $backgroundColor) {
                $invitationItem->background_color = $backgroundColor;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($invitationItem->getTable(), 'preview_image') && $previewImage) {
                $invitationItem->preview_image = $previewImage;
            }
            $invitationItem->save();
        }

        return $order->fresh(['items']);
    }

    public function persistDesignDraft(Product $product, array $payload, ?Authenticatable $user = null): CustomerTemplateCustom
    {
        Log::info('persistDesignDraft called', ['payload_keys' => array_keys($payload)]);

        $user = $user ?? Auth::user();
        $userId = $user?->getAuthIdentifier();
        $customerId = $user?->customer?->customer_id;

        $match = [
            'template_id' => $product->template_id,
            'product_id' => $product->id,
        ];

        if ($userId) {
            $match['user_id'] = $userId;
        } elseif ($customerId) {
            $match['customer_id'] = $customerId;
        }

        $record = CustomerTemplateCustom::query()->firstOrNew($match);

        // Process preview_image
        $previewImage = Arr::get($payload, 'preview_image');
        if ($previewImage && Str::startsWith($previewImage, 'data:')) {
            $extension = str_contains($previewImage, 'svg') ? 'svg' : 'png';
            $previewImage = $this->persistDataUrl($previewImage, 'customer/designs/preview', $extension, $record->preview_image, 'preview_image');
        }

        // Process preview_images
        $previewImages = Arr::get($payload, 'preview_images', []);
        $processedPreviewImages = [];
        foreach ($previewImages as $key => $image) {
            if (is_string($image) && Str::startsWith($image, 'data:')) {
                $extension = str_contains($image, 'svg') ? 'svg' : 'png';
                $processedPreviewImages[$key] = $this->persistDataUrl($image, 'customer/designs/preview', $extension, $record->preview_images[$key] ?? null, 'preview_images.' . $key);
            } else {
                $processedPreviewImages[$key] = $image;
            }
        }

        // Process svg
        $design = Arr::get($payload, 'design', []);
        $svgPath = $record->svg_path;
        if (isset($design['sides'])) {
            foreach ($design['sides'] as $side => $sideData) {
                if (isset($sideData['svg']) && is_string($sideData['svg'])) {
                    $svgDataUrl = 'data:image/svg+xml;base64,' . base64_encode($sideData['svg']);
                    $svgPath = $this->persistDataUrl($svgDataUrl, 'customer/designs/svg', 'svg', $svgPath, 'svg');
                    break; // save only one for now
                }
            }
        }

        $record->fill([
            'customer_id' => $customerId,
            'user_id' => $userId,
            'template_id' => $product->template_id,
            'product_id' => $product->id,
            'order_id' => Arr::get($payload, 'order_id'),
            'order_item_id' => Arr::get($payload, 'order_item_id'),
            'status' => Arr::get($payload, 'status', 'draft'),
            'is_locked' => (bool) Arr::get($payload, 'is_locked', false),
            'design' => $design,
            'summary' => Arr::get($payload, 'summary'),
            'placeholders' => Arr::get($payload, 'placeholders', []),
            'preview_image' => $previewImage,
            'preview_images' => $processedPreviewImages,
            'svg_path' => $svgPath,
            'last_edited_at' => Arr::get($payload, 'last_edited_at') ? Carbon::parse(Arr::get($payload, 'last_edited_at')) : now(),
        ]);

        $record->save();

        return $record->refresh();
    }

    public function persistFinalizedSelection(?Order $order, array $summary, ?Product $product = null): CustomerFinalized
    {
        $user = Auth::user();
        $customerId = $order?->customer_id ?? $user?->customer?->customer_id;

        $productId = $product?->id ?? ($summary['productId'] ?? null);
        if (!$product && $productId) {
            $product = Product::find($productId);
        }

        $templateId = $product?->template_id ?? ($summary['template_id'] ?? null);

        $designMeta = $summary['metadata']['design'] ?? $summary['design'] ?? [];
        $previewImages = $summary['previewImages'] ?? [];
        $previewImage = $summary['previewImage']
            ?? Arr::get($previewImages, 0)
            ?? Arr::get($summary, 'invitationImage');

        $size = $summary['size']
            ?? $summary['invitation_size']
            ?? ($product->size ?? null);

        $paperStock = [
            'id' => $summary['paperStockId'] ?? null,
            'name' => $summary['paperStockName'] ?? null,
            'price' => $summary['paperStockPrice'] ?? null,
            'status' => $summary['paperStockStatus'] ?? null,
            'preorder' => $summary['paperStockPreorder'] ?? null,
        ];

        $estimatedDate = $summary['estimated_date']
            ?? data_get($summary, 'metadata.final_step.estimated_date')
            ?? data_get($summary, 'metadata.final_step.pickup_date');
        $paperPreorder = $summary['paperStockPreorder'] ?? null;
        $preOrderStatus = $paperPreorder ? 'pre_order' : ($summary['paperStockStatus'] ?? 'none');

        return $this->persistCustomerOrderItem($order, [
            'customer_id' => $customerId,
            'template_id' => $templateId,
            'product_id' => $productId,
            'design' => $designMeta,
            'preview_images' => $previewImages,
            'quantity' => $summary['quantity'] ?? null,
            'size' => $size,
            'paper_stock' => $paperStock,
            'estimated_date' => $estimatedDate,
            'total_price' => $summary['totalAmount'] ?? null,
            'status' => $summary['orderStatus'] ?? 'pending',
            'product_type' => $summary['product_type'] ?? 'invitation',
            'pre_order_status' => $preOrderStatus,
            'pre_order_date' => $summary['paperStockAvailabilityDate'] ?? null,
            'preview_image' => $previewImage,
        ]);
    }

    public function findFinalizedSelection(?Order $order, ?Product $product = null, ?int $customerId = null): ?CustomerFinalized
    {
        $customerId = $customerId ?? Auth::user()?->customer?->customer_id;

        $query = CustomerFinalized::query();

        if ($order?->customer_order_id) {
            $query->where('order_id', $order->customer_order_id);
        } elseif ($order?->id) {
            $query->where('order_id', $order->id);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($product?->id) {
            $query->where('product_id', $product->id);
        }

        return $query->latest()->first();
    }

    protected function normalizePreOrderStatus($value): string
    {
        if ($value === true) {
            return 'pre_order';
        }

        if ($value === false || $value === null) {
            return 'none';
        }

        $normalized = is_string($value) ? Str::lower(trim($value)) : null;

        if (!$normalized || $normalized === '') {
            return 'none';
        }

        if (in_array($normalized, ['pre_order', 'preorder', 'pre-order'], true)) {
            return 'pre_order';
        }

        if (in_array($normalized, ['available', 'in_stock', 'instock', 'in-stock'], true)) {
            return 'available';
        }

        return 'none';
    }

    protected function persistCustomerOrderItem(?Order $order, array $attributes): CustomerFinalized
    {
        $user = Auth::user();
        $customerId = $attributes['customer_id']
            ?? $order?->customer_id
            ?? $user?->customer?->customer_id;

        $productType = $attributes['product_type'] ?? 'invitation';
        $productId = $attributes['product_id'] ?? null;

        $design = $attributes['design'] ?? [];
        if (!is_array($design)) {
            $design = (array) $design;
        }

        $previewImages = $attributes['preview_images'] ?? [];
        if (!is_array($previewImages)) {
            $previewImages = array_filter([$previewImages]);
        }

        $firstSideKey = array_key_first($design['sides'] ?? []) ?? 'front';
        $firstSide = $design['sides'][$firstSideKey] ?? [];
        $designSvg = $attributes['design_svg'] ?? (is_string($firstSide['svg'] ?? null) ? $firstSide['svg'] : null);
        $designJsonEncoded = $attributes['design_json'] ?? (!empty($design) ? json_encode($design) : null);
        $canvasWidth = Arr::get($attributes, 'canvas_width', Arr::get($design, 'canvas.width'));
        $canvasHeight = Arr::get($attributes, 'canvas_height', Arr::get($design, 'canvas.height'));
        $backgroundColor = Arr::get($attributes, 'background_color')
            ?? Arr::get($design, 'canvas.background')
            ?? Arr::get($design, 'canvas.background_color')
            ?? Arr::get($design, 'background_color');
        $previewImage = $attributes['preview_image'] ?? ($previewImages[0] ?? null);

        $paperStock = $attributes['paper_stock'] ?? null;
        if ($paperStock !== null && !is_array($paperStock)) {
            $paperStock = ['value' => $paperStock];
        }

        $preOrderStatus = $this->normalizePreOrderStatus($attributes['pre_order_status'] ?? null);

        $record = CustomerFinalized::query()->firstOrNew([
            'order_id' => $order?->customer_order_id,
            'customer_id' => $customerId,
            'product_id' => $productId,
            'product_type' => $productType,
        ]);

        $record->fill([
            'customer_id' => $customerId,
            'order_id' => $order?->customer_order_id,
            'template_id' => $attributes['template_id'] ?? null,
            'product_id' => $productId,
            'design' => $design,
            'preview_images' => $previewImages,
            'quantity' => $attributes['quantity'] ?? null,
            'size' => $attributes['size'] ?? null,
            'paper_stock' => $paperStock,
            'estimated_date' => $attributes['estimated_date'] ?? null,
            'total_price' => $attributes['total_price'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'product_type' => $productType,
            'pre_order_status' => $preOrderStatus,
            'pre_order_date' => $attributes['pre_order_date'] ?? null,
        ]);

        if (Schema::hasColumn($record->getTable(), 'design_svg')) {
            $record->design_svg = $designSvg;
        }
        if (Schema::hasColumn($record->getTable(), 'design_json')) {
            $record->design_json = $designJsonEncoded;
        }
        if (Schema::hasColumn($record->getTable(), 'canvas_width')) {
            $record->canvas_width = $canvasWidth;
        }
        if (Schema::hasColumn($record->getTable(), 'canvas_height')) {
            $record->canvas_height = $canvasHeight;
        }
        if (Schema::hasColumn($record->getTable(), 'background_color')) {
            $record->background_color = $backgroundColor;
        }
        if (Schema::hasColumn($record->getTable(), 'preview_image')) {
            $record->preview_image = $previewImage;
        }

        $record->save();

        return $record->refresh();
    }

    protected function persistEnvelopeRecord(Order $order, array $meta): CustomerFinalized
    {
        $images = $meta['images'] ?? [];
        if (!is_array($images)) {
            $images = [];
        }
        if (empty($images) && !empty($meta['image'])) {
            $images = [$meta['image']];
        }

        $paperStock = array_filter([
            'material' => $meta['material'] ?? null,
            'material_id' => $meta['material_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->persistCustomerOrderItem($order, [
            'product_id' => $meta['product_id'] ?? $meta['id'] ?? null,
            'design' => ['metadata' => $meta],
            'preview_images' => $images,
            'quantity' => $meta['qty'] ?? $meta['quantity'] ?? null,
            'size' => $meta['size'] ?? null,
            'paper_stock' => !empty($paperStock) ? $paperStock : null,
            'total_price' => $meta['total'] ?? $meta['total_price'] ?? null,
            'status' => $meta['status'] ?? 'pending',
            'product_type' => 'envelope',
            'pre_order_status' => $meta['pre_order_status'] ?? $meta['status'] ?? null,
            'pre_order_date' => $meta['pre_order_date'] ?? $meta['availability_date'] ?? null,
        ]);
    }

    protected function persistGiveawayRecord(Order $order, array $meta): CustomerFinalized
    {
        $images = $meta['images'] ?? [];
        if (!is_array($images)) {
            $images = [];
        }
        if (empty($images) && !empty($meta['image'])) {
            $images = [$meta['image']];
        }

        return $this->persistCustomerOrderItem($order, [
            'product_id' => $meta['product_id'] ?? $meta['id'] ?? null,
            'design' => ['metadata' => $meta],
            'preview_images' => $images,
            'quantity' => $meta['qty'] ?? $meta['quantity'] ?? null,
            'size' => $meta['size'] ?? null,
            'total_price' => $meta['total'] ?? $meta['total_price'] ?? null,
            'status' => $meta['status'] ?? 'pending',
            'product_type' => 'giveaway',
            'pre_order_status' => $meta['pre_order_status'] ?? null,
            'pre_order_date' => $meta['pre_order_date'] ?? null,
        ]);
    }

    protected function deleteCustomerOrderItems(Order $order, string $productType, ?int $productId = null): void
    {
        $query = CustomerFinalized::query()
            ->where('order_id', $order->id)
            ->where('product_type', $productType);

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        $query->delete();
    }

    protected function syncEnvelopeAssociations(OrderItem $orderItem, ?Product $product, array $meta): void
    {
        $quantity = max(1, (int) ($meta['qty'] ?? $meta['quantity'] ?? $orderItem->quantity ?? 1));
        $unitPrice = (float) ($meta['price'] ?? $meta['unit_price'] ?? $orderItem->unit_price ?? 0);

        // Envelope selections do not populate ancillary tables (addons, paper stock, colors, bulk)
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

        $estimatedPickupCandidate = Arr::get($summary, 'estimated_date')
            ?? Arr::get($summary, 'dateNeeded')
            ?? Arr::get($summary, 'metadata.final_step.estimated_date')
            ?? Arr::get($summary, 'metadata.final_step.metadata.estimated_date');

        if ($estimatedPickupCandidate) {
            try {
                $pickupDate = Carbon::parse($estimatedPickupCandidate)->format('Y-m-d');

                $finalStepMeta = Arr::get($metadata, 'final_step', []);
                if (!is_array($finalStepMeta)) {
                    $finalStepMeta = [];
                }

                $finalStepMeta['estimated_date'] = $pickupDate;
                if (!empty($summary['dateNeededLabel'])) {
                    $finalStepMeta['estimated_date_label'] = $summary['dateNeededLabel'];
                }
                $metadata['final_step'] = $finalStepMeta;

                $deliveryMeta = Arr::get($metadata, 'delivery', []);
                if (!is_array($deliveryMeta)) {
                    $deliveryMeta = [];
                }
                $deliveryMeta['estimated_pickup_date'] = $pickupDate;
                if (!empty($summary['dateNeededLabel'])) {
                    $deliveryMeta['estimated_pickup_label'] = $summary['dateNeededLabel'];
                }
                $metadata['delivery'] = $deliveryMeta;
            } catch (\Throwable $e) {
                // Ignore pickup date parse failures
            }
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
        $metadata = $order->metadata ?? [];
        $omitBasePrice = Arr::get($metadata, 'final_step.metadata.omit_base_price', false);

        $pricing = [
            'unit_price' => $item->unit_price,
            'subtotal' => $order->subtotal_amount,
            'tax' => $order->tax_amount,
            'shipping' => $order->shipping_fee,
            'total' => $order->total_amount,
        ];

        if ($omitBasePrice) {
            $paymentAmount = Arr::get($metadata, 'final_step.metadata.payment_amount', 0);
            $pricing['total'] = (float) $paymentAmount;
            $pricing['subtotal'] = (float) $paymentAmount;
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'product' => [
                'id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
            ],
            'pricing' => $pricing,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    public function buildQuantityOptions(?Product $product, ?int $selectedQuantity): array
    {
        $basePrice = $this->unitPriceFor($product);

        return collect(range(1, 20))->map(function ($step) use ($basePrice) {
            $qty = $step * 10;

            return [
                'label' => number_format($qty),
                'value' => $qty,
                'price' => round($qty * $basePrice, 2),
            ];
        })->when($selectedQuantity, function ($options) use ($selectedQuantity, $basePrice) {
            if (!$options->contains(fn ($option) => $option['value'] === $selectedQuantity)) {
                $options->push([
                    'label' => number_format($selectedQuantity),
                    'value' => $selectedQuantity,
                    'price' => round($selectedQuantity * $basePrice, 2),
                ]);
            }

            return $options->sortBy('value')->values();
        })->values()->all();
    }

    public function getQuantityLimits(?Product $product): array
    {
        return ['min' => 10, 'max' => 200];
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
                $available = $stock->material?->stock_qty ?? 0;
                return [
                    'id' => $stockId,
                    'name' => $stock->name ?? 'Paper Stock',
                    'price' => $stock->price !== null ? (float) $stock->price : null,
                    'image' => $this->resolveMediaPath($stock->image_path, $fallbackImage),
                    'selected' => $selectedKey !== null && $selectedKey === $stockId,
                    'available' => (int) $available,
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

        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::url($normalized);
        }

        return $fallback;
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

        $estimatedDateInput = $payload['estimated_date']
            ?? ($metadataPayload['estimated_date'] ?? null);
        $pickupDate = null;
        if ($estimatedDateInput) {
            try {
                $pickupDate = Carbon::parse($estimatedDateInput)->startOfDay();
            } catch (\Throwable $e) {
                $pickupDate = null;
            }
        }

        if ($pickupDate) {
            $metadataPayload['estimated_date'] = $pickupDate->toDateString();
            $metadataPayload['estimated_date_label'] = $metadataPayload['estimated_date_label']
                ?? $pickupDate->format('F j, Y');
        } else {
            unset($metadataPayload['estimated_date']);
            unset($metadataPayload['estimated_date_label']);
        }

        $addonQuantitiesPayload = collect($payload['addon_quantities'] ?? [])
            ->mapWithKeys(function ($value, $key) {
                $id = null;
                $quantity = null;

                if (is_array($value)) {
                    if (isset($value['addon_id']) && is_numeric($value['addon_id'])) {
                        $id = (int) $value['addon_id'];
                    }

                    if (isset($value['quantity']) && is_numeric($value['quantity'])) {
                        $quantity = (int) $value['quantity'];
                    } elseif (isset($value['qty']) && is_numeric($value['qty'])) {
                        $quantity = (int) $value['qty'];
                    } elseif (isset($value['value']) && is_numeric($value['value'])) {
                        $quantity = (int) $value['value'];
                    }
                }

                if ($id === null && (is_numeric($key) || (is_string($key) && is_numeric($key)))) {
                    $id = (int) $key;
                }

                if ($quantity === null && !is_array($value) && is_numeric($value)) {
                    $quantity = (int) $value;
                }

                if ($id === null || $quantity === null || $quantity < 1) {
                    return [];
                }

                return [$id => $quantity];
            });

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

        $existingAddonRecords = $item->addons->keyBy('size_id');

        $item->addons()->whereNotIn('size_id', $addonIds->all())->delete();

        if ($addonIds->isNotEmpty()) {
            $addons = ProductSize::query()->whereIn('id', $addonIds)->get();
            foreach ($addons as $addon) {
                $requestedQuantity = max(1, (int) ($addonQuantitiesPayload->get($addon->id, $quantity)));

                $metadata = $existingAddonRecords->get($addon->id)?->pricing_metadata ?? [];
                if (!is_array($metadata)) {
                    $metadata = [];
                }
                $metadata['quantity'] = $requestedQuantity;

                $item->addons()->updateOrCreate(
                    ['size_id' => $addon->id],
                    [
                        'size_type' => $addon->addon_type,
                        'size' => $addon->name ?? 'Add-on',
                        'size_price' => $addon->price ?? 0,
                        'quantity' => $requestedQuantity,
                        'pricing_metadata' => $metadata,
                    ]
                );
            }
        }

        $this->syncBulkSelection($item, $product, $quantity, $unitPrice);
        $this->syncColorSelections($item, $previewSelections, $product);

        $item->load(['addons', 'paperStockSelection', 'colors']);

        $meta = $order->metadata ?? [];
        $previousFinalStep = Arr::get($meta, 'final_step');
        if (!is_array($previousFinalStep)) {
            $previousFinalStep = [];
        }
        $pickupDateLabel = $pickupDate ? $pickupDate->format('F j, Y') : null;
        $meta['final_step'] = [
            'quantity' => $item->quantity,
            'paper_stock_id' => $item->paperStockSelection?->paper_stock_id,
            'paper_stock_name' => $item->paperStockSelection?->paper_stock_name,
            'paper_stock_price' => $item->paperStockSelection?->price,
            'addon_ids' => $item->addons->pluck('size_id')->filter()->values()->all(),
            'addon_quantities' => $item->addons
                ->filter(fn ($addon) => $addon->size_id !== null)
                ->mapWithKeys(fn ($addon) => [$addon->size_id => max(1, (int) ($addon->quantity ?? 1))])
                ->all(),
            'estimated_date' => $pickupDate ? $pickupDate->toDateString() : Arr::get($previousFinalStep, 'estimated_date'),
            'estimated_date_label' => $pickupDateLabel ?? Arr::get($previousFinalStep, 'estimated_date_label'),
            'color_ids' => [],
            'colors' => $item->colors->map(function ($color) {
                return array_filter([
                    'average_usage_ml' => $color->average_usage_ml,
                    'total_ink_ml' => $color->total_ink_ml,
                ], fn ($value) => $value !== null && $value !== '');
            })->values()->all(),
            'metadata' => $metadataPayload,
            'preview_selections' => $previewSelections,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($pickupDate) {
            $deliveryMeta = Arr::get($meta, 'delivery');
            if (!is_array($deliveryMeta)) {
                $deliveryMeta = [];
            }

            $deliveryMeta['estimated_pickup_date'] = $pickupDate->toDateString();
            $deliveryMeta['estimated_pickup_label'] = $pickupDateLabel;
            $deliveryMeta['estimated_ship_date'] = $deliveryMeta['estimated_ship_date'] ?? $pickupDate->toDateString();

            $meta['delivery'] = array_filter($deliveryMeta, fn ($value) => $value !== null && $value !== '');
        }

        $updatePayload = ['metadata' => $meta];
        if ($pickupDate) {
            $updatePayload['date_needed'] = $pickupDate->toDateString();
        }

        $order->update($updatePayload);

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

    public function resolveEnvelopeAvailability(ProductEnvelope $envelope): array
    {
        $material = $envelope->material;

        if (!$material && $envelope->material_id) {
            $material = $envelope->material()->with('inventory')->first();
        }

        if (!$material && $envelope->envelope_material_name) {
            $material = Material::query()
                ->with('inventory')
                ->where('material_name', $envelope->envelope_material_name)
                ->orderByDesc('date_updated')
                ->first();
        } elseif ($material && !$material->relationLoaded('inventory')) {
            $material->loadMissing('inventory');
        }

        $stock = null;
        if ($material) {
            $inventoryStock = $material->inventory?->stock_level;
            $stock = $inventoryStock !== null ? (int) $inventoryStock : $material->stock_qty;
        }

        $stock = $stock !== null ? max(0, (int) $stock) : null;

        $configuredMax = $envelope->max_quantity ?? $envelope->max_qty;
        $configuredMax = $configuredMax !== null ? max(0, (int) $configuredMax) : null;

        $maxQuantity = $stock !== null
            ? ($configuredMax !== null ? min($stock, $configuredMax) : $stock)
            : $configuredMax;

        return [
            'material' => $material,
            'available_stock' => $stock,
            'configured_max' => $configuredMax,
            'max_quantity' => $maxQuantity,
        ];
    }

    public function applyEnvelopeSelection(Order $order, array $payload): Order
    {
        $envelopeId = (int) ($payload['envelope_id'] ?? 0);
        $resolvedEnvelope = null;
        $availability = null;

        if ($envelopeId > 0) {
            $resolvedEnvelope = ProductEnvelope::with(['material', 'material.inventory'])->find($envelopeId);
            if ($resolvedEnvelope) {
                $availability = $this->resolveEnvelopeAvailability($resolvedEnvelope);
            }
        }

        $meta = $order->metadata ?? [];

        $quantity = max(1, (int) ($payload['quantity'] ?? 0));
        $unitPrice = (float) ($payload['unit_price'] ?? 0);
        $total = $payload['total_price'] ?? null;
        if ($total === null) {
            $total = $quantity * $unitPrice;
        }

        $envelopeMeta = $payload['metadata'] ?? [];

        $minQuantity = (int) ($envelopeMeta['min_qty'] ?? Arr::get($payload, 'metadata.min_qty') ?? 10);
        // Removed hardcoded minimum enforcement - let the API and frontend handle constraints

        $maxQuantity = Arr::get($availability, 'max_quantity');
        $metaMaxCandidate = $envelopeMeta['max_qty'] ?? Arr::get($payload, 'metadata.max_qty');
        if ($metaMaxCandidate !== null) {
            $metaMaxCandidate = (int) $metaMaxCandidate;
            $maxQuantity = $maxQuantity !== null
                ? min($maxQuantity, $metaMaxCandidate)
                : $metaMaxCandidate;
        }
        if ($maxQuantity !== null) {
            $maxQuantity = (int) $maxQuantity;
            if ($maxQuantity < $minQuantity) {
                $maxQuantity = $minQuantity;
            }
        }

        if ($maxQuantity !== null && $maxQuantity < $quantity) {
            $quantity = $maxQuantity;
            $total = $quantity * $unitPrice;
        }

        $materialName = $envelopeMeta['material'] ?? $resolvedEnvelope?->envelope_material_name;
        if (!$materialName && $availability && Arr::get($availability, 'material')) {
            $materialName = Arr::get($availability, 'material.material_name');
        }

        $envelopeMeta = array_filter([
            'id' => $payload['envelope_id'] ?? $envelopeMeta['id'] ?? null,
            'product_id' => $payload['product_id'] ?? $resolvedEnvelope?->product_id,
            'name' => $envelopeMeta['name'] ?? null,
            'unit_price' => $unitPrice,
            'price' => $unitPrice,
            'qty' => $quantity,
            'total' => (float) $total,
            'material' => $materialName,
            'image' => $envelopeMeta['image'] ?? null,
            'min_qty' => $minQuantity,
            'max_qty' => $maxQuantity,
            'available_stock' => Arr::get($availability, 'available_stock'),
            'material_id' => $resolvedEnvelope?->material_id,
            'updated_at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');

        // Support multiple envelope selections; keep legacy single "envelope" for backward compatibility
        $envelopes = $meta['envelopes'] ?? [];
        if (empty($envelopes) && !empty($meta['envelope'])) {
            $envelopes[] = $meta['envelope'];
        }

        // Replace existing entry by id or append
        $existingIndex = null;
        foreach ($envelopes as $idx => $envelopeRow) {
            if (($envelopeRow['id'] ?? null) === ($envelopeMeta['id'] ?? null)) {
                $existingIndex = $idx;
                break;
            }
        }
        if ($existingIndex !== null) {
            $envelopes[$existingIndex] = $envelopeMeta;
        } else {
            $envelopes[] = $envelopeMeta;
        }

        $meta['envelopes'] = array_values($envelopes);
        $meta['envelope'] = $meta['envelopes'][0] ?? $envelopeMeta; // legacy consumers
        $order->update(['metadata' => $meta]);

        $this->upsertEnvelopeOrderItem($order, $envelopeMeta);

        $order->refresh();
        $this->recalculateOrderTotals($order);

        $order->refresh();
        $primaryItem = $this->primaryInvitationItem($order);
        if ($primaryItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }
        $this->syncMaterialUsage($order);

        $this->persistEnvelopeRecord($order, $envelopeMeta);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function applyGiveawaySelection(Order $order, array $payload): Order
    {
        $productId = (int) ($payload['product_id'] ?? 0);
        $product = Product::with(['template', 'uploads', 'images'])->find($productId);
        if ($product) {
            $product->setRelation('bulkOrders', collect());
        }

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
            'is_preorder' => $payload['is_preorder'] ?? false,
            'updated_at' => now()->toIso8601String(),
        ], function ($value, $key) {
            if ($key === 'images') {
                return is_array($value) && !empty($value);
            }

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        $giveaways = $meta['giveaways'] ?? [];
        // Migrate old single giveaway if exists
        if (empty($giveaways) && !empty($meta['giveaway'])) {
            $oldId = $meta['giveaway']['product_id'] ?? $meta['giveaway']['id'] ?? null;
            if ($oldId) {
                $giveaways[$oldId] = $meta['giveaway'];
            }
        }
        
        $giveaways[$product->id] = $giveawayMeta;
        $meta['giveaways'] = $giveaways;
        unset($meta['giveaway']);
        
        $order->update(['metadata' => $meta]);

        $this->upsertGiveawayOrderItem($order, $giveawayMeta);

        $order->refresh();
        $this->recalculateOrderTotals($order);

        $order->refresh();
        $primaryItem = $this->primaryInvitationItem($order);
        if ($primaryItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }
        $this->syncMaterialUsage($order);

        $this->persistGiveawayRecord($order, $giveawayMeta);

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function clearEnvelopeSelection(Order $order): Order
    {
        $meta = $order->metadata ?? [];
        unset($meta['envelope'], $meta['envelopes']);

        $order->update(['metadata' => $meta]);

        $this->removeLineItem($order, OrderItem::LINE_TYPE_ENVELOPE);

        $order->refresh();

        $this->recalculateOrderTotals($order);

        $order->refresh();
        $primaryItem = $this->primaryInvitationItem($order);
        if ($primaryItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }
        $this->syncMaterialUsage($order);

        $this->deleteCustomerOrderItems($order, 'envelope');

        return $order->fresh([
            'items.product',
            'items.addons',
            'items.paperStockSelection',
        ]);
    }

    public function clearGiveawaySelection(Order $order, ?int $productId = null): Order
    {
        $meta = $order->metadata ?? [];
        
        if ($productId) {
            $giveaways = $meta['giveaways'] ?? [];
            if (empty($giveaways) && !empty($meta['giveaway'])) {
                $oldId = $meta['giveaway']['product_id'] ?? $meta['giveaway']['id'] ?? null;
                if ($oldId) {
                    $giveaways[$oldId] = $meta['giveaway'];
                }
            }
            
            unset($giveaways[$productId]);
            $meta['giveaways'] = $giveaways;
            unset($meta['giveaway']);
            
            $order->items()
                ->where('line_type', OrderItem::LINE_TYPE_GIVEAWAY)
                ->where('product_id', $productId)
                ->delete();
        } else {
            unset($meta['giveaway']);
            unset($meta['giveaways']);
            $this->removeLineItem($order, OrderItem::LINE_TYPE_GIVEAWAY);
        }

        $order->update(['metadata' => $meta]);

        $order->refresh();

        $this->recalculateOrderTotals($order);

        $order->refresh();
        $primaryItem = $this->primaryInvitationItem($order);
        if ($primaryItem) {
            $order->update([
                'summary_snapshot' => $this->buildSummarySnapshot($order, $primaryItem),
            ]);
        }
        $this->syncMaterialUsage($order);

        if ($productId) {
            $this->deleteCustomerOrderItems($order, 'giveaway', $productId);
        } else {
            $this->deleteCustomerOrderItems($order, 'giveaway');
        }

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
     * Persists the per-order material requirements in the `product_materials` table and applies
     * differential stock adjustments so materials are only deducted once and restored on changes.
     */
    public function syncMaterialUsage(Order $order): void
    {
        $order->loadMissing([
            'items.product',
            'items.product.inkUsage',
            'items.paperStockSelection',
            'items.addons',
        ]);

        // Do not deduct materials for orders with pending payment
        if (strtolower($order->payment_status ?? '') === 'pending') {
            return;
        }

        // Also check if balance is due by loading payments if not already loaded
        if (!$order->relationLoaded('payments')) {
            $order->load('payments');
        }
        $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
        $totalPaid = round($paidPayments->sum('amount'), 2);
        $grandTotal = (float) ($order->total_amount ?? 0);
        $balanceDue = max($grandTotal - $totalPaid, 0);
        if ($balanceDue > 0) {
            return;
        }

        $materialTotals = [];
        $materialCache = [];
        $paperStockCache = [];
        $envelopeCache = [];
        $addonCache = [];
        $materialNameCache = [];
        $materialOrderingColumn = Schema::hasColumn('materials', 'updated_at') ? 'updated_at' : 'material_id';

        $existingUsageRecords = ProductMaterial::query()
            ->where('order_id', $order->id)
            ->where('source_type', 'custom')
            ->get()
            ->keyBy(fn (ProductMaterial $row) => (int) $row->material_id);

        $existingDetailRecords = ProductMaterial::query()
            ->where('order_id', $order->id)
            ->whereIn('source_type', ['product', 'paper_stock', 'envelope', 'addon'])
            ->get();

        $detailRecordIndex = $existingDetailRecords->keyBy(function (ProductMaterial $row) {
            return implode(':', [
                (int) $row->material_id,
                (string) ($row->order_item_id ?? 'null'),
                (string) ($row->source_type ?? 'product'),
                (string) ($row->source_id ?? 'null'),
            ]);
        });

        $resolveMaterialByName = function (?string $name) use (&$materialNameCache, $materialOrderingColumn) {
            if (!$name) {
                return null;
            }

            $normalized = Str::of($name)->lower()->trim()->value();
            if ($normalized === '') {
                return null;
            }

            $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
            $lookupKeys = array_values(array_filter(array_unique([$normalized, $compact])));

            foreach ($lookupKeys as $lookupKey) {
                if (array_key_exists($lookupKey, $materialNameCache)) {
                    $cached = $materialNameCache[$lookupKey];
                    return $cached instanceof Material ? $cached : null;
                }
            }

            $material = Material::query()
                ->with('inventory')
                ->where(function ($query) use ($normalized, $compact) {
                    $query->whereRaw('LOWER(material_name) = ?', [$normalized]);

                    if ($compact !== '' && $compact !== $normalized) {
                        $query->orWhereRaw("REPLACE(REPLACE(REPLACE(LOWER(material_name), ' ', ''), '-', ''), '_', '') = ?", [$compact]);
                    }
                })
                ->orderByDesc($materialOrderingColumn)
                ->first();

            foreach ($lookupKeys as $lookupKey) {
                $materialNameCache[$lookupKey] = $material ?: false;
            }

            return $material ?: null;
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

            $quantityMode = $meta['quantity_mode'] ?? 'per_unit';
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

        $inkUsageContext = $this->determineInkUsageDistribution($order);
        $inkUsageTotalsMl = $inkUsageContext['totals_ml'] ?? [];
        $inkUsageTotalsUnits = $inkUsageContext['totals_units'] ?? [];
        $orderInkSnapshots = $inkUsageContext['items'] ?? [];
        $orderItemsById = $order->items->keyBy('id');

        foreach ($order->items as $item) {
            $productMaterialUsageFound = false;
            if ($item->product_id) {
                // Skip ProductMaterial processing for invitations - they should only use selected paper stock
                if ($item->line_type !== OrderItem::LINE_TYPE_INVITATION) {
                    $productMaterials = ProductMaterial::query()
                        ->with(['material.inventory'])
                        ->where('product_id', $item->product_id)
                        ->whereNull('order_id')
                        ->get();

                    foreach ($productMaterials as $productMaterial) {
                        $perUnitQty = (float) ($productMaterial->qty ?? 0);
                        if ($perUnitQty <= 0) {
                            continue;
                        }

                        $linkedMaterial = $productMaterial->material;

                        if (!$linkedMaterial && $productMaterial->material_id) {
                            $linkedMaterial = $materialCache[$productMaterial->material_id] ??=
                                Material::query()->with('inventory')->find($productMaterial->material_id);
                        }

                        if (!$linkedMaterial && $productMaterial->item) {
                            $linkedMaterial = $resolveMaterialByName($productMaterial->item);
                        }

                        if (!$linkedMaterial) {
                            continue;
                        }

                        $accumulateMaterial($item, $linkedMaterial, $perUnitQty, [
                            'product_material_id' => $productMaterial->id,
                            'source' => 'product_material',
                            'quantity_mode' => match ($productMaterial->quantity_mode) {
                                'per_order' => 'per_order',
                                default => 'per_unit',
                            },
                            'item' => $productMaterial->item,
                            'type' => $productMaterial->type,
                        ]);

                        $productMaterialUsageFound = true;
                    }
                }
            }

            if (!$productMaterialUsageFound) {
                $productType = Str::lower((string) ($item->product?->product_type
                    ?? Arr::get($item->design_metadata ?? [], 'product_type', '')));

                $eligibleForFallback = in_array($item->line_type, [OrderItem::LINE_TYPE_GIVEAWAY], true)
                    || in_array($productType, ['giveaway', 'souvenir', 'souvenirs'], true);

                if ($eligibleForFallback) {
                    $metaMaterialId = Arr::get($item->design_metadata ?? [], 'material_id')
                        ?? Arr::get($item->design_metadata ?? [], 'material.material_id');

                    if (!$metaMaterialId && $item->line_type === OrderItem::LINE_TYPE_GIVEAWAY) {
                        $giveawayMeta = Arr::get($order->metadata ?? [], 'giveaways.' . ($item->product_id ?? $item->id))
                            ?? Arr::get($order->metadata ?? [], 'giveaways.' . ($item->design_metadata['product_id'] ?? ''))
                            ?? Arr::get($order->metadata ?? [], 'giveaway');
                        $metaMaterialId = Arr::get($giveawayMeta ?? [], 'material_id')
                            ?? Arr::get($giveawayMeta ?? [], 'material.material_id');
                    }

                    if ($metaMaterialId) {
                        $fallbackMaterial = $materialCache[$metaMaterialId] ??=
                            Material::query()->with('inventory')->find($metaMaterialId);

                        if ($fallbackMaterial) {
                            $perUnitOverride = Arr::get($item->design_metadata ?? [], 'material_qty_per_unit');
                            if (!is_numeric($perUnitOverride) || (float) $perUnitOverride <= 0) {
                                $perUnitOverride = 1.0;
                            } else {
                                $perUnitOverride = (float) $perUnitOverride;
                            }

                            $accumulateMaterial($item, $fallbackMaterial, $perUnitOverride, [
                                'source' => 'product_fallback',
                                'fallback_id' => $metaMaterialId,
                            ]);

                            $productMaterialUsageFound = true;
                        }
                    }

                    $fallbackNames = array_filter([
                        $item->product?->name,
                        Arr::get($item->design_metadata ?? [], 'name'),
                        Arr::get($item->design_metadata ?? [], 'material_name'),
                        Arr::get($order->metadata, 'giveaway.name'),
                        Arr::get($order->metadata, 'giveaway.material'),
                        $item->product_name ?? null,
                    ], fn ($name) => is_string($name) && trim($name) !== '');

                    $perUnitOverride = Arr::get($item->design_metadata ?? [], 'material_qty_per_unit');
                    if (!is_numeric($perUnitOverride) || (float) $perUnitOverride <= 0) {
                        $perUnitOverride = 1.0;
                    } else {
                        $perUnitOverride = (float) $perUnitOverride;
                    }

                    foreach ($fallbackNames as $candidateName) {
                        $fallbackMaterial = $resolveMaterialByName($candidateName);
                        if (!$fallbackMaterial) {
                            continue;
                        }

                        $accumulateMaterial($item, $fallbackMaterial, $perUnitOverride, [
                            'source' => 'product_fallback',
                            'fallback_name' => $candidateName,
                        ]);

                        $productMaterialUsageFound = true;
                        break;
                    }
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

                if ($paperStock) {
                    $paperMaterial = $paperStock->material;
                    if (!$paperMaterial) {
                        $fallbackNames = array_filter([
                            $paperStock->material_name ?? null,
                            $paperStock->name ?? null,
                        ], fn ($value) => is_string($value) && trim($value) !== '');

                        foreach ($fallbackNames as $candidateName) {
                            $paperMaterial = $resolveMaterialByName($candidateName);
                            if ($paperMaterial) {
                                break;
                            }
                        }
                    }

                    if ($paperMaterial) {
                        // Check if this material has already been processed through ProductMaterial records
                        // to avoid double counting when fallback paper stock matches existing product materials
                        // Skip this check for invitations since they don't use ProductMaterial records
                        $materialAlreadyProcessed = false;
                        if ($item->line_type !== OrderItem::LINE_TYPE_INVITATION && $item->product_id) {
                            $existingProductMaterials = ProductMaterial::query()
                                ->where('product_id', $item->product_id)
                                ->whereNull('order_id')
                                ->where('material_id', $paperMaterial->getKey())
                                ->exists();
                            $materialAlreadyProcessed = $existingProductMaterials;
                        }

                        if (!$materialAlreadyProcessed) {
                            $accumulateMaterial($item, $paperMaterial, 1.0, [
                                'paper_stock_id' => $paperStock->id,
                                'source' => 'paper_stock',
                            ]);
                        }
                    }
                }
            }

            if ($item->line_type === OrderItem::LINE_TYPE_ENVELOPE) {
                $envelope = null;
                if ($item->product_id) {
                    $envelope = $envelopeCache[$item->product_id] ??= ProductEnvelope::query()
                        ->with(['material.inventory'])
                        ->where('product_id', $item->product_id)
                        ->first();
                }

                $envelopeMaterial = $envelope?->material;

                if (!$envelopeMaterial && $envelope?->material_id) {
                    $envelopeMaterial = $materialCache[$envelope->material_id] ??=
                        Material::query()->with('inventory')->find($envelope->material_id);
                }

                if (!$envelopeMaterial) {
                    $metaMaterialId = Arr::get($item->design_metadata ?? [], 'material_id')
                        ?? Arr::get($item->design_metadata ?? [], 'material.material_id')
                        ?? Arr::get($order->metadata ?? [], 'envelope.material_id')
                        ?? Arr::get($order->metadata ?? [], 'envelope.material.material_id');

                    if ($metaMaterialId) {
                        $envelopeMaterial = $materialCache[$metaMaterialId] ??=
                            Material::query()->with('inventory')->find($metaMaterialId);
                    }

                    $candidateNames = array_filter([
                        $envelope?->envelope_material_name,
                        Arr::get($item->design_metadata ?? [], 'material'),
                        Arr::get($order->metadata ?? [], 'envelope.material'),
                        $item->product_name,
                    ], fn ($value) => is_string($value) && trim($value) !== '');

                    foreach ($candidateNames as $candidateName) {
                        $envelopeMaterial = $resolveMaterialByName($candidateName);
                        if ($envelopeMaterial) {
                            break;
                        }
                    }
                }

                if ($envelopeMaterial) {
                    $accumulateMaterial($item, $envelopeMaterial, 1.0, [
                        'envelope_id' => $envelope?->id,
                        'source' => 'envelope',
                    ]);
                }
            }

            if ($item->addons && $item->addons->isNotEmpty()) {
                foreach ($item->addons as $addonSelection) {
                    $addon = null;
                    if ($addonSelection->size_id) {
                        $addon = $addonCache[$addonSelection->size_id] ??=
                            ProductSize::query()
                                ->with(['material.inventory'])
                                ->find($addonSelection->size_id);
                    }

                    $addonName = $addon?->name ?? $addonSelection->size ?? null;

                    $material = null;
                    if ($addon && $addon->material) {
                        $material = $addon->material;
                    } elseif ($addon && $addon->material_id) {
                        $material = $materialCache[$addon->material_id] ??=
                            Material::query()->with('inventory')->find($addon->material_id);
                    }

                    if (!$material) {
                        $material = $resolveMaterialByName($addonName);
                    }

                    if (!$material) {
                        continue;
                    }

                    $addonQuantity = max(1, (int) ($addonSelection->quantity ?? data_get($addonSelection->pricing_metadata, 'quantity', 1)));

                    $accumulateMaterial($item, $material, (float) $addonQuantity, [
                        'addon_id' => $addonSelection->size_id,
                        'addon_name' => $addonName,
                        'addon_quantity' => $addonQuantity,
                        'source' => 'addon',
                        'quantity_mode' => 'per_order',
                    ]);
                }
            }
        }

        if (!empty($orderInkSnapshots)) {
            foreach ($orderInkSnapshots as $snapshot) {
                $item = $orderItemsById->get($snapshot['order_item_id'] ?? null);
                if (!$item) {
                    continue;
                }

                foreach (($snapshot['distribution_ml'] ?? []) as $colorKey => $amount) {
                    if ($amount <= 0) {
                        continue;
                    }

                    $material = $resolveMaterialByName(Str::headline($colorKey) . ' Ink');
                    if ($material) {
                        $accumulateMaterial($item, $material, $amount, [
                            'source' => 'ink_cmyk',
                            'ink_type' => $colorKey,
                            'quantity_mode' => 'per_order',
                        ]);
                    }
                }
            }
        }

        $this->syncOrderInkUsage($order, $inkUsageTotalsMl, $orderInkSnapshots, $inkUsageTotalsUnits);

        $now = now();

        if (empty($materialTotals)) {
            if ($existingUsageRecords->isNotEmpty()) {
                foreach ($existingUsageRecords as $record) {
                    $material = Material::query()
                        ->with('inventory')
                        ->find($record->material_id);

                    if ($material && (float) $record->quantity_used > 0) {
                        $this->adjustMaterialStock($material, -1 * (float) $record->quantity_used);
                    }

                    $record->delete();
                }
            }

            if ($detailRecordIndex->isNotEmpty()) {
                foreach ($detailRecordIndex as $detailRecord) {
                    $detailRecord->delete();
                }
            }

            return;
        }

        foreach ($materialTotals as $materialId => $aggregate) {
            /** @var Material $material */
            $material = $aggregate['material'];
            if (!$material->relationLoaded('inventory')) {
                $material->loadMissing('inventory');
            }

            $previousRecord = $existingUsageRecords->get((int) $materialId);
            $previousUsed = $previousRecord ? (float) $previousRecord->quantity_used : 0.0;

            $requiredTotal = round((float) $aggregate['required'], 4);
            $diff = $requiredTotal - $previousUsed;

            $adjustment = [
                'applied' => 0.0,
                'shortage' => 0.0,
                'remaining_stock' => [
                    'inventory' => $material->inventory?->stock_level,
                    'material' => $material->stock_qty,
                ],
            ];

            if (abs($diff) > 0.00001) {
                $adjustment = $this->adjustMaterialStock($material, $diff);
            }

            $applied = (float) ($adjustment['applied'] ?? $diff);
            $newUsed = max(0.0, round($previousUsed + $applied, 4));
            $pendingShortage = max(0.0, round($requiredTotal - $newUsed, 4));
            $quantityReserved = max(0.0, round(min($requiredTotal, $newUsed), 4));

            $quantityModes = collect($aggregate['components'])
                ->pluck('quantity_mode')
                ->map(function ($mode) {
                    return $mode === 'per_item' ? 'per_unit' : $mode;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $orderQuantities = collect($aggregate['components'])
                ->pluck('order_quantity')
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (int) $value)
                ->sum();

            $quantityModeValue = (!empty($quantityModes) && count($quantityModes) === 1 && $quantityModes[0] === 'per_order')
                ? 'per_order'
                : 'per_unit';

            $reservedAt = $quantityReserved > 0
                ? ($previousRecord?->reserved_at ?? $now)
                : null;

            $deductedAt = $newUsed > 0
                ? ($previousRecord?->deducted_at ?? $now)
                : null;

            ProductMaterial::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'material_id' => $materialId,
                    'source_type' => 'custom',
                    'source_id' => null,
                    'order_item_id' => null,
                ],
                [
                    'customer_id' => $order->customer_id,
                    'product_id' => null,
                    'item' => $material->material_name ?? null,
                    'type' => $material->material_type ?? null,
                    'color' => $material->color ?? null,
                    'unit' => $material->unit ?? null,
                    'weight' => $material->weight_gsm ?? null,
                    'qty' => round((float) $aggregate['per_unit_qty'], 4),
                    'quantity_mode' => $quantityModeValue,
                    'order_quantity' => $orderQuantities !== 0 ? $orderQuantities : null,
                    'quantity_required' => $requiredTotal,
                    'quantity_reserved' => $quantityReserved,
                    'quantity_used' => $newUsed,
                    'reserved_at' => $reservedAt,
                    'deducted_at' => $deductedAt,
                    'metadata' => [
                        'components' => $aggregate['components'],
                        'pending_shortage' => $pendingShortage,
                        'remaining_stock' => $adjustment['remaining_stock'] ?? null,
                        'last_diff' => round($diff, 4),
                        'last_applied' => round($applied, 4),
                        'quantity_modes' => $quantityModes,
                    ],
                ]
            );

            $allocationScale = ($requiredTotal > 0 && $newUsed > 0)
                ? min(1.0, $newUsed / $requiredTotal)
                : 0.0;

            $detailGroups = [];
            foreach ($aggregate['components'] as $component) {
                $orderItemId = $component['order_item_id'] ?? null;
                $componentSource = $component['source'] ?? 'product';
                $sourceType = match ($componentSource) {
                    'product_material' => 'product',
                    'paper_stock' => 'paper_stock',
                    'envelope' => 'envelope',
                    'addon' => 'addon',
                    default => 'product',
                };

                $sourceId = match ($sourceType) {
                    'product' => $component['product_material_id'] ?? null,
                    'paper_stock' => $component['paper_stock_id'] ?? null,
                    'envelope' => $component['envelope_id'] ?? null,
                    'addon' => $component['addon_id'] ?? null,
                    default => null,
                };
                $sourceId = $sourceId !== null ? (int) $sourceId : null;

                $detailKey = implode(':', [
                    (int) $materialId,
                    (string) (($orderItemId ?? 'null')),
                    $sourceType,
                    (string) ($sourceId ?? 'null'),
                ]);

                if (!isset($detailGroups[$detailKey])) {
                    $detailGroups[$detailKey] = [
                        'order_item_id' => $orderItemId !== null ? (int) $orderItemId : null,
                        'product_id' => $component['product_id'] ?? null,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'quantity_modes' => [],
                        'per_unit_qty' => 0.0,
                        'required_qty' => 0.0,
                        'order_quantity' => null,
                        'components' => [],
                        'source_label' => $component['addon_name'] ?? ($component['source'] ?? null),
                    ];
                }

                $detailGroups[$detailKey]['quantity_modes'][] = $component['quantity_mode'] ?? 'per_unit';
                $detailGroups[$detailKey]['per_unit_qty'] = max(
                    $detailGroups[$detailKey]['per_unit_qty'],
                    (float) ($component['per_unit_qty'] ?? 0)
                );
                $detailGroups[$detailKey]['required_qty'] += (float) ($component['required_qty'] ?? 0);

                if (isset($component['order_quantity'])) {
                    $incomingQuantity = (int) $component['order_quantity'];
                    $currentQuantity = $detailGroups[$detailKey]['order_quantity'];
                    $detailGroups[$detailKey]['order_quantity'] = $currentQuantity === null
                        ? $incomingQuantity
                        : max((int) $currentQuantity, $incomingQuantity);
                }

                $detailGroups[$detailKey]['components'][] = $component;
            }

            if (!empty($detailGroups)) {
                $detailKeys = array_keys($detailGroups);
                $detailCount = count($detailKeys);
                $remainingUsedForAllocation = $newUsed;

                foreach ($detailKeys as $index => $detailKey) {
                    $group = $detailGroups[$detailKey];
                    $componentRequired = round($group['required_qty'], 4);

                    $componentUsed = 0.0;
                    if ($componentRequired > 0) {
                        if ($index === $detailCount - 1) {
                            $componentUsed = min($componentRequired, max(0.0, round($remainingUsedForAllocation, 4)));
                        } else {
                            $componentUsed = round(min($componentRequired, $componentRequired * $allocationScale), 4);
                            if ($componentUsed > $remainingUsedForAllocation) {
                                $componentUsed = $remainingUsedForAllocation;
                            }
                            $componentUsed = max(0.0, round($componentUsed, 4));
                        }
                    }

                    $remainingUsedForAllocation = max(0.0, round($remainingUsedForAllocation - $componentUsed, 4));

                    $componentReserved = $componentUsed;
                    $componentShortage = max(0.0, round($componentRequired - $componentUsed, 4));

                    $detailModeList = collect($group['quantity_modes'])
                        ->map(fn ($mode) => $mode === 'per_item' ? 'per_unit' : $mode)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $detailQuantityModeValue = (!empty($detailModeList) && count($detailModeList) === 1 && $detailModeList[0] === 'per_order')
                        ? 'per_order'
                        : 'per_unit';

                    $detailAttributes = [
                        'order_id' => $order->id,
                        'order_item_id' => $group['order_item_id'],
                        'material_id' => $materialId,
                        'source_type' => $group['source_type'],
                        'source_id' => $group['source_id'],
                    ];

                    $existingDetail = $detailRecordIndex->get($detailKey);

                    $detailReservedAt = $componentReserved > 0
                        ? ($existingDetail?->reserved_at ?? $reservedAt ?? $now)
                        : null;

                    $detailDeductedAt = $componentUsed > 0
                        ? ($existingDetail?->deducted_at ?? $now)
                        : null;

                    ProductMaterial::updateOrCreate(
                        $detailAttributes,
                        [
                            'customer_id' => $order->customer_id,
                            'product_id' => $group['product_id'] !== null ? (int) $group['product_id'] : null,
                            'item' => $material->material_name ?? null,
                            'type' => $material->material_type ?? null,
                            'color' => $material->color ?? null,
                            'unit' => $material->unit ?? null,
                            'weight' => $material->weight_gsm ?? null,
                            'qty' => round($group['per_unit_qty'], 4),
                            'quantity_mode' => $detailQuantityModeValue,
                            'order_quantity' => $group['order_quantity'] !== null ? (int) $group['order_quantity'] : null,
                            'quantity_required' => $componentRequired,
                            'quantity_reserved' => $componentReserved,
                            'quantity_used' => $componentUsed,
                            'reserved_at' => $detailReservedAt,
                            'deducted_at' => $detailDeductedAt,
                            'metadata' => [
                                'components' => $group['components'],
                                'pending_shortage' => $componentShortage,
                                'allocation_scale' => $allocationScale,
                                'source_label' => $group['source_label'],
                            ],
                        ]
                    );

                    $detailRecordIndex->forget($detailKey);
                }
            }

            $existingUsageRecords->forget((int) $materialId);
        }

        if ($detailRecordIndex->isNotEmpty()) {
            foreach ($detailRecordIndex as $detailRecord) {
                $detailRecord->delete();
            }
        }

        if ($existingUsageRecords->isNotEmpty()) {
            foreach ($existingUsageRecords as $record) {
                $material = Material::query()
                    ->with('inventory')
                    ->find($record->material_id);

                if ($material && (float) $record->quantity_used > 0) {
                    $this->adjustMaterialStock($material, -1 * (float) $record->quantity_used);
                }

                $record->delete();
            }
        }
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

        $applied = $currentMaterialStock - $newMaterialStock;

        $movementQuantity = (int) round($applied);
        if ($movementQuantity !== 0) {
            $movementType = $movementQuantity > 0 ? 'usage' : 'restock';

            StockMovement::create([
                'material_id' => $material->getKey(),
                'movement_type' => $movementType,
                'quantity' => abs($movementQuantity),
                'user_id' => Auth::id(),
            ]);
        }

        return [
            'applied' => $applied,
            'shortage' => $shortage,
            'remaining_stock' => [
                'inventory' => $inventory ? (int) round($newInventoryLevel) : null,
                'material' => (int) round($newMaterialStock),
            ],
        ];
    }

    public function checkStockFromSummary(array $summary): array
    {
        $product = $this->resolveProduct(null, $summary['productId'] ?? null);
        $quantity = max(1, (int) ($summary['quantity'] ?? 1));

        $shortages = [];
        $materialCache = [];
        $materialNameCache = [];

        $resolveMaterialByName = function (?string $name) use (&$materialNameCache) {
            if (!$name) {
                return null;
            }

            $normalized = Str::of($name)->lower()->trim()->value();
            if ($normalized === '') {
                return null;
            }

            $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
            $lookupKeys = array_values(array_filter(array_unique([$normalized, $compact])));

            foreach ($lookupKeys as $lookupKey) {
                if (array_key_exists($lookupKey, $materialNameCache)) {
                    $cached = $materialNameCache[$lookupKey];
                    return $cached instanceof Material ? $cached : null;
                }
            }

            $material = Material::query()
                ->with('inventory')
                ->where(function ($query) use ($normalized, $compact) {
                    $query->whereRaw('LOWER(material_name) = ?', [$normalized]);

                    if ($compact !== '' && $compact !== $normalized) {
                        $query->orWhereRaw("REPLACE(REPLACE(REPLACE(LOWER(material_name), ' ', ''), '-', ''), '_', '') = ?", [$compact]);
                    }
                })
                ->orderByDesc('updated_at')
                ->first();

            foreach ($lookupKeys as $lookupKey) {
                $materialNameCache[$lookupKey] = $material ?: false;
            }

            return $material ?: null;
        };

        $checkMaterial = function (Material $material, float $requiredQty) use (&$shortages) {
            $currentStock = $material->inventory?->stock_level ?? $material->stock_qty ?? 0;
            if ($currentStock < $requiredQty) {
                $shortages[] = [
                    'material_name' => $material->material_name,
                    'required' => $requiredQty,
                    'available' => $currentStock,
                    'shortage' => $requiredQty - $currentStock,
                ];
            }
        };

        // Check product materials
        if ($product) {
            $productMaterials = ProductMaterial::query()
                ->with(['material.inventory'])
                ->where('product_id', $product->id)
                ->whereNull('order_id')
                ->get();

            foreach ($productMaterials as $productMaterial) {
                $perUnitQty = (float) ($productMaterial->qty ?? 0);
                if ($perUnitQty <= 0) {
                    continue;
                }

                $linkedMaterial = $productMaterial->material;

                if (!$linkedMaterial && $productMaterial->material_id) {
                    $linkedMaterial = $materialCache[$productMaterial->material_id] ??=
                        Material::query()->with('inventory')->find($productMaterial->material_id);
                }

                if (!$linkedMaterial && $productMaterial->item) {
                    $linkedMaterial = $resolveMaterialByName($productMaterial->item);
                }

                if (!$linkedMaterial) {
                    continue;
                }

                $requiredQty = match ($productMaterial->quantity_mode) {
                    'per_order' => $perUnitQty,
                    default => $perUnitQty * $quantity,
                };

                if ($requiredQty > 0) {
                    $checkMaterial($linkedMaterial, $requiredQty);
                }
            }

            // Check paper stock if selected
            $paperStockId = $summary['paperStockId'] ?? null;
            if ($paperStockId) {
                $paperStock = ProductPaperStock::query()
                    ->with(['material.inventory'])
                    ->find($paperStockId);

                if ($paperStock) {
                    $paperMaterial = $paperStock->material;
                    if (!$paperMaterial) {
                        $fallbackNames = array_filter([
                            $paperStock->material_name ?? null,
                            $paperStock->name ?? null,
                        ], fn ($value) => is_string($value) && trim($value) !== '');

                        foreach ($fallbackNames as $candidateName) {
                            $paperMaterial = $resolveMaterialByName($candidateName);
                            if ($paperMaterial) {
                                break;
                            }
                        }
                    }

                    if ($paperMaterial) {
                        $checkMaterial($paperMaterial, $quantity); // Assuming 1 per unit
                    }
                }
            }

            // Check addons
            $addonIds = $summary['addonIds'] ?? [];
            if (!empty($addonIds)) {
                $addons = ProductSize::query()
                    ->with(['material.inventory'])
                    ->whereIn('id', $addonIds)
                    ->get();

                foreach ($addons as $addon) {
                    $material = $addon->material;
                    if (!$material && $addon->material_id) {
                        $material = $materialCache[$addon->material_id] ??=
                            Material::query()->with('inventory')->find($addon->material_id);
                    }

                    if (!$material) {
                        $material = $resolveMaterialByName($addon->name);
                    }

                    if ($material) {
                        $addonQuantity = 1; // Assuming 1 per addon, can be adjusted
                        $checkMaterial($material, $addonQuantity);
                    }
                }
            }
        }

        // Check envelope if present
        if (!empty($summary['envelope'])) {
            $envelopeData = $summary['envelope'];
            $envelopeProductId = $envelopeData['product_id'] ?? $envelopeData['id'] ?? null;
            if ($envelopeProductId) {
                $envelope = ProductEnvelope::query()
                    ->with(['material.inventory'])
                    ->where('product_id', $envelopeProductId)
                    ->first();

                $material = $envelope?->material;
                if (!$material && $envelope?->material_id) {
                    $material = $materialCache[$envelope->material_id] ??=
                        Material::query()->with('inventory')->find($envelope->material_id);
                }

                if (!$material) {
                    $candidateNames = array_filter([
                        $envelope?->envelope_material_name,
                        $envelopeData['material'] ?? null,
                    ], fn ($value) => is_string($value) && trim($value) !== '');

                    foreach ($candidateNames as $candidateName) {
                        $material = $resolveMaterialByName($candidateName);
                        if ($material) {
                            break;
                        }
                    }
                }

                if ($material) {
                    $envelopeQty = (int) ($envelopeData['qty'] ?? $envelopeData['quantity'] ?? 0);
                    $checkMaterial($material, $envelopeQty);
                }
            }
        }

        // Check giveaway if present
        if (!empty($summary['giveaway'])) {
            $giveawayData = $summary['giveaway'];
            $giveawayProductId = $giveawayData['product_id'] ?? $giveawayData['id'] ?? null;
            if ($giveawayProductId) {
                $giveawayProduct = Product::find($giveawayProductId);
                if ($giveawayProduct) {
                    $giveawayQty = (int) ($giveawayData['qty'] ?? $giveawayData['quantity'] ?? 0);

                    // Use fallback logic for giveaway
                    $fallbackNames = array_filter([
                        $giveawayProduct->name,
                        $giveawayData['name'] ?? null,
                        $giveawayData['material'] ?? null,
                    ], fn ($name) => is_string($name) && trim($name) !== '');

                    foreach ($fallbackNames as $candidateName) {
                        $material = $resolveMaterialByName($candidateName);
                        if ($material) {
                            $checkMaterial($material, $giveawayQty);
                            break;
                        }
                    }
                }
            }
        }

        return $shortages;
    }

    public function checkInkStock(Order $order): bool
    {
        $order->loadMissing([
            'items.product',
            'items.product.inkUsage',
        ]);

        $requiredTotals = [];

        foreach ($order->items as $item) {
            $quantity = max(0, (int) $item->quantity);
            if ($quantity === 0) {
                continue;
            }

            $averageUsage = (float) ($item->product?->inkUsage?->sum('average_usage_ml') ?? 0);
            $totalInk = round($averageUsage * $quantity, 4);
            if ($totalInk <= 0.0) {
                continue;
            }

            $distribution = $this->splitInkIntoComponents($totalInk);
            foreach ($distribution as $color => $amount) {
                if ($amount <= 0.0) {
                    continue;
                }

                $requiredTotals[$color] = ($requiredTotals[$color] ?? 0.0) + $amount;
            }
        }

        if (empty($requiredTotals)) {
            return true;
        }

        $requiredUnits = $this->distributeInkTotals($requiredTotals);

        foreach ($requiredUnits as $color => $units) {
            if ($units <= 0) {
                continue;
            }

            $ink = $this->resolveInkByColorKey($color);
            if (!$ink) {
                // Ink not configured; treat as non-blocking but surface via metadata later.
                continue;
            }

            $inventoryStock = optional($ink->inventory)->stock_level;

            $available = max(
                (int) ($inventoryStock ?? 0),
                (int) ($ink->stock_qty ?? 0),
                (int) ($ink->stock_qty_ml ?? 0)
            );

            if ($available <= 0) {
                return false;
            }

            if ($available < $units) {
                return false;
            }
        }

        return true;
    }

    protected function removeLineItem(Order $order, string $lineType): void
    {
        $order->items()->where('line_type', $lineType)->delete();
    }

    public function recalculateOrderTotals(Order $order): void
    {
        // Always reload items to ensure we have the latest quantities
        $order->load(['items.addons', 'items.paperStockSelection']);

        $invitationItem = $this->primaryInvitationItem($order);
        if (!$invitationItem) {
            return;
        }

        // Check if base price should be omitted
        $metadata = $order->metadata ?? [];
        $omitBasePrice = Arr::get($metadata, 'final_step.metadata.omit_base_price', false);

        if ($omitBasePrice) {
            // When base price is omitted, use the payment_amount as the total
            $paymentAmount = Arr::get($metadata, 'final_step.metadata.payment_amount', 0);
            $total = round((float) $paymentAmount, 2);
            $subtotal = $total; // subtotal equals total in this case
            $tax = 0.0;
            $shipping = 0.0;
        } else {
            // Include base price of the invitation in the subtotal so the order total reflects
            // the product's unit price plus any extras (paper, addons, etc.). Previously this
            // was set to 0 which caused orders with only the base product to end up with a
            // zero total amount.
            $baseSubtotal = round((float) ($invitationItem->unit_price ?? 0) * (int) $invitationItem->quantity, 2);

            // paperStockSelection->price is stored as a per-unit price; multiply by quantity
            $paperPerUnit = (float) ($invitationItem->paperStockSelection?->price ?? 0);
            $paperTotal = round($paperPerUnit * $invitationItem->quantity, 2);

            $addonsTotal = (float) ($invitationItem->addons?->sum(fn($addon) => $addon->addon_price * $invitationItem->quantity) ?? 0);

            $inkTotal = 0.0; // TODO: calculate ink costs if applicable

            $extrasTotal = round($paperTotal + $addonsTotal + $inkTotal, 2);

            $additionalLineItemsTotal = $order->items
                ->reject(fn ($item) => $item->is($invitationItem))
                ->sum(function (OrderItem $item) {
                    $subtotal = $item->subtotal;

                    if ($subtotal === null) {
                        $subtotal = $item->unit_price * $item->quantity;
                    }

                    return (float) $subtotal;
                });

            // Include baseSubtotal so subtotal reflects base product + extras + other line items
            $subtotal = round($baseSubtotal + $extrasTotal + $additionalLineItemsTotal, 2);
            $tax = 0.0;
            $shipping = 0.0;
            $total = round($subtotal, 2);
        }

        $order->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'shipping_fee' => $shipping,
            'total_amount' => $total,
        ]);
    }

    public function buildSummary(Order $order): array
    {
        $order->loadMissing(['items.addons', 'items.paperStockSelection', 'items.product']);

        $invitationItem = $this->primaryInvitationItem($order);
        if (!$invitationItem) {
            return [];
        }

        $summary = [
            'productId' => $invitationItem->product_id,
            'productName' => $invitationItem->product_name ?? $invitationItem->product?->name,
            'quantity' => $invitationItem->quantity,
            'unitPrice' => $invitationItem->unit_price,
            'paperStockId' => $invitationItem->paperStockSelection?->paper_stock_id,
            'paperStockName' => $invitationItem->paperStockSelection?->paper_stock_name,
            'paperStockPrice' => $invitationItem->paperStockSelection?->price,
            'addonIds' => $invitationItem->addons?->pluck('size_id')->toArray() ?? [],
            'metadata' => $order->metadata ?? [],
            'subtotalAmount' => $order->subtotal_amount,
            'totalAmount' => $order->total_amount,
            'taxAmount' => $order->tax_amount,
            'shippingFee' => 0.0,
        ];

        // Extract envelopes from metadata
        $metadata = $order->metadata ?? [];
        $summary['envelopes'] = [];
        if (!empty($metadata['envelopes']) && is_array($metadata['envelopes'])) {
            $summary['envelopes'] = $metadata['envelopes'];
        } elseif (!empty($metadata['envelope']) && is_array($metadata['envelope'])) {
            $summary['envelopes'] = [$metadata['envelope']];
        }
        $summary['envelope'] = $summary['envelopes'][0] ?? null; // legacy consumers

        // Extract giveaways from metadata (support both plural and legacy single formats)
        $summary['giveaways'] = [];
        if (!empty($metadata['giveaways']) && is_array($metadata['giveaways'])) {
            $summary['giveaways'] = $metadata['giveaways'];
        } elseif (!empty($metadata['giveaway']) && is_array($metadata['giveaway'])) {
            $summary['giveaways'] = [$metadata['giveaway']];
        }
        $summary['giveaway'] = $summary['giveaways'][0] ?? null; // legacy consumer compatibility

        return $summary;
    }

    public function logActivity(Order $order, string $activity, array $data = []): void
    {
        $user = Auth::user();
        $order->activities()->create([
            'activity_type' => $activity,
            'description' => json_encode($data),
            'user_id' => $user?->user_id,
            'user_name' => $user?->name ?? $user?->email,
            'user_role' => $user?->role ?? 'customer',
            'created_at' => now(),
        ]);
    }

    protected function determineInkUsageDistribution(Order $order): array
    {
        $order->loadMissing([
            'items.product',
            'items.product.inkUsage',
            'items.inkUsage',
        ]);

        $totalsMl = array_fill_keys(array_keys(self::CMYK_RATIOS), 0.0);
        $totalsUnits = array_fill_keys(array_keys(self::CMYK_RATIOS), 0);
        $perItemSnapshots = [];

        foreach ($order->items as $item) {
            $item->inkUsage()->delete();

            $quantity = max(0, (int) $item->quantity);
            if ($quantity === 0) {
                continue;
            }

            $averageUsage = (float) ($item->product?->inkUsage?->sum('average_usage_ml') ?? 0);
            $totalInk = round($averageUsage * $quantity, 4);
            if ($totalInk <= 0.0) {
                continue;
            }

            $distributionMl = $this->splitInkIntoComponents($totalInk);
            $distributionUnits = $this->distributeInkTotals($distributionMl);

            $item->inkUsage()->create([
                'average_usage_ml' => round($averageUsage, 4),
                'total_ink_ml' => $totalInk,
            ]);

            foreach ($distributionMl as $color => $amount) {
                $totalsMl[$color] = round(($totalsMl[$color] ?? 0.0) + $amount, 4);
            }

            foreach ($distributionUnits as $color => $units) {
                $totalsUnits[$color] = ($totalsUnits[$color] ?? 0) + (int) $units;
            }

            $perItemSnapshots[] = [
                'order_item_id' => $item->id,
                'line_type' => $item->line_type,
                'product_id' => $item->product_id,
                'quantity' => $quantity,
                'average_usage_ml' => round($averageUsage, 4),
                'total_ink_ml' => $totalInk,
                'distribution_ml' => $distributionMl,
                'distribution_units' => $distributionUnits,
            ];
        }

        $totalsMl = array_filter($totalsMl, static fn ($value) => $value > 0.0);
        $totalsUnits = array_filter($totalsUnits, static fn ($value) => $value > 0);

        return [
            'totals_ml' => $totalsMl,
            'totals_units' => $totalsUnits,
            'items' => $perItemSnapshots,
        ];
    }

    protected function splitInkIntoComponents(float $totalInk): array
    {
        $totalInk = max(0.0, round($totalInk, 4));

        if ($totalInk <= 0.0) {
            return array_fill_keys(array_keys(self::CMYK_RATIOS), 0.0);
        }

        $ratios = self::CMYK_RATIOS;
        $colors = array_keys($ratios);
        $lastColor = end($colors) ?: null;
        $components = [];
        $allocated = 0.0;

        foreach ($ratios as $color => $ratio) {
            if ($lastColor !== null && $color === $lastColor) {
                $amount = round($totalInk - $allocated, 4);
            } else {
                $amount = round($totalInk * $ratio, 4);
                $allocated = round($allocated + $amount, 4);
            }

            $components[$color] = max(0.0, $amount);
        }

        $delta = round($totalInk - array_sum($components), 4);
        if ($delta !== 0.0 && $lastColor !== null) {
            $components[$lastColor] = max(0.0, round($components[$lastColor] + $delta, 4));
        }

        return $components;
    }

    protected function distributeInkTotals(array $totals): array
    {
        $filtered = [];
        foreach ($totals as $color => $value) {
            $key = $this->normalizeInkColorKey((string) $color);
            if ($key === '') {
                continue;
            }

            $filtered[$key] = round((float) $value, 4);
        }

        if (empty($filtered)) {
            return [];
        }

        $positive = array_filter($filtered, static fn ($value) => $value > 0);
        if (empty($positive)) {
            return array_fill_keys(array_keys($filtered), 0);
        }

        $allocation = array_fill_keys(array_keys($filtered), 0);
        $fractions = [];

        foreach ($positive as $color => $amount) {
            $base = (int) floor($amount);
            $allocation[$color] = $base;
            $fractions[$color] = $amount - $base;
        }

        $targetTotal = (int) round(array_sum($filtered));

        if ($targetTotal <= 0) {
            return array_fill_keys(array_keys($filtered), 0);
        }

        $allocatedBase = array_sum($allocation);
        $remainder = $targetTotal - $allocatedBase;

        if ($remainder > 0 && !empty($fractions)) {
            arsort($fractions);
            $keys = array_keys($fractions);
            $index = 0;
            $count = count($keys);

            while ($remainder > 0) {
                $color = $keys[$index % $count];
                $allocation[$color] += 1;
                $remainder--;
                $index++;
            }
        } elseif ($remainder < 0 && !empty($fractions)) {
            asort($fractions);
            $keys = array_keys($fractions);
            $index = 0;
            $count = count($keys);

            while ($remainder < 0) {
                $color = $keys[$index % $count];
                if ($allocation[$color] > 0) {
                    $allocation[$color] -= 1;
                    $remainder++;
                }

                $index++;
                if ($index > $count * 2) {
                    break;
                }
            }
        }

        return array_map('intval', $allocation);
    }

    protected function extractInkUsageState($metadata): array
    {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($metadata)) {
            $metadata = [];
        }

        $inkUsage = (array) ($metadata['ink_usage'] ?? []);

        $previousApplied = [];
        foreach ((array) ($inkUsage['applied'] ?? []) as $color => $amount) {
            $key = $this->normalizeInkColorKey((string) $color);
            if ($key === '') {
                continue;
            }

            $previousApplied[$key] = (int) round((float) $amount);
        }

        $previousRequired = [];
        foreach ((array) ($inkUsage['required'] ?? []) as $color => $amount) {
            $key = $this->normalizeInkColorKey((string) $color);
            if ($key === '') {
                continue;
            }

            $previousRequired[$key] = (int) round((float) $amount);
        }

        $previousShortages = [];
        foreach ((array) ($inkUsage['shortages'] ?? []) as $color => $amount) {
            $key = $this->normalizeInkColorKey((string) $color);
            if ($key === '') {
                continue;
            }

            $previousShortages[$key] = (int) round((float) $amount);
        }

        return [$metadata, $previousApplied, $previousRequired, $previousShortages];
    }

    protected function normalizeInkColorKey(string $color): string
    {
        $normalized = Str::of($color)
            ->lower()
            ->replace([' ink', '-ink', '_ink'], '')
            ->replace(['-', '_', ' '], '')
            ->trim()
            ->value();

        if (!is_string($normalized) || $normalized === '') {
            return '';
        }

        $sanitized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return is_string($sanitized) ? $sanitized : '';
    }

    protected function resolveInkByColorKey(string $colorKey): ?Ink
    {
        $normalized = $this->normalizeInkColorKey($colorKey);
        if ($normalized === '') {
            return null;
        }

        $headline = Str::headline($normalized);
        $candidates = array_values(array_unique(array_filter([
            $normalized,
            $normalized . ' ink',
            Str::of($headline)->lower()->value(),
            Str::of($headline . ' Ink')->lower()->value(),
        ])));

        if (empty($candidates)) {
            return null;
        }

        $orderByColumn = Schema::hasColumn('inks', 'updated_at') ? 'updated_at' : 'id';

        $exactMatch = Ink::query()
            ->with('inventory')
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $index => $candidate) {
                    if ($index === 0) {
                        $query->whereRaw('LOWER(ink_color) = ?', [$candidate]);
                    } else {
                        $query->orWhereRaw('LOWER(ink_color) = ?', [$candidate]);
                    }
                }
            })
            ->orderByDesc($orderByColumn)
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        return Ink::query()
            ->with('inventory')
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $index => $candidate) {
                    $pattern = '%' . $candidate . '%';
                    if ($index === 0) {
                        $query->whereRaw('LOWER(ink_color) LIKE ?', [$pattern]);
                    } else {
                        $query->orWhereRaw('LOWER(ink_color) LIKE ?', [$pattern]);
                    }
                }
            })
            ->orderByDesc($orderByColumn)
            ->first();
    }

    // Reconcile ink stock against required totals while tracking applied amounts in metadata.
    protected function syncOrderInkUsage(Order $order, array $requiredTotalsMl, array $itemSnapshots = [], ?array $requiredTotalsUnits = null): void
    {
        $aggregatedMl = [];
        foreach ($requiredTotalsMl as $color => $amount) {
            $key = $this->normalizeInkColorKey((string) $color);
            if ($key === '') {
                continue;
            }

            $aggregatedMl[$key] = round((float) $amount, 4);
        }

        if ($requiredTotalsUnits === null) {
            $requiredUnits = $this->distributeInkTotals($aggregatedMl);
        } else {
            $requiredUnits = [];
            foreach ($requiredTotalsUnits as $color => $amount) {
                $key = $this->normalizeInkColorKey((string) $color);
                if ($key === '') {
                    continue;
                }

                $requiredUnits[$key] = max(0, (int) $amount);
            }
        }

        foreach ($aggregatedMl as $color => $amount) {
            if (!array_key_exists($color, $requiredUnits)) {
                $requiredUnits[$color] = 0;
            }
        }

        DB::transaction(function () use ($order, $aggregatedMl, $requiredUnits, $itemSnapshots) {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();
            if (!$lockedOrder) {
                return;
            }

            [$metadata, $previousApplied, $previousRequired, $previousShortages] = $this->extractInkUsageState($lockedOrder->metadata);

            $requiredUnitsLocal = $requiredUnits;
            foreach ($previousApplied as $key => $_) {
                if (!array_key_exists($key, $requiredUnitsLocal)) {
                    $requiredUnitsLocal[$key] = 0;
                }
            }

            $allKeys = array_values(array_unique(array_merge(
                array_keys($requiredUnitsLocal),
                array_keys($previousApplied)
            )));

            if (empty($allKeys) && empty($previousApplied)) {
                if (isset($metadata['ink_usage'])) {
                    unset($metadata['ink_usage']);
                    $lockedOrder->update(['metadata' => $metadata]);
                    $order->setAttribute('metadata', $metadata);
                }
                return;
            }

            $shortages = [];
            $updatedApplied = $previousApplied;

            foreach ($allKeys as $key) {
                $requiredQty = (int) ($requiredUnitsLocal[$key] ?? 0);
                $previousQty = (int) ($previousApplied[$key] ?? 0);
                $diffQty = $requiredQty - $previousQty;

                if ($diffQty === 0) {
                    continue;
                }

                $ink = $this->resolveInkByColorKey($key);
                if (!$ink) {
                    continue;
                }

                $adjustment = $this->adjustInkStock($ink, $diffQty);
                $appliedDelta = (int) round($adjustment['applied'] ?? $diffQty);

                if ($diffQty > 0 && $appliedDelta < $diffQty) {
                    $shortages[$key] = ($shortages[$key] ?? 0) + ($diffQty - $appliedDelta);
                }

                $updatedApplied[$key] = max(0, $previousQty + $appliedDelta);
            }

            $filteredRequired = array_filter($requiredUnitsLocal, static fn ($value) => (int) $value !== 0);
            $filteredApplied = array_filter($updatedApplied, static fn ($value) => (int) $value !== 0);

            if (empty($filteredRequired) && empty($filteredApplied)) {
                if (isset($metadata['ink_usage'])) {
                    unset($metadata['ink_usage']);
                    $lockedOrder->update(['metadata' => $metadata]);
                    $order->setAttribute('metadata', $metadata);
                }
                return;
            }

            $rawTotals = array_filter($aggregatedMl, static fn ($value) => $value > 0.0);
            $inkMetadata = [
                'required' => $filteredRequired,
                'applied' => $filteredApplied,
                'raw_required_ml' => array_map(static fn ($value) => round((float) $value, 4), $rawTotals),
                'total_required_ml' => round(array_sum($aggregatedMl), 4),
                'items' => array_values($itemSnapshots),
                'shortages' => $shortages,
                'updated_at' => now()->toIso8601String(),
            ];

            $inkMetadata = array_filter($inkMetadata, function ($value) {
                if (is_array($value)) {
                    return !empty($value);
                }

                return $value !== null;
            });

            $existingMetadata = $metadata['ink_usage'] ?? null;
            if ($existingMetadata === $inkMetadata) {
                $order->setAttribute('metadata', $metadata);
                return;
            }

            $metadata['ink_usage'] = $inkMetadata;
            $lockedOrder->update(['metadata' => $metadata]);
            $order->setAttribute('metadata', $metadata);
        });
    }

    public function deductInkStock(Order $order): void
    {
        $context = $this->determineInkUsageDistribution($order);
        $this->syncOrderInkUsage(
            $order,
            $context['totals_ml'] ?? [],
            $context['items'] ?? [],
            $context['totals_units'] ?? []
        );
    }

    protected function adjustInkStock(Ink $ink, float $delta): array
    {
        $inkRecord = Ink::query()
            ->whereKey($ink->getKey())
            ->lockForUpdate()
            ->with(['inventory' => function ($query) {
                $query->lockForUpdate();
            }])
            ->first();

        if ($inkRecord) {
            $ink = $inkRecord;
        } else {
            $ink->loadMissing('inventory');
        }

        $inventory = $ink->inventory;
        $currentInventory = $inventory?->stock_level ?? 0;
        $currentInkStock = $ink->stock_qty ?? $currentInventory;

        $newInventoryLevel = $inventory ? $currentInventory - $delta : null;
        $newInkStock = $currentInkStock - $delta;

        $shortage = 0.0;

        if ($newInventoryLevel !== null && $newInventoryLevel < 0) {
            $shortage = max($shortage, abs($newInventoryLevel));
            $newInventoryLevel = 0;
        }

        if ($newInkStock < 0) {
            $shortage = max($shortage, abs($newInkStock));
            $newInkStock = 0;
        }

        if ($inventory && $newInventoryLevel !== null) {
            $inventory->stock_level = (int) round($newInventoryLevel);
            $inventory->save();
        }

        $ink->stock_qty = (int) round($newInkStock);
        $ink->save();

        $applied = $currentInkStock - $newInkStock;

        $movementQuantity = (int) round($applied);
        if ($movementQuantity !== 0) {
            $movementType = $movementQuantity > 0 ? 'usage' : 'restock';

            InkStockMovement::create([
                'ink_id' => $ink->getKey(),
                'movement_type' => $movementType,
                'quantity' => abs($movementQuantity),
                'user_id' => Auth::id(),
            ]);
        }

        return [
            'applied' => $applied,
            'shortage' => $shortage,
            'remaining_stock' => [
                'inventory' => $inventory?->stock_level,
                'ink' => $ink->stock_qty,
            ],
        ];
    }

    public function restoreInkStock(Order $order): void
    {
        $metadata = $order->metadata;
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($metadata)) {
            $metadata = [];
        }

        $appliedUsage = data_get($metadata, 'ink_usage.applied', []);
        if (!empty($appliedUsage)) {
            $this->syncOrderInkUsage($order, []);
            return;
        }

        $context = $this->determineInkUsageDistribution($order);
        $requiredUnits = $context['totals_units'] ?? [];

        if (empty($requiredUnits)) {
            $rawTotals = $context['totals_ml'] ?? [];
            $requiredUnits = $this->distributeInkTotals($rawTotals);
        }

        if (empty($requiredUnits)) {
            return;
        }

        foreach ($requiredUnits as $color => $units) {
            if ($units <= 0) {
                continue;
            }

            $ink = $this->resolveInkByColorKey($color);
            if ($ink) {
                $this->adjustInkStock($ink, -1 * $units);
            }
        }

        if (!empty($metadata) && isset($metadata['ink_usage'])) {
            unset($metadata['ink_usage']);
            $order->update(['metadata' => $metadata]);
        }
    }

    private function decodeDataUrl(string $dataUrl): string
    {
        if (!Str::startsWith($dataUrl, 'data:')) {
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

    private function persistDataUrl(string $dataUrl, string $directory, string $extension, ?string $existingPath, string $field): string
    {
        if (trim((string) $dataUrl) === '') {
            if ($existingPath) {
                return $existingPath;
            }
            throw new \InvalidArgumentException('Missing data payload.');
        }

        try {
            $contents = $this->decodeDataUrl($dataUrl);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Invalid data payload provided.');
        }

        $normalizedExistingPath = null;
        if ($existingPath) {
            $normalizedExistingPath = ltrim(str_replace('\\', '/', (string) $existingPath), '/');
            $normalizedExistingPath = preg_replace('#^/?storage/#i', '', $normalizedExistingPath) ?? $normalizedExistingPath;
        }

        if ($normalizedExistingPath && Storage::disk('public')->exists($normalizedExistingPath)) {
            Storage::disk('public')->delete($normalizedExistingPath);
        }

        $directory = trim($directory, '/');
        if ($directory !== '') {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = ($directory ? $directory . '/' : '') . 'design_' . Str::uuid() . '.' . $extension;

        $stored = Storage::disk('public')->put($filename, $contents);

        if (!$stored) {
            throw new \InvalidArgumentException('Failed to persist exported asset on disk.');
        }

        return $filename;
    }

    public function calculateTotalsFromSummary(array $summary): array
    {
        $product = $this->resolveProduct(null, $summary['productId'] ?? null);
        if ($product) {
            $product->loadMissing(['paperStocks', 'addons']);
        }

        // Determine quantity and unit price defensively so we can compute an
        // invitation total even when the product lookup fails (e.g., session-only
        // summaries or in test environments).
        $quantity = max(1, (int) ($summary['quantity'] ?? 1));
        $unitPrice = (float) ($summary['unitPrice'] ?? ($product ? $this->unitPriceFor($product) : 0));
        $invitationTotal = round($unitPrice * $quantity, 2);

        // If we couldn't resolve the product, return totals based solely on
        // the provided unit/quantity (no paper/addons/envelope/giveaway data).
        if (!$product) {
            $subtotal = $invitationTotal;
            $total = round($subtotal + 0.0, 2);

            return [
                'invitationTotal' => $invitationTotal,
                'subtotalAmount' => $subtotal,
                'totalAmount' => $total,
                'taxAmount' => 0.0,
                'shippingFee' => static::DEFAULT_SHIPPING_FEE,
                'extras' => [
                    'paper' => 0.0,
                    'addons' => 0.0,
                    'envelope' => 0.0,
                    'giveaway' => 0.0,
                ],
            ];
        }

        // Include base price of the invitation (unit * quantity). Previously this was
        // omitted which caused server-side totals to undercount the order and made
        // outstanding balance 0 when only the invitation existed.
        $invitationTotal = round($unitPrice * $quantity, 2);
        $baseSubtotal = $invitationTotal;

        // Calculate paper stock total
        $paperTotal = 0.0;
        $paperStockId = $summary['paperStockId'] ?? null;
        if ($paperStockId) {
            $paperStock = $product->paperStocks()->find($paperStockId);
            if ($paperStock && $paperStock->price) {
                $paperTotal = round((float) $paperStock->price * $quantity, 2);
            }
        }

        // Calculate addons total
        $addonsTotal = 0.0;
        $addonIds = $summary['addonIds'] ?? [];
        if (!empty($addonIds)) {
            $addons = $product->addons()->whereIn('id', $addonIds)->get();
            foreach ($addons as $addon) {
                $addonsTotal += round((float) $addon->price * $quantity, 2);
            }
        }

        // Calculate envelope total
        $envelopeTotal = 0.0;
        if (!empty($summary['envelope']) && is_array($summary['envelope'])) {
            $envelopeData = $summary['envelope'];
            $envelopeQty = max(1, (int) ($envelopeData['qty'] ?? $envelopeData['quantity'] ?? 1));
            $envelopePrice = (float) ($envelopeData['price'] ?? $envelopeData['unit_price'] ?? 0);
            $envelopeTotal = round($envelopePrice * $envelopeQty, 2);
        }

        // Calculate giveaway total
        $giveawayTotal = 0.0;
        $giveaways = $summary['giveaways'] ?? [];
        if (empty($giveaways) && !empty($summary['giveaway'])) {
            $giveaways = [$summary['giveaway']];
        }
        foreach ($giveaways as $giveaway) {
            if (!empty($giveaway) && is_array($giveaway)) {
                $giveawayQty = max(1, (int) ($giveaway['qty'] ?? $giveaway['quantity'] ?? 1));
                $giveawayPrice = (float) ($giveaway['price'] ?? $giveaway['unit_price'] ?? 0);
                $giveawayTotal += round($giveawayPrice * $giveawayQty, 2);
            }
        }

        // Include invitation base into subtotal so session/server totals match
        // what the client expects (unit * qty + extras).
        $subtotal = round($baseSubtotal + $paperTotal + $addonsTotal + $envelopeTotal + $giveawayTotal, 2);
        $tax = 0.0;
        $shipping = 0.0; // Always 0 for shipping
        $total = round($subtotal + $shipping, 2);

        return [
            // Provide an explicit invitationTotal to make it easy for consumers
            // to show per-item totals without re-deriving from unit/qty.
            'invitationTotal' => $invitationTotal,
            'subtotalAmount' => $subtotal,
            'totalAmount' => $total,
            'taxAmount' => $tax,
            'shippingFee' => 0.0,
            'extras' => [
                'paper' => $paperTotal,
                'addons' => $addonsTotal,
                'envelope' => $envelopeTotal,
                'giveaway' => $giveawayTotal,
            ],
        ];
    }
}
