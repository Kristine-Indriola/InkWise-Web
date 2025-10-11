<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductBulkOrder;
use App\Models\ProductColor;
use App\Models\ProductEnvelope;
use App\Models\ProductPaperStock;
use App\Models\User;
use Illuminate\Support\Arr;
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
        $color = ProductColor::create([
            'product_id' => $product->id,
            'name' => 'Champagne Gold',
            'color_code' => '#E8D6A7',
        ]);

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
                'embossed_powder' => [
                    'id' => $color->id,
                    'name' => $color->name,
                    'color_code' => $color->color_code,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.quantity', 120)
            ->assertJsonPath('summary.paperStockId', $paperStock->id)
            ->assertJsonPath('summary.extras.paper', 150)
            ->assertJsonPath('summary.extras.addons', 20)
            ->assertJsonPath('summary.bulkSelection.qty_selected', 120)
            ->assertJsonPath('summary.colorSelections.0.color_name', $color->name);

    $summary = $response->json('summary');
    $this->assertEqualsWithDelta(3170.0, $summary['subtotalAmount'], 0.001);
    $this->assertEqualsWithDelta(0.0, $summary['taxAmount'], 0.001);
    $this->assertEqualsWithDelta(250.0, $summary['shippingFee'], 0.001);
    $this->assertEqualsWithDelta(3420.0, $summary['totalAmount'], 0.001);

        $order = Order::with(['items.paperStockSelection', 'items.addons', 'items.bulkSelections', 'items.colors'])->first();
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
        $this->assertCount(1, $item->bulkSelections);
        $this->assertEquals(120, $item->bulkSelections->first()->qty_selected);
        $this->assertEqualsWithDelta(25.0, $item->bulkSelections->first()->price_per_unit, 0.001);
        $this->assertCount(1, $item->colors);
        $this->assertEquals($color->id, $item->colors->first()->color_id);
        $this->assertSame($color->name, $item->colors->first()->color_name);

    $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
    $this->assertEqualsWithDelta(0.0, $order->tax_amount, 0.001);
    $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
    $this->assertEqualsWithDelta(3420.0, $order->total_amount, 0.001);

        $this->assertArrayHasKey('final_step', $order->metadata);
        $this->assertSame([$addon->id], $order->metadata['final_step']['addon_ids']);
        $this->assertSame(120, Arr::get($order->metadata, 'final_step.bulk.qty_selected'));
        $this->assertSame($color->id, Arr::get($order->metadata, 'final_step.color_ids.0'));

        $this->assertDatabaseHas('order_item_bulk', [
            'order_item_id' => $item->id,
            'qty_selected' => 120,
        ]);
        $this->assertDatabaseHas('order_item_colors', [
            'order_item_id' => $item->id,
            'color_id' => $color->id,
        ]);
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
            ->assertJsonPath('summary.totalAmount', 4440);

    $order = Order::first();
    $this->assertEqualsWithDelta(4190.0, $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
        $this->assertEqualsWithDelta(4440.0, $order->total_amount, 0.001);
        $this->assertArrayHasKey('envelope', $order->metadata);
        $this->assertEquals('Classic White', $order->metadata['envelope']['name']);

        $order->load(['items.addons', 'items.bulkSelections', 'items.colors', 'items.paperStockSelection']);
        $envelopeItem = $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_ENVELOPE);
        $this->assertNotNull($envelopeItem);
        $this->assertNull($envelopeItem->paperStockSelection);
        $this->assertCount(0, $envelopeItem->bulkSelections);
        $this->assertCount(0, $envelopeItem->addons);
        $this->assertCount(0, $envelopeItem->colors);

        $this->deleteJson('/order/envelope')
            ->assertOk()
            ->assertJsonMissingPath('summary.envelope');

    $order->refresh();
    $this->assertArrayNotHasKey('envelope', $order->metadata ?? []);
    $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
    $this->assertEqualsWithDelta(0.0, $order->tax_amount, 0.001);
    $this->assertEqualsWithDelta(3420.0, $order->total_amount, 0.001);
    }

    public function test_summary_sync_uses_preview_selections_for_associations(): void
    {
        [$user, $product, , $paperStock, $addon] = $this->seedInvitationProduct();
        $color = ProductColor::create([
            'product_id' => $product->id,
            'name' => 'Rose Gold',
            'color_code' => '#B76E79',
        ]);

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $product->id,
            'quantity' => 80,
        ])->assertRedirect(route('order.review'));

        $this->postJson('/order/finalstep/save', [
            'quantity' => 80,
        ])->assertOk();

        $this->assertTrue(
            OrderItem::where('line_type', OrderItem::LINE_TYPE_INVITATION)->exists(),
            'Expected an invitation order item to exist after final step save.'
        );

        $payload = [
            'productId' => $product->id,
            'productName' => $product->name,
            'quantity' => 80,
            'unitPrice' => 25,
            'subtotalAmount' => 2000,
            'totalAmount' => 2000,
            'previewSelections' => [
                'paper_stock' => [
                    'id' => $paperStock->id,
                    'name' => $paperStock->name,
                    'price' => $paperStock->price,
                ],
                'trim' => [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price,
                ],
                'embossed_powder' => [
                    'id' => $color->id,
                    'name' => $color->name,
                    'color_code' => $color->color_code,
                ],
            ],
        ];

        $this->postJson('/order/summary/sync', ['summary' => $payload])
            ->assertOk();

    $order = Order::first();
        $this->assertNotNull($order);

        $item = OrderItem::where('order_id', $order->id)
            ->where('line_type', OrderItem::LINE_TYPE_INVITATION)
            ->with(['addons', 'colors'])
            ->first();
        $this->assertNotNull($item);
        $this->assertDatabaseHas('order_item_paper_stock', [
            'order_item_id' => $item->id,
            'paper_stock_id' => $paperStock->id,
        ]);
        $item->load(['addons', 'colors']);
        $this->assertCount(1, $item->addons);
        $this->assertEquals($addon->id, $item->addons->first()->addon_id);
        $this->assertCount(1, $item->colors);
        $this->assertEquals($color->id, $item->colors->first()->color_id);
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
            ->assertJsonPath('summary.totalAmount', 4080);

        $order = Order::first();
        $this->assertArrayHasKey('giveaway', $order->metadata);
        $this->assertEquals('Thank You Stickers', $order->metadata['giveaway']['name']);
    $this->assertEqualsWithDelta(3830.0, $order->subtotal_amount, 0.001);
    $this->assertEqualsWithDelta(0.0, $order->tax_amount, 0.001);
    $this->assertEqualsWithDelta(250.0, $order->shipping_fee, 0.001);
    $this->assertEqualsWithDelta(4080.0, $order->total_amount, 0.001);

        $this->deleteJson('/order/giveaways')
            ->assertOk()
            ->assertJsonMissingPath('summary.giveaway');

    $order->refresh();
    $this->assertArrayNotHasKey('giveaway', $order->metadata ?? []);
    $this->assertEqualsWithDelta(3170.0, $order->subtotal_amount, 0.001);
    $this->assertEqualsWithDelta(0.0, $order->tax_amount, 0.001);
    $this->assertEqualsWithDelta(3420.0, $order->total_amount, 0.001);
    }

    public function test_checkout_persists_session_summary_into_order_tables(): void
    {
        [$user, $invitationProduct] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $invitationProduct->id,
            'quantity' => 110,
        ])->assertRedirect(route('order.review'));

        $envelopeProduct = Product::create([
            'name' => 'Ivory Envelope',
            'event_type' => 'Wedding',
            'product_type' => 'Envelope',
            'base_price' => 9.5,
        ]);

        ProductBulkOrder::create([
            'product_id' => $envelopeProduct->id,
            'min_qty' => 50,
            'max_qty' => 300,
            'price_per_unit' => 9.5,
        ]);

        ProductPaperStock::create([
            'product_id' => $envelopeProduct->id,
            'name' => 'Ivory Satin',
            'price' => 5.0,
        ]);

        $envelopeAddon = ProductAddon::create([
            'product_id' => $envelopeProduct->id,
            'addon_type' => 'liner',
            'name' => 'Luxury Liner',
            'price' => 120,
        ]);

        ProductColor::create([
            'product_id' => $envelopeProduct->id,
            'name' => 'Ivory Accent',
            'color_code' => '#f9f7f1',
        ]);

        $envelopeRecord = ProductEnvelope::create([
            'product_id' => $envelopeProduct->id,
            'envelope_material_name' => 'Ivory Satin',
            'price_per_unit' => 9.5,
            'max_qty' => 300,
            'envelope_image' => '/images/no-image.png',
        ]);

        $this->postJson('/order/envelope', [
            'product_id' => $envelopeProduct->id,
            'envelope_id' => $envelopeRecord->id,
            'quantity' => 120,
            'unit_price' => 9.5,
            'total_price' => 1140,
            'metadata' => [
                'name' => 'Ivory Satin',
                'material' => 'Ivory Satin',
                'image' => '/images/no-image.png',
                'max_qty' => 300,
                'addons' => [
                    [
                        'id' => $envelopeAddon->id,
                        'name' => 'Luxury Liner',
                        'type' => 'liner',
                        'price' => 120,
                    ],
                ],
            ],
        ])->assertOk();

        $giveawayProduct = Product::create([
            'name' => 'Mini Scented Candle',
            'event_type' => 'Wedding',
            'product_type' => 'Giveaway',
            'base_price' => 6.75,
            'description' => 'Aromatic candle to thank guests.',
        ]);

        ProductBulkOrder::create([
            'product_id' => $giveawayProduct->id,
            'min_qty' => 50,
            'max_qty' => 400,
            'price_per_unit' => 6.50,
        ]);

        $this->postJson('/order/giveaways', [
            'product_id' => $giveawayProduct->id,
            'quantity' => 150,
            'unit_price' => 6.75,
            'total_price' => 1012.5,
            'metadata' => [
                'name' => 'Mini Scented Candle',
                'image' => '/images/no-image.png',
                'description' => 'Aromatic candle to thank guests.',
            ],
        ])->assertOk();

        // Simulate the customer visiting the order summary page before checkout.
        $this->get('/order/summary')->assertOk();

        $this->get('/checkout')->assertOk();

        $order = Order::with([
            'items.addons',
            'items.bulkSelections',
            'items.colors',
            'items.paperStockSelection',
        ])->first();

        $this->assertNotNull($order, 'Order should be persisted after checkout redirect.');
        $this->assertCount(3, $order->items);

        $envelopeItem = $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_ENVELOPE);
        $this->assertNotNull($envelopeItem);
        $this->assertSame($envelopeProduct->id, $envelopeItem->product_id);
        $this->assertSame(120, $envelopeItem->quantity);
        $this->assertNull($envelopeItem->paperStockSelection);
        $this->assertTrue($envelopeItem->bulkSelections->isEmpty());
        $this->assertTrue($envelopeItem->addons->isEmpty());
        $this->assertTrue($envelopeItem->colors->isEmpty());

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'line_type' => OrderItem::LINE_TYPE_ENVELOPE]);
        $this->assertDatabaseMissing('order_item_addons', ['order_item_id' => $envelopeItem->id]);
        $this->assertDatabaseMissing('order_item_bulk', ['order_item_id' => $envelopeItem->id]);
        $this->assertDatabaseMissing('order_item_colors', ['order_item_id' => $envelopeItem->id]);
        $this->assertDatabaseMissing('order_item_paper_stock', ['order_item_id' => $envelopeItem->id]);
    }

    public function test_summary_sync_endpoint_persists_client_envelope_before_checkout(): void
    {
        [$user, $invitationProduct] = $this->seedInvitationProduct();

        $this->actingAs($user);

        $this->post('/order/cart/items', [
            'product_id' => $invitationProduct->id,
            'quantity' => 100,
        ])->assertRedirect(route('order.review'));

        $baseSummary = session('order_summary_payload');
        $this->assertIsArray($baseSummary);

        $envelopeProduct = Product::create([
            'name' => 'Pearl Envelope',
            'event_type' => 'Wedding',
            'product_type' => 'Envelope',
            'base_price' => 9.75,
        ]);

        ProductBulkOrder::create([
            'product_id' => $envelopeProduct->id,
            'min_qty' => 50,
            'max_qty' => 300,
            'price_per_unit' => 9.75,
        ]);

        ProductPaperStock::create([
            'product_id' => $envelopeProduct->id,
            'name' => 'Pearl Satin',
            'price' => 6.0,
        ]);

        $envelopeRecord = ProductEnvelope::create([
            'product_id' => $envelopeProduct->id,
            'envelope_material_name' => 'Pearl Satin',
            'price_per_unit' => 9.75,
            'max_qty' => 300,
            'envelope_image' => '/images/no-image.png',
        ]);

        $clientSummary = $baseSummary;
        $clientSummary['envelope'] = [
            'id' => $envelopeRecord->id,
            'product_id' => $envelopeProduct->id,
            'name' => 'Pearl Satin',
            'qty' => 120,
            'price' => 9.75,
            'total' => 1170.0,
            'material' => 'Pearl Satin',
            'image' => '/images/no-image.png',
        ];

        $clientSummary['extras'] = $clientSummary['extras'] ?? ['paper' => 0, 'addons' => 0, 'envelope' => 0, 'giveaway' => 0];
        $clientSummary['extras']['envelope'] = 1170.0;
        $clientSummary['hasEnvelope'] = true;

        session()->put('order_summary_payload', Arr::except($baseSummary, ['envelope', 'hasEnvelope']));

        $response = $this->postJson('/order/summary/sync', ['summary' => $clientSummary]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.hasEnvelope', true)
            ->assertJsonPath('summary.envelope.name', 'Pearl Satin');

        $this->assertEqualsWithDelta(1170.0, $response->json('summary.envelope.total'), 0.001);

        $this->get('/checkout')->assertOk();

        $order = Order::with('items')->first();
        $this->assertNotNull($order);
        $envelopeItem = $order->items->firstWhere('line_type', OrderItem::LINE_TYPE_ENVELOPE);
        $this->assertNotNull($envelopeItem);
        $this->assertSame($envelopeProduct->id, $envelopeItem->product_id);
        $this->assertSame(120, $envelopeItem->quantity);
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
