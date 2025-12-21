<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SimCartItemSeeder extends Seeder
{
    public function run(): void
    {
        $product = \App\Models\Product::first();
        if (! $product) {
            $this->command->info('No product found; skipping');
            return;
        }

        $session = 'sim_'.uniqid();

        $ci = \App\Models\CartItem::create([
            'session_id' => $session,
            'customer_id' => null,
            'product_type' => $product->product_type ?? null,
            'product_id' => $product->id,
            'quantity' => 1,
            'paper_type_id' => null,
            'paper_price' => 0,
            'unit_price' => round($product->price ?? 0, 2),
            'total_price' => round($product->price ?? 0, 2),
            'status' => 'not_ordered',
            'metadata' => [],
        ]);

        $this->command->info('CREATED_ID='.$ci->id.' SESSION='.$session);
    }
}
