<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\User;
use App\Services\OrderFlowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestMaterialDeduction extends Command
{
    protected $signature = 'material:test-deduction';
    protected $description = 'Test material deduction by creating a sample order';

    public function __construct(private readonly OrderFlowService $orderFlow)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('=== Testing Material Deduction ===');
        $this->newLine();

        // 1. Find a product with materials
        $product = Product::with(['materials.material.inventory'])
            ->whereHas('materials', function ($query) {
                $query->whereNull('order_id');
            })
            ->first();

        if (!$product) {
            $this->error('No products found with material links. Cannot test deduction.');
            return 1;
        }

        $this->info("Testing with product: {$product->name} (ID: {$product->id})");
        $this->newLine();

        // 2. Check material stock before
        $this->info('Material stock BEFORE order:');
        $materialsBefore = [];
        foreach ($product->materials as $productMaterial) {
            $material = $productMaterial->material;
            if ($material) {
                $materialsBefore[$material->material_id] = [
                    'name' => $material->material_name,
                    'stock_qty' => $material->stock_qty,
                    'inventory_level' => $material->inventory ? $material->inventory->stock_level : null,
                    'required_qty' => $productMaterial->qty ?? 1,
                ];
                
                $this->line("  {$material->material_name}:");
                $this->line("    stock_qty: {$material->stock_qty}");
                $this->line("    inventory_level: " . ($material->inventory ? $material->inventory->stock_level : 'N/A'));
                $this->line("    required per order: " . ($productMaterial->qty ?? 1));
            }
        }
        $this->newLine();

        // 3. Create a test order
        $this->info('Creating test order...');
        
        $order = null;
        try {
            $user = User::first();
            if (!$user) {
                $this->error('No users found. Cannot create test order.');
                return 1;
            }

            $quantity = 10; // Order 10 units
            $unitPrice = $this->orderFlow->unitPriceFor($product);
            
            $summary = [
                'productId' => $product->id,
                'productName' => $product->name,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'subtotalAmount' => $unitPrice * $quantity,
                'taxAmount' => 0,
                'shippingFee' => 0,
                'totalAmount' => $unitPrice * $quantity,
                'metadata' => [
                    'design' => [
                        'template' => ['id' => $product->template_id],
                    ],
                ],
            ];

            // Check stock first
            $this->info('Checking stock availability...');
            $stockShortages = $this->orderFlow->checkStockFromSummary($summary);
            
            if (!empty($stockShortages)) {
                $this->warn('Stock shortages detected:');
                foreach ($stockShortages as $shortage) {
                    $this->line("  {$shortage['material_name']}: required={$shortage['required']}, available={$shortage['available']}");
                }
                $this->warn('Reducing order quantity to 1 to test deduction...');
                $quantity = 1;
                $summary['quantity'] = 1;
                $summary['subtotalAmount'] = $unitPrice;
                $summary['totalAmount'] = $unitPrice;
                
                $stockShortages = $this->orderFlow->checkStockFromSummary($summary);
                if (!empty($stockShortages)) {
                    $this->error('Still have stock shortages even with quantity 1. Cannot proceed.');
                    return 1;
                }
            }

            DB::transaction(function () use ($user, $summary, &$order) {
                $customerOrder = $this->orderFlow->createCustomerOrder($user);
                
                $order = $customerOrder->orders()->create([
                    'customer_id' => $customerOrder->customer_id,
                    'user_id' => $user->user_id,
                    'order_number' => $this->orderFlow->generateOrderNumber(),
                    'order_date' => now(),
                    'status' => 'pending',
                    'subtotal_amount' => $summary['subtotalAmount'],
                    'tax_amount' => $summary['taxAmount'] ?? 0,
                    'shipping_fee' => $summary['shippingFee'] ?? 0,
                    'total_amount' => $summary['totalAmount'],
                    'payment_status' => 'pending',
                    'metadata' => $summary['metadata'] ?? [],
                ]);

                // This should trigger material deduction
                $order = $this->orderFlow->initializeOrderFromSummary($order, $summary);
                $this->orderFlow->recalculateOrderTotals($order);
            });

            if (!$order) {
                $this->error('Failed to create order');
                return 1;
            }

            $this->info("✓ Order created: {$order->order_number}");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('Failed to create order: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        // 4. Check material stock after
        $this->info('Material stock AFTER order:');
        foreach ($materialsBefore as $materialId => $beforeData) {
            $material = Material::with('inventory')->find($materialId);
            if ($material) {
                $this->line("  {$material->material_name}:");
                $this->line("    stock_qty: {$beforeData['stock_qty']} → {$material->stock_qty}");
                
                $inventoryBefore = $beforeData['inventory_level'] ?? 'N/A';
                $inventoryAfter = $material->inventory ? $material->inventory->stock_level : 'N/A';
                $this->line("    inventory_level: {$inventoryBefore} → {$inventoryAfter}");
                
                $expectedDeduction = $beforeData['required_qty'] * $quantity;
                $actualDeduction = $beforeData['stock_qty'] - $material->stock_qty;
                
                if ($actualDeduction > 0) {
                    $this->info("    ✓ Deducted: {$actualDeduction} (expected: {$expectedDeduction})");
                } else {
                    $this->error("    ✗ NOT DEDUCTED! (expected: -{$expectedDeduction})");
                }
            }
        }
        $this->newLine();

        // 5. Check ProductMaterial records
        if (!$order) {
            $this->error('Order was not created. Cannot check material records.');
            return 1;
        }

        $this->info('Checking ProductMaterial tracking records...');
        $materialUsage = ProductMaterial::where('order_id', $order->id)->get();
        
        if ($materialUsage->count() > 0) {
            $table = [];
            foreach ($materialUsage as $usage) {
                $table[] = [
                    $usage->item ?? 'N/A',
                    number_format($usage->quantity_required, 2),
                    number_format($usage->quantity_used, 2),
                    $usage->deducted_at ? '✓ ' . $usage->deducted_at->format('Y-m-d H:i') : '✗ NOT DEDUCTED',
                ];
            }
            $this->table(['Material', 'Required', 'Used', 'Deducted At'], $table);
        } else {
            $this->error('✗ No material usage records created for this order!');
        }
        $this->newLine();

        // 6. Cleanup option
        if ($this->confirm('Delete the test order?', true)) {
            DB::transaction(function () use ($order) {
                // Restore materials before deleting
                $materialUsage = ProductMaterial::where('order_id', $order->id)->get();
                foreach ($materialUsage as $usage) {
                    if ($usage->material && $usage->quantity_used > 0) {
                        $material = $usage->material;
                        $material->stock_qty += (int) round($usage->quantity_used);
                        $material->save();
                        
                        if ($material->inventory) {
                            $material->inventory->stock_level += (int) round($usage->quantity_used);
                            $material->inventory->save();
                        }
                    }
                }
                
                $order->items()->delete();
                ProductMaterial::where('order_id', $order->id)->delete();
                $order->customerOrder()->delete();
                $order->delete();
            });
            $this->info('✓ Test order deleted and stock restored');
        }

        $this->info('=== Test Complete ===');
        return 0;
    }
}
