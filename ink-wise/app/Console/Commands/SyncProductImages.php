<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

class SyncProductImages extends Command
{
    protected $signature = 'templates:sync-product-images';
    protected $description = 'Copy template previews into products folder and update product.image when missing';

    public function handle()
    {
        $this->info('Scanning products for missing images...');

        $products = Product::whereNull('image')->whereNotNull('template_id')->get();
        if ($products->isEmpty()) {
            $this->info('No products found that require syncing.');
            return 0;
        }

        foreach ($products as $product) {
            $template = Template::find($product->template_id);
            if (!$template || empty($template->preview)) {
                $this->warn("Product {$product->id} has no template preview to copy.");
                continue;
            }

            $preview = preg_replace('#^/??storage/#i', '', $template->preview);
            try {
                // try to resolve actual file path (handles missing extension)
                $actual = $preview;
                if (!Storage::disk('public')->exists($preview)) {
                    // search templates/previews for matching base name
                    $base = pathinfo($preview, PATHINFO_FILENAME);
                    if (Storage::disk('public')->exists('templates/previews')) {
                        $files = Storage::disk('public')->files('templates/previews');
                        foreach ($files as $f) {
                            if (strpos(pathinfo($f, PATHINFO_FILENAME), $base) === 0) {
                                $actual = $f;
                                break;
                            }
                        }
                    }
                }

                // If preview exists on public disk, copy it
                if (Storage::disk('public')->exists($actual)) {
                    $ext = pathinfo($actual, PATHINFO_EXTENSION) ?: 'png';
                    $newName = 'products/product_' . $product->id . '_' . time() . '.' . $ext;
                    Storage::disk('public')->copy($actual, $newName);
                    $product->image = $newName;
                    $product->save();
                    $this->info("Copied for product {$product->id} -> {$newName}");
                    continue;
                }

                // Try public filesystem paths
                $possible = [
                    public_path('storage/' . $actual),
                    public_path($actual),
                ];
                $copied = false;
                foreach ($possible as $p) {
                    if (file_exists($p)) {
                        $ext = pathinfo($p, PATHINFO_EXTENSION) ?: 'png';
                        $newName = 'products/product_' . $product->id . '_' . time() . '.' . $ext;
                        $contents = file_get_contents($p);
                        Storage::disk('public')->put($newName, $contents);
                        $product->image = $newName;
                        $product->save();
                        $this->info("Copied for product {$product->id} -> {$newName}");
                        $copied = true;
                        break;
                    }
                }

                if (!$copied) {
                    $this->warn("Could not find preview file for template {$template->id} (product {$product->id}).");
                }

            } catch (\Throwable $e) {
                $this->error("Error processing product {$product->id}: " . $e->getMessage());
            }
        }

        $this->info('Done.');
        return 0;
    }
}
