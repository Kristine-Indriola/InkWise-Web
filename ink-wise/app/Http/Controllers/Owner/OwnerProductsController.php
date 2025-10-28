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

        if (($type = trim((string) $request->query('type'))) !== '') {
            $normalizedType = Str::lower($type);
            $typeVariants = collect([$normalizedType])
                ->merge(Str::endsWith($normalizedType, 's')
                    ? [rtrim($normalizedType, 's')]
                    : [$normalizedType . 's'])
                ->unique()
                ->all();

            $query->where(function ($builder) use ($typeVariants) {
                foreach ($typeVariants as $index => $value) {
                    $clause = "LOWER(COALESCE(product_type, '')) = ?";
                    if ($index === 0) {
                        $builder->whereRaw($clause, [$value]);
                    } else {
                        $builder->orWhereRaw($clause, [$value]);
                    }
                }

                $builder->orWhereHas('template', function ($relation) use ($typeVariants) {
                    foreach ($typeVariants as $index => $value) {
                        $clause = "LOWER(COALESCE(product_type, '')) = ?";
                        if ($index === 0) {
                            $relation->whereRaw($clause, [$value]);
                        } else {
                            $relation->orWhereRaw($clause, [$value]);
                        }
                    }
                });
            });
        }

        if (($search = trim((string) $request->query('search'))) !== '') {
            $keyword = '%' . Str::lower($search) . '%';
            $numeric = preg_replace('/\D+/', '', $search);

            $query->where(function ($builder) use ($keyword, $numeric) {
                $builder->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(event_type, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(product_type, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(theme_style, '')) LIKE ?", [$keyword]);

                if ($numeric !== '') {
                    $builder->orWhere('id', (int) $numeric);
                    $builder->orWhereRaw('LOWER(CAST(id AS CHAR)) LIKE ?', ['%' . $numeric . '%']);
                }

                $builder->orWhereHas('template', function ($relation) use ($keyword) {
                    $relation->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(event_type, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(product_type, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(theme_style, '')) LIKE ?", [$keyword]);
                });

                $builder->orWhereHas('envelope.material', function ($relation) use ($keyword) {
                    $relation->whereRaw("LOWER(COALESCE(material_name, '')) LIKE ?", [$keyword]);
                });

                $builder->orWhereHas('colors', function ($relation) use ($keyword) {
                    $relation->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(color_code, '')) LIKE ?", [$keyword]);
                });
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