<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductMaterial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkProductMaterials extends Command
{
    protected $signature = 'material:link-products {--dry-run : Show what would be done without making changes}';
    protected $description = 'Link existing products to their materials for inventory deduction';

    public function handle()
    {
        $this->info('=== Linking Products to Materials ===');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all Invitation products with paper stocks but no material links
        $invitations = Product::with(['paperStocks.material'])
            ->where('product_type', 'Invitation')
            ->whereHas('paperStocks')
            ->whereDoesntHave('materials', function ($query) {
                $query->whereNull('order_id');
            })
            ->get();

        $this->info("Found {$invitations->count()} Invitation products without material links");

        if ($invitations->count() > 0) {
            $table = [];
            $linkedCount = 0;

            foreach ($invitations as $product) {
                $paperStocksWithMaterial = $product->paperStocks->filter(function ($ps) {
                    return $ps->material_id !== null;
                });

                $table[] = [
                    $product->id,
                    $product->name,
                    $paperStocksWithMaterial->count(),
                    $dryRun ? 'Would link' : 'Linking...',
                ];

                if (!$dryRun && $paperStocksWithMaterial->count() > 0) {
                    DB::transaction(function () use ($product, $paperStocksWithMaterial) {
                        // Delete existing product-level material links
                        $product->materials()->whereNull('order_id')->delete();

                        // Create new links for each paper stock
                        foreach ($paperStocksWithMaterial as $paperStock) {
                            ProductMaterial::create([
                                'product_id' => $product->id,
                                'material_id' => $paperStock->material_id,
                                'item' => $paperStock->name ?? 'paper_stock',
                                'type' => 'paper_stock',
                                'qty' => 1,
                                'source_type' => 'product',
                                'quantity_mode' => 'per_unit',
                            ]);
                        }
                    });

                    $linkedCount += $paperStocksWithMaterial->count();
                }
            }

            $this->table(['Product ID', 'Name', 'Paper Stocks', 'Status'], $table);
            
            if (!$dryRun) {
                $this->info("✓ Linked {$linkedCount} materials to {$invitations->count()} products");
            }
        }
        $this->newLine();

        // Check Giveaways
        $giveaways = Product::with(['materials'])
            ->where('product_type', 'Giveaway')
            ->get();

        $giveawaysWithMaterials = $giveaways->filter(function ($p) {
            return $p->materials()->whereNull('order_id')->exists();
        });

        $giveawaysWithoutMaterials = $giveaways->count() - $giveawaysWithMaterials->count();

        $this->info("Giveaway products:");
        $this->line("  With materials: {$giveawaysWithMaterials->count()}");
        if ($giveawaysWithoutMaterials > 0) {
            $this->warn("  Without materials: {$giveawaysWithoutMaterials}");
            $this->comment("  Note: Giveaways need materials to be assigned manually via the admin panel");
        }
        $this->newLine();

        // Check Envelopes
        $envelopes = Product::with(['envelope.material'])
            ->where('product_type', 'Envelope')
            ->get();

        $envelopesWithMaterial = $envelopes->filter(function ($p) {
            return $p->envelope && $p->envelope->material_id;
        });

        $envelopesWithoutMaterial = $envelopes->count() - $envelopesWithMaterial->count();

        $this->info("Envelope products:");
        $this->line("  With materials: {$envelopesWithMaterial->count()}");
        if ($envelopesWithoutMaterial > 0) {
            $this->warn("  Without materials: {$envelopesWithoutMaterial}");
        }
        $this->newLine();

        $this->info('=== Complete ===');
        
        if ($dryRun) {
            $this->comment('Run without --dry-run to apply changes');
        }

        return 0;
    }
}
