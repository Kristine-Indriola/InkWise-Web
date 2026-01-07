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

            // Normalize preview: accept full URLs, '/storage/..', 'storage/..' or storage-relative paths
            $rawPreview = $template->preview;
            $preview = '';
            // If it's a full URL, extract the path component (e.g. /storage/templates/previews/..)
            if (preg_match('#^https?://#i', $rawPreview)) {
                $parsed = parse_url($rawPreview);
                $path = $parsed['path'] ?? '';
                // remove leading /storage/ if present
                $preview = preg_replace('#^/??storage/#i', '', ltrim($path, '/'));
            } else {
                $preview = preg_replace('#^/??storage/#i', '', ltrim($rawPreview, '/'));
            }
            try {
                // try to resolve actual file path (handles missing extension)
                $actual = $preview;
                if (!Storage::disk('public')->exists($preview)) {
                    // search saved_templates/previews and templates/previews for matching base name
                    $base = pathinfo($preview, PATHINFO_FILENAME);
                    $dirs = ['saved_templates/previews', 'templates/previews'];
                    foreach ($dirs as $dir) {
                        if (Storage::disk('public')->exists($dir)) {
                            $files = Storage::disk('public')->files($dir);
                            foreach ($files as $f) {
                                if (strpos(pathinfo($f, PATHINFO_FILENAME), $base) === 0) {
                                    $actual = $f;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // If preview exists on public disk, copy it
                if ($actual && Storage::disk('public')->exists($actual)) {
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
                    if ($p && file_exists($p)) {
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

                // If still not copied and original was a remote URL, attempt to download it
                if (!$copied && preg_match('#^https?://#i', $rawPreview)) {
                    try {
                        $this->info("Attempting to download remote preview for template {$template->id}");
                        $contents = @file_get_contents($rawPreview);
                        if ($contents !== false) {
                            // try to determine extension from headers or URL
                            $ext = pathinfo(parse_url($rawPreview, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                            $newName = 'products/product_' . $product->id . '_' . time() . '.' . $ext;
                            Storage::disk('public')->put($newName, $contents);
                            $product->image = $newName;
                            $product->save();
                            $this->info("Downloaded remote preview for product {$product->id} -> {$newName}");
                            $copied = true;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("Failed to download remote preview: " . $e->getMessage());
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
