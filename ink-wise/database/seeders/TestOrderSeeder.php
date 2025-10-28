<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestOrderSeeder extends Seeder
{
    public function run()
    {
        // Create 3 customer_orders and linked orders + order_items
        for ($i = 1; $i <= 3; $i++) {
            $coId = DB::table('customer_orders')->insertGetId([
                'name' => 'Test Customer ' . $i,
                'email' => "test{$i}@example.com",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderNumber = 'TEST-' . strtoupper(Str::random(6));

            $orderId = DB::table('orders')->insertGetId([
                'customer_order_id' => $coId,
                'customer_id' => null,
                'user_id' => null,
                'order_number' => $orderNumber,
                'order_date' => now()->subDays(4 - $i),
                'status' => $i === 1 ? 'pending' : ($i === 2 ? 'completed' : 'confirmed'),
                'subtotal_amount' => 100 * $i,
                'tax_amount' => 0,
                'shipping_fee' => 0,
                'total_amount' => 100 * $i,
                'created_at' => now()->subDays(4 - $i),
                'updated_at' => now()->subDays(4 - $i),
            ]);

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_name' => 'Sample Product ' . $i,
                'quantity' => 1 + $i,
                'unit_price' => 100 * $i,
                'subtotal' => 100 * $i * (1 + $i),
                'created_at' => now()->subDays(4 - $i),
                'updated_at' => now()->subDays(4 - $i),
            ]);
        }
    }
}
