<?php

namespace App\Support\Admin;

use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrderSummaryPresenter
{
    public static function make(?Order $order): ?array
    {
        if (!$order) {
            return null;
        }

        $order->loadMissing([
            'customerOrder.customer',
            'customer',
            'items.product',
            'items.bulkSelections',
            'items.paperStockSelection',
            'items.addons',
            'items.colors',
            'rating',
        ]);

        $customerOrder = $order->customerOrder;
        $customer = $order->customer ?? $customerOrder?->customer;
        $summary = $order->summary_snapshot ?? [];

        $customerName = static::resolveCustomerName($customerOrder, $customer);
        $customerEmail = static::resolveCustomerEmail($customerOrder, $customer);
        $customerPhone = static::resolveCustomerPhone($customerOrder, $customer);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->order_date ?? $order->created_at,
            'updated_at' => $order->updated_at,
            'status' => Str::lower((string) $order->status ?: 'pending'),
            'payment_status' => Str::lower((string) $order->payment_status ?: 'pending'),
            'fulfillment_status' => Str::lower((string) $order->status ?: 'processing'),
            'subtotal' => static::toFloat($order->subtotal_amount),
            'discount_total' => static::toFloat(Arr::get($summary, 'totals.discount', 0)),
            'shipping_fee' => static::toFloat($order->shipping_fee),
            'tax_total' => static::toFloat($order->tax_amount),
            'grand_total' => static::toFloat($order->total_amount),
            'production_status' => Arr::get($order->metadata, 'production.status', Str::lower((string) $order->status ?: 'queue')),
            'delivery_status' => Arr::get($order->metadata, 'delivery.status'),
            'estimated_ship_date' => Arr::get($order->metadata, 'delivery.estimated_ship_date', $order->date_needed),
            'shipping_option' => $order->shipping_option,
            'payment_method' => $order->payment_method,
            'currency' => Arr::get($summary, 'currency', 'PHP'),
            'customer' => [
                'id' => $customer?->customer_id,
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
                'company' => $customerOrder?->company,
                'tags' => static::resolveCustomerTags($customerOrder, $customer, $summary),
            ],
            'shipping_address' => static::formatAddress($customerOrder, Arr::get($summary, 'shipping.address')),
            'billing_address' => static::formatAddress($customerOrder, Arr::get($summary, 'billing.address')),
            'items' => static::transformItems($order->items, $summary),
            'timeline' => static::buildTimeline($order),
            'admin_actions' => Arr::get($summary, 'admin_actions', []),
            'rating' => $order->rating ? [
                'rating' => (int) $order->rating->rating,
                'comment' => $order->rating->review,
                'photos' => $order->rating->photos ?? [],
                'submitted_at' => $order->rating->created_at,
            ] : null,
        ];
    }

    protected static function resolveCustomerName(?CustomerOrder $customerOrder, $customer): string
    {
        $nameFromOrder = trim((string) ($customerOrder?->name ?? ''));
        if ($nameFromOrder !== '') {
            return $nameFromOrder;
        }

        $fullName = trim(collect([
            $customer?->first_name ?? $customer?->name,
            $customer?->last_name,
        ])->filter()->implode(' '));

        return $fullName !== '' ? $fullName : 'Guest customer';
    }

    protected static function resolveCustomerEmail(?CustomerOrder $customerOrder, $customer): ?string
    {
        return $customerOrder?->email
            ?? $customer?->email
            ?? $customer?->user?->email
            ?? null;
    }

    protected static function resolveCustomerPhone(?CustomerOrder $customerOrder, $customer): ?string
    {
        return $customerOrder?->phone
            ?? $customer?->phone
            ?? $customer?->contact_number
            ?? null;
    }

    protected static function resolveCustomerTags(?CustomerOrder $customerOrder, $customer, array $summary): array
    {
        $tags = collect();

        $tags = $tags
            ->merge(Arr::get($summary, 'customer.tags', []))
            ->merge(Arr::get($customerOrder, 'tags', []))
            ->merge(Arr::wrap($customer?->segment ?? null));

        return $tags->filter()->unique()->values()->all();
    }

    protected static function formatAddress(?CustomerOrder $customerOrder, $fallback = null): ?string
    {
        if ($fallback && is_string($fallback)) {
            return $fallback;
        }

        if (!$customerOrder) {
            return null;
        }

        $lines = collect([
            $customerOrder->address,
            trim(collect([$customerOrder->city, $customerOrder->province])->filter()->implode(', ')),
            $customerOrder->postal_code,
        ])->filter()->all();

        if (empty($lines)) {
            return null;
        }

        return implode('\n', $lines);
    }

    protected static function transformItems(Collection $items, array $summary): array
    {
        return $items->map(function (OrderItem $item) use ($summary) {
            $metadata = $item->design_metadata ?? [];
            $options = Arr::get($metadata, 'options', []);

            if (empty($options)) {
                $options = static::deriveOptionsFromRelations($item);
            }

            $previewImages = Arr::get($metadata, 'preview_images')
                ?? Arr::get($metadata, 'images')
                ?? Arr::get($summary, 'preview_images');

            return [
                'id' => $item->id,
                'name' => $item->product_name ?? $item->product?->name ?? 'Custom product',
                'sku' => $item->product?->sku,
                'product_type' => $item->product?->product_type,
                'line_type' => $item->line_type,
                'quantity' => $item->quantity,
                'unit_price' => static::toFloat($item->unit_price),
                'total' => static::toFloat($item->subtotal ?: $item->unit_price * $item->quantity),
                'options' => $options,
                'breakdown' => static::buildItemBreakdown($item),
                'preview_images' => Arr::wrap($previewImages),
            ];
        })->values()->all();
    }

    protected static function buildItemBreakdown(OrderItem $item): array
    {
        $rows = [];

        $paper = $item->paperStockSelection;
        if ($paper) {
            $paperName = $paper->paperStock?->name ?? $paper->paper_stock_name ?? 'Paper stock';
            $paperPrice = static::toFloat($paper->price);

            $rows[] = [
                'label' => 'Paper stock: ' . $paperName,
                'quantity' => 1,
                'unit_price' => $paperPrice,
                'total' => $paperPrice,
                'type' => 'paper_stock',
            ];
        }

        if ($item->addons->isNotEmpty()) {
            foreach ($item->addons as $addon) {
                $addonName = $addon->productAddon?->name ?? $addon->addon_name ?? 'Add-on';
                $addonType = $addon->addon_type ? Str::headline(str_replace('_', ' ', $addon->addon_type)) : null;
                $addonPrice = static::toFloat($addon->addon_price);

                $label = $addonType
                    ? sprintf('%s (%s)', $addonName, $addonType)
                    : $addonName;

                $rows[] = [
                    'label' => $label,
                    'quantity' => 1,
                    'unit_price' => $addonPrice,
                    'total' => $addonPrice,
                    'type' => 'addon',
                ];
            }
        }

        return $rows;
    }

    protected static function deriveOptionsFromRelations(OrderItem $item): array
    {
        $options = [];

        if ($item->bulkSelections->isNotEmpty()) {
            $bulk = $item->bulkSelections->first();
            if ($bulk) {
                $options['quantity_set'] = $bulk->qty_selected;
                if ($bulk->price_per_unit) {
                    $options['price_per_unit'] = static::toFloat($bulk->price_per_unit);
                }
            }
        }

        if ($item->paperStockSelection) {
            $paper = $item->paperStockSelection;
            $options['paper_stock'] = $paper->paperStock?->name ?? $paper->paper_stock_name;
            if ($paper->price) {
                $options['paper_stock_price'] = static::toFloat($paper->price);
            }
        }

        if ($item->addons->isNotEmpty()) {
            $options['addons'] = $item->addons->map(function ($addon) {
                return [
                    'name' => $addon->productAddon?->name ?? $addon->addon_name,
                    'type' => $addon->addon_type,
                    'price' => static::toFloat($addon->addon_price),
                ];
            })->toArray();
        }

        if ($item->colors->isNotEmpty()) {
            $options['colors'] = $item->colors->map(function ($color) {
                return [
                    'name' => $color->productColor?->name ?? $color->color_name,
                    'code' => $color->color_code,
                ];
            })->toArray();
        }

        return $options;
    }

    protected static function buildTimeline(Order $order): array
    {
        $events = collect();

        $createdAt = $order->created_at ?? $order->order_date;
        if ($createdAt) {
            $events->push([
                'label' => 'Order placed',
                'state' => 'success',
                'timestamp' => $createdAt,
                'author' => $order->customerOrder?->name,
            ]);
        }

        $paymentStatus = Str::lower((string) $order->payment_status ?: 'pending');
        if ($paymentStatus === 'paid' && $order->updated_at) {
            $events->push([
                'label' => 'Payment received',
                'state' => 'paid',
                'timestamp' => $order->updated_at,
            ]);
        }

        $status = Str::lower((string) $order->status ?: 'pending');
        if (in_array($status, ['in_production', 'completed', 'fulfilled', 'shipped'], true)) {
            $events->push([
                'label' => 'Production update',
                'state' => $status,
                'timestamp' => $order->updated_at,
                'note' => Str::headline($status),
            ]);
        }

        $metadataEvents = collect(Arr::get($order->metadata, 'timeline', []))
            ->map(function ($entry) {
                $timestamp = Arr::get($entry, 'timestamp');
                if ($timestamp && ! $timestamp instanceof Carbon) {
                    try {
                        $timestamp = Carbon::parse($timestamp);
                    } catch (\Throwable $e) {
                        $timestamp = null;
                    }
                }

                return [
                    'label' => Arr::get($entry, 'label', 'Activity'),
                    'state' => Str::lower(Arr::get($entry, 'state', 'default')),
                    'author' => Arr::get($entry, 'author'),
                    'note' => Arr::get($entry, 'note'),
                    'timestamp' => $timestamp,
                ];
            });

        $events = $events->merge($metadataEvents)->filter();

        return $events
            ->sortByDesc(function ($event) {
                $timestamp = Arr::get($event, 'timestamp');
                if ($timestamp instanceof Carbon) {
                    return $timestamp->timestamp;
                }

                if (is_string($timestamp)) {
                    try {
                        return Carbon::parse($timestamp)->timestamp;
                    } catch (\Throwable $e) {
                        return 0;
                    }
                }

                return 0;
            })
            ->values()
            ->map(function ($event) {
                $timestamp = Arr::get($event, 'timestamp');

                if ($timestamp instanceof Carbon) {
                    $event['timestamp'] = $timestamp->toIso8601String();
                } elseif ($timestamp instanceof \DateTimeInterface) {
                    $event['timestamp'] = Carbon::instance($timestamp)->toIso8601String();
                }

                return $event;
            })
            ->all();
    }

    protected static function toFloat($value): float
    {
        return (float) ($value ?? 0);
    }
}
