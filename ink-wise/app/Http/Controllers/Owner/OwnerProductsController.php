<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OwnerProductsController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with([
            'template',
            'images',
            'uploads',
            'envelope.material',
            'paperStocks',
            'addons',
            'colors',
            'bulkOrders'
        ]);

        if ($type = $request->query('type')) {
            $query->whereRaw('LOWER(product_type) = ?', [Str::lower($type)]);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%");
            });
        }

        if ($stockFilter = $request->query('stock')) {
            if ($stockFilter === 'in' && Schema::hasColumn('products', 'stock_availability')) {
                $query->where(function ($q) {
                    $q->whereNull('stock_availability')
                      ->orWhereRaw('LOWER(stock_availability) like ?', ['%in stock%'])
                      ->orWhereRaw('LOWER(stock_availability) like ?', ['%available%']);
                });
            }
        }

        $products = $query
            ->orderByDesc('created_at')
            ->paginate(12)
            ->appends($request->query());

        $products->getCollection()->each(function (Product $product) {
            $this->hydrateLegacyAttributes($product);
        });

        $summaryBaseQuery = Product::query();
        $totalProducts = (clone $summaryBaseQuery)->count();
        $invitationCount = (clone $summaryBaseQuery)
            ->whereRaw('LOWER(product_type) = ?', ['invitation'])
            ->count();
        $giveawayCount = (clone $summaryBaseQuery)
            ->whereRaw('LOWER(product_type) = ?', ['giveaway'])
            ->count();

        $inStockCount = null;
        if (Schema::hasColumn('products', 'stock_availability')) {
            $inStockCount = (clone $summaryBaseQuery)
                ->where(function ($q) {
                    $q->whereNull('stock_availability')
                      ->orWhereRaw('LOWER(stock_availability) like ?', ['%in stock%'])
                      ->orWhereRaw('LOWER(stock_availability) like ?', ['%available%']);
                })
                ->count();
        }

        return view('owner.products.index', compact(
            'products',
            'totalProducts',
            'invitationCount',
            'giveawayCount',
            'inStockCount'
        ));
    }

    public function show(Product $product)
    {
        $product->load([
            'template',
            'images',
            'uploads',
            'envelope.material',
            'paperStocks',
            'addons',
            'colors',
            'bulkOrders'
        ]);

        $this->hydrateLegacyAttributes($product);

        return view('owner.products.show', compact('product'));
    }

    protected function hydrateLegacyAttributes(Product $product): void
    {
        $product->setAttribute('product_images', $product->images);
        $product->setAttribute('paper_stocks', $product->paperStocks ?? collect());
        $product->setAttribute('product_paper_stocks', $product->paperStocks ?? collect());
        $product->setAttribute('product_addons', $product->addons ?? collect());
        $product->setAttribute('addOns', $product->addons ?? collect());
        $product->setAttribute('product_colors', $product->colors ?? collect());
        $product->setAttribute('bulk_orders', $product->bulkOrders ?? collect());
        $product->setAttribute('product_bulk_orders', $product->bulkOrders ?? collect());
        $product->setAttribute('product_uploads', $product->uploads ?? collect());
    }
}