<?php

namespace Tests\Feature\Customer;

use App\Models\CustomerOrder;
use App\Models\Ink;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Services\OrderFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderInkUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_deduct_ink_stock_updates_inventory_and_metadata(): void
    {
        [$cyan, $magenta, $yellow, $black] = $this->seedInks(200);

        $product = Product::create([
            'name' => 'Elegant Invite',
            'event_type' => 'Wedding',
            'product_type' => 'Invitation',
            'base_price' => 50,
        ]);

        ProductColor::create([
            'product_id' => $product->id,
            'average_usage_ml' => 2.5,
        ]);

        $customerOrder = CustomerOrder::create([
            'name' => 'Ink Test Customer',
            'email' => 'ink@example.com',
        ]);

        $order = Order::create([
            'customer_order_id' => $customerOrder->id,
            'order_number' => 'INV-' . Str::upper(Str::random(6)),
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 0,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 40,
            'unit_price' => 10,
            'subtotal' => 400,
            'line_type' => OrderItem::LINE_TYPE_INVITATION,
        ]);

        /** @var OrderFlowService $service */
        $service = app(OrderFlowService::class);
        $service->deductInkStock($order->fresh('items.product.inkUsage'));

        foreach ([$cyan, $magenta, $yellow, $black] as $ink) {
            $ink->refresh()->load('inventory');
        }

        $this->assertSame(170, $cyan->stock_qty);
        $this->assertSame(170, $cyan->inventory->stock_level);
        $this->assertSame(170, $magenta->stock_qty);
        $this->assertSame(170, $magenta->inventory->stock_level);
        $this->assertSame(170, $yellow->stock_qty);
        $this->assertSame(170, $yellow->inventory->stock_level);
        $this->assertSame(190, $black->stock_qty);
        $this->assertSame(190, $black->inventory->stock_level);

        $this->assertDatabaseHas('order_item_ink_usage', [
            'order_item_id' => $orderItem->id,
            'average_usage_ml' => '2.50',
            'total_ink_ml' => '100.00',
        ]);

        $order->refresh();
        $inkUsage = $order->metadata['ink_usage'];

        $this->assertSame([
            'cyan' => 30,
            'magenta' => 30,
            'yellow' => 30,
            'black' => 10,
        ], $inkUsage['required']);

        $this->assertSame($inkUsage['required'], $inkUsage['applied']);
        $this->assertSame(100.0, $inkUsage['total_required_ml']);
        $this->assertArrayNotHasKey('shortages', $inkUsage);
        $this->assertNotEmpty($inkUsage['items']);

        $this->assertCount(4, $inkUsage['items'][0]['distribution_ml'] ?? []);
    }

    public function test_deduct_ink_stock_handles_rounding_remainders(): void
    {
        [$cyan, $magenta, $yellow, $black] = $this->seedInks(50);

        $product = Product::create([
            'name' => 'Batch Invite',
            'event_type' => 'Birthday',
            'product_type' => 'Invitation',
            'base_price' => 30,
        ]);

        ProductColor::create([
            'product_id' => $product->id,
            'average_usage_ml' => 0.9,
        ]);

        $customerOrder = CustomerOrder::create([
            'name' => 'Remainder Customer',
            'email' => 'round@example.com',
        ]);

        $order = Order::create([
            'customer_order_id' => $customerOrder->id,
            'order_number' => 'INV-' . Str::upper(Str::random(6)),
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 12,
            'subtotal' => 36,
            'line_type' => OrderItem::LINE_TYPE_INVITATION,
        ]);

        /** @var OrderFlowService $service */
        $service = app(OrderFlowService::class);
        $service->deductInkStock($order->fresh('items.product.inkUsage'));

        $order->refresh();
        $inkUsage = $order->metadata['ink_usage'];

        $this->assertSame(3, array_sum($inkUsage['applied']));
        $this->assertSame($inkUsage['required'], $inkUsage['applied']);

        $cyan->refresh();
        $magenta->refresh();
        $yellow->refresh();
        $black->refresh();

        $totalConsumed =
            (50 - $cyan->stock_qty) +
            (50 - $magenta->stock_qty) +
            (50 - $yellow->stock_qty) +
            (50 - $black->stock_qty);

        $this->assertSame(3, $totalConsumed);
    }

    /**
     * @return array<int, Ink>
     */
    private function seedInks(int $stock): array
    {
        $colors = ['Cyan', 'Magenta', 'Yellow', 'Black'];
        $inks = [];

        foreach ($colors as $color) {
            $ink = Ink::create([
                'material_name' => $color . ' Ink',
                'product_type' => 'Invitation',
                'ink_color' => $color,
                'stock_qty_ml' => $stock,
                'stock_qty' => $stock,
                'cost_per_ml' => 1,
                'avg_usage_per_invite_ml' => 1,
                'cost_per_invite' => 1,
            ]);

            Inventory::create([
                'ink_id' => $ink->id,
                'stock_level' => $stock,
                'reorder_level' => 5,
            ]);

            $inks[] = $ink;
        }

        return $inks;
    }
}
