<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductBulkOrder;
use App\Models\ProductEnvelope;
use App\Models\ProductPaperStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function seedInvitationProduct(): array
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Elegant Invitation',
            'event_type' => 'Wedding',
            'product_type' => 'Invitation',
            'base_price' => 25,
        ]);

        $bulk = ProductBulkOrder::create([
            'product_id' => $product->id,
            'min_qty' => 50,
            'max_qty' => 200,
            'price_per_unit' => 25,
        ]);

        $paperStock = ProductPaperStock::create([
            'product_id' => $product->id,
            'name' => 'Luxe Cotton',
            'price' => 150,
        ]);

        $addon = ProductAddon::create([
            'product_id' => $product->id,
            'addon_type' => 'trim',
            'name' => 'Gold Foil',
            'price' => 20,
        ]);

        return [$user, $product, $bulk, $paperStock, $addon];
    }

    public function test_final_step_save_updates_order_and_summary(): void
    {
        [$user, $product, , $paperStock, $addon] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])->assertRedirect(route('order.review'));

        $response = $this->postJson('/order/finalstep/save', [
            'quantity' => 120,
            'paper_stock_id' => $paperStock->id,
            'paper_stock_price' => 150,
            'paper_stock_name' => $paperStock->name,
            'addons' => [$addon->id],
            'metadata' => ['note' => 'test-meta'],
            'preview_selections' => [
                'paper_stock' => [
                    'id' => $paperStock->id,
                    'name' => $paperStock->name,
                    'price' => $paperStock->price,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.quantity', 120)
            ->assertJsonPath('summary.paperStockId', $paperStock->id)
            ->assertJsonPath('summary.extras.paper', 150)
            ->assertJsonPath('summary.extras.addons', 20);

        $summary = $response->json('summary');
        $this->assertEqualsWithDelta(3170.0, $summary['subtotalAmount'], 0.001);
        $this->assertEqualsWithDelta(380.4, $summary['taxAmount'], 0.001);
        $this->assertEqualsWithDelta(250.0, $summary['shippingFee'], 0.001);
        $this->assertEqualsWithDelta(3800.4, $summary['totalAmount'], 0.001);

        $order = Order::with(['items.paperStockSelection', 'items.addons'])->first();
        $this->assertNotNull($order);

        $item = $order->items->first();
        $this->assertSame(120, $item->quantity);
        $this->assertEqualsWithDelta(25.0, $item->unit_price, 0.001);
        $this->assertEqualsWithDelta(3000.0, $item->subtotal, 0.001);
        $this->assertNotNull($item->paperStockSelection);
        $this->assertEquals($paperStock->id, $item->paperStockSelection->paper_stock_id);
        $this->assertEqualsWithDelta(150.0, $item->paperStockSelection->price, 0.001);
        $this->assertCount(1, $item->addons);
        $this->assertEquals($addon->id, $item->addons->first()->addon_id);

        $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(380.4, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
        $this->assertEqualsWithDelta(3800.4, $order->total_amount, 0.001);

        $this->assertArrayHasKey('final_step', $order->metadata);
        $this->assertSame([$addon->id], $order->metadata['final_step']['addon_ids']);
    }

    public function test_envelope_selection_updates_metadata_and_totals(): void
    {
        [$user, $product, , $paperStock, $addon] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])->assertRedirect(route('order.review'));

        $this->postJson('/order/finalstep/save', [
            'quantity' => 120,
            'paper_stock_id' => $paperStock->id,
            'paper_stock_price' => 150,
            'paper_stock_name' => $paperStock->name,
            'addons' => [$addon->id],
        ])->assertOk();

        $envelopeProduct = Product::create([
            'name' => 'Classic Envelope',
            'event_type' => 'Wedding',
            'product_type' => 'Envelope',
            'base_price' => 8.5,
        ]);

        $envelope = ProductEnvelope::create([
            'product_id' => $envelopeProduct->id,
            'envelope_material_name' => 'Classic White',
            'price_per_unit' => 8.5,
            'max_qty' => 300,
        ]);

        $response = $this->postJson('/order/envelope', [
            'product_id' => $envelopeProduct->id,
            'envelope_id' => $envelope->id,
            'quantity' => 120,
            'unit_price' => 8.5,
            'total_price' => 1020,
            'metadata' => [
                'name' => 'Classic White',
                'material' => 'Uncoated smooth',
                'image' => '/images/no-image.png',
                'max_qty' => 300,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.envelope.name', 'Classic White')
            ->assertJsonPath('summary.envelope.total', 1020)
            ->assertJsonPath('summary.extras.envelope', 1020)
            ->assertJsonPath('summary.totalAmount', 4942.8);

        $order = Order::first();
        $this->assertEqualsWithDelta(4190.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(502.8, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
        $this->assertEqualsWithDelta(4942.8, $order->total_amount, 0.001);
        $this->assertArrayHasKey('envelope', $order->metadata);
        $this->assertEquals('Classic White', $order->metadata['envelope']['name']);

        $this->deleteJson('/order/envelope')
            ->assertOk()
            ->assertJsonMissingPath('summary.envelope');

        $order->refresh();
        $this->assertArrayNotHasKey('envelope', $order->metadata ?? []);
        $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(380.4, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(3800.4, $order->total_amount, 0.001);
    }

    public function test_giveaway_selection_updates_metadata_and_totals(): void
    {
        [$user, $product, , $paperStock, $addon] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])->assertRedirect(route('order.review'));

        $this->postJson('/order/finalstep/save', [
            'quantity' => 120,
            'paper_stock_id' => $paperStock->id,
            'paper_stock_price' => 150,
            'paper_stock_name' => $paperStock->name,
            'addons' => [$addon->id],
        ])->assertOk();

        $giveawayProduct = Product::create([
            'name' => 'Thank You Stickers',
            'event_type' => 'Wedding',
            'product_type' => 'Giveaway',
            'base_price' => 5.5,
            'description' => 'Set of 120 metallic thank-you stickers.',
        ]);

        $response = $this->postJson('/order/giveaways', [
            'product_id' => $giveawayProduct->id,
            'quantity' => 120,
            'unit_price' => 5.5,
            'total_price' => 660,
            'metadata' => [
                'name' => 'Thank You Stickers',
                'image' => '/images/no-image.png',
                'description' => 'Metallic thank-you stickers',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.giveaway.name', 'Thank You Stickers')
            ->assertJsonPath('summary.giveaway.total', 660)
            ->assertJsonPath('summary.extras.giveaway', 660)
            ->assertJsonPath('summary.totalAmount', 4539.6);

        $order = Order::first();
        $this->assertArrayHasKey('giveaway', $order->metadata);
        $this->assertEquals('Thank You Stickers', $order->metadata['giveaway']['name']);
        $this->assertEqualsWithDelta(3830.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(459.6, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
        $this->assertEqualsWithDelta(4539.6, $order->total_amount, 0.001);

        $this->deleteJson('/order/giveaways')
            ->assertOk()
            ->assertJsonMissingPath('summary.giveaway');

        $order->refresh();
        $this->assertArrayNotHasKey('giveaway', $order->metadata ?? []);
        $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(380.4, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(3800.4, $order->total_amount, 0.001);
    }

    public function test_summary_clear_endpoint_removes_active_order(): void
    {
        [$user, $product] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])->assertRedirect(route('order.review'));

        $this->postJson('/order/finalstep/save', [
            'quantity' => 100,
        ])->assertOk();

        $response = $this->deleteJson('/order/summary');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Order summary cleared.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseCount('orders', 0);
        $this->assertFalse(session()->has('order_summary_payload'));
        $this->assertFalse(session()->has('current_order_id'));
    }
}
