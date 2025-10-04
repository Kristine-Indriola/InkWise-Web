<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_view_an_order_summary(): void
    {
        $user = User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $customerOrder = CustomerOrder::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '09171234567',
            'address' => '123 Main St',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'postal_code' => '1100',
        ]);

        $order = Order::create([
            'customer_order_id' => $customerOrder->id,
            'order_number' => 'ORD-1001',
            'order_date' => now()->subDay(),
            'status' => 'in_production',
            'payment_status' => 'paid',
            'subtotal_amount' => 4800,
            'tax_amount' => 576,
            'shipping_fee' => 200,
            'total_amount' => 5576,
            'summary_snapshot' => [
                'currency' => 'PHP',
                'customer' => ['tags' => ['wedding']],
            ],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Wedding Invitation Set',
            'quantity' => 50,
            'unit_price' => 96,
            'subtotal' => 4800,
            'design_metadata' => [
                'options' => [
                    'size' => '5x7 inches',
                    'paper_stock' => 'Luxe Cotton',
                ],
                'preview_images' => ['https://example.com/invite.jpg'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.ordersummary.index', ['order' => $order->order_number]));

        $response->assertOk();
        $response->assertViewIs('admin.ordersummary.index');
        $response->assertSee('ORD-1001');
        $response->assertViewHas('order', function ($viewOrder) {
            return data_get($viewOrder, 'items.0.name') === 'Wedding Invitation Set'
                && data_get($viewOrder, 'payment_status') === 'paid';
        });
    }
}
