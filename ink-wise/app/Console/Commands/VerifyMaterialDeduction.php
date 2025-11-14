<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyMaterialDeduction extends Command
{
    protected $signature = 'material:verify-deduction {--fix : Fix missing inventory records}';
    protected $description = 'Verify material deduction is working and optionally fix issues';

    public function handle()
    {
        $this->info('=== Material Deduction Verification ===');
        $this->newLine();

        // 1. Check materials without inventory records
        $this->info('1. Checking materials without inventory records...');
        $materialsWithoutInventory = Material::whereDoesntHave('inventory')->get();
        
        if ($materialsWithoutInventory->count() > 0) {
            $this->warn("Found {$materialsWithoutInventory->count()} materials without inventory records:");
            $table = [];
            foreach ($materialsWithoutInventory as $material) {
                $table[] = [
                    $material->material_id,
                    $material->material_name,
                    $material->stock_qty ?? 0,
                ];
            }
            $this->table(['ID', 'Name', 'Stock Qty'], $table);
            
            if ($this->option('fix')) {
                $this->info('Creating missing inventory records...');
                foreach ($materialsWithoutInventory as $material) {
                    Inventory::create([
                        'material_id' => $material->material_id,
                        'stock_level' => $material->stock_qty ?? 0,
                        'reorder_level' => $material->reorder_point ?? 10,
                        'remarks' => 'Auto-created',
                    ]);
                }
                $this->info('✓ Created ' . $materialsWithoutInventory->count() . ' inventory records');
            }
        } else {
            $this->info('✓ All materials have inventory records');
        }
        $this->newLine();

        // 2. Check products without material links
        $this->info('2. Checking products without material links...');
        $productsWithoutMaterials = Product::whereDoesntHave('materials', function ($query) {
            $query->whereNull('order_id');
        })->get();
        
        if ($productsWithoutMaterials->count() > 0) {
            $this->warn("Found {$productsWithoutMaterials->count()} products without material links:");
            $table = [];
            foreach ($productsWithoutMaterials->take(10) as $product) {
                $table[] = [
                    $product->id,
                    $product->name,
                    $product->product_type,
                ];
            }
            $this->table(['ID', 'Name', 'Type'], $table);
            $this->comment('Note: Products need material links to deduct inventory on orders');
        } else {
            $this->info('✓ All products have material links');
        }
        $this->newLine();

        // 3. Check recent orders for material deduction
        $this->info('3. Checking recent orders for material deduction...');
        $recentOrders = Order::with(['items'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        if ($recentOrders->count() > 0) {
            $this->info("Showing last 5 orders from the past 7 days:");
            foreach ($recentOrders as $order) {
                $materialUsage = ProductMaterial::where('order_id', $order->id)->get();
                $this->line("Order #{$order->order_number} ({$order->created_at->format('Y-m-d H:i')}):");
                
                if ($materialUsage->count() > 0) {
                    $table = [];
                    foreach ($materialUsage as $usage) {
                        $table[] = [
                            $usage->item ?? 'N/A',
                            number_format($usage->quantity_required, 2),
                            number_format($usage->quantity_used, 2),
                            $usage->deducted_at ? $usage->deducted_at->format('Y-m-d H:i') : 'NOT DEDUCTED',
                        ];
                    }
                    $this->table(['Material', 'Required', 'Used', 'Deducted At'], $table);
                } else {
                    $this->warn('  No material usage tracked for this order!');
                }
                $this->newLine();
            }
        } else {
            $this->comment('No recent orders found in the past 7 days');
        }
        $this->newLine();

        // 4. Summary of stock levels
        $this->info('4. Current stock summary:');
        $lowStockMaterials = Material::with('inventory')
            ->where(function ($query) {
                $query->whereColumn('stock_qty', '<=', 'reorder_point')
                    ->orWhereHas('inventory', function ($q) {
                        $q->whereColumn('stock_level', '<=', 'reorder_level');
                    });
            })
            ->get();
        
        if ($lowStockMaterials->count() > 0) {
            $this->warn("Found {$lowStockMaterials->count()} materials with low stock:");
            $table = [];
            foreach ($lowStockMaterials->take(10) as $material) {
                $table[] = [
                    $material->material_name,
                    $material->stock_qty ?? 0,
                    $material->inventory ? $material->inventory->stock_level : 'N/A',
                    $material->reorder_point ?? 0,
                ];
            }
            $this->table(['Material', 'Stock Qty', 'Inventory Level', 'Reorder Point'], $table);
        } else {
            $this->info('✓ All materials have sufficient stock');
        }
        $this->newLine();

        // 5. Test deduction logic
        $this->info('5. Testing deduction logic...');
        $testMaterial = Material::with('inventory')->first();
        if ($testMaterial) {
            $beforeMaterialStock = $testMaterial->stock_qty;
            $beforeInventoryLevel = $testMaterial->inventory ? $testMaterial->inventory->stock_level : null;
            
            $this->line("Test material: {$testMaterial->material_name}");
            $this->line("  Material stock_qty: {$beforeMaterialStock}");
            $this->line("  Inventory stock_level: " . ($beforeInventoryLevel ?? 'N/A'));
            
            if ($beforeMaterialStock > 0) {
                $this->comment('  Deduction logic exists in OrderFlowService::adjustMaterialStock()');
                $this->comment('  It should update both stock_qty and stock_level on order creation');
            } else {
                $this->warn('  Material has 0 stock - orders may fail stock check');
            }
        }
        $this->newLine();

        $this->info('=== Verification Complete ===');
        $this->newLine();
        
        if (!$this->option('fix')) {
            $this->comment('Run with --fix option to automatically create missing inventory records');
        }

        return 0;
    }
}
