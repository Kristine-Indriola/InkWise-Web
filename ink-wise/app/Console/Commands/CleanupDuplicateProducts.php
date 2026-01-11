<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Template;

class CleanupDuplicateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-duplicate-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate products for the same template, keeping only the first one created.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of duplicate products...');

        // Get all templates that have multiple products
        $templatesWithDuplicates = Template::with('products')
            ->whereHas('products', function ($query) {
                $query->havingRaw('COUNT(*) > 1');
            })
            ->withCount('products')
            ->having('products_count', '>', 1)
            ->get();

        $totalDeleted = 0;

        foreach ($templatesWithDuplicates as $template) {
            $products = $template->products()->orderBy('created_at')->get();
            
            // Keep the first product, delete the rest
            $productsToDelete = $products->skip(1);
            
            $this->info("Template {$template->id} ({$template->name}) has {$products->count()} products. Keeping first, deleting " . $productsToDelete->count());
            
            foreach ($productsToDelete as $product) {
                $product->delete();
                $totalDeleted++;
            }
        }

        $this->info("Cleanup completed. Deleted {$totalDeleted} duplicate products.");
        
        return Command::SUCCESS;
    }
}
