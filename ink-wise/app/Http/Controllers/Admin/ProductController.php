<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Template;
use App\Models\Material;
use App\Models\Ink;
use Illuminate\Http\Request;
use App\Models\ProductMaterial;
use App\Models\ProductInk;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Product::with(['materials', 'inks']);

        // Search
        if ($search = $request->query('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Sorting
        $allowedSorts = ['created_at','selling_price','quantity_ordered','name'];
        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order);

        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $products = $query->paginate($perPage)->appends($request->query());

        $totalProducts = \App\Models\Product::count();
        $totalQuantity = \App\Models\Product::sum('quantity_ordered');
    $totalSales = \App\Models\Product::sum(DB::raw('selling_price * quantity_ordered'));
        $activeProducts = \App\Models\Product::where('status', 'active')->count();
        $inactiveProducts = \App\Models\Product::where('status', 'inactive')->count();

        return view('admin.products.index', compact(
            'products',
            'totalProducts',
            'totalQuantity',
            'totalSales',
            'activeProducts',
            'inactiveProducts'
        ));
    }

    public function invitation()
    {
        // Sample order & material data (based on the info you gave)
        $sampleOrder = [
            'order_name' => 'Elegant Pearl Wedding Invitation',
            'qty' => 100
        ];

        $materials = [
            [
                'id' => 'cardstock',
                'name' => 'Pearl Metallic Cardstock – Champagne',
                'size' => 'A7',
                'weight' => '270 GSM',
                'unit_price' => 12.00,
                'qty_needed' => 100,
            ],
            [
                'id' => 'envelope',
                'name' => 'Metallic Envelope – Gold',
                'size' => 'A7',
                'unit_price' => 8.00,
                'qty_needed' => 100,
            ],
        ];

        $printing = [
            'ink_per_invite_ml' => 0.23,
            'cost_per_invite_ink' => 0.26, // given
        ];

        $foil = [
            'item' => 'Foil Roll – Gold',
            'usage_per_invite_cm' => 0.5,
            'roll_length_cm' => 3000,
            'roll_price' => 150.00
        ];

        $lamination = [
            'item' => 'Matte Lamination Film',
            'roll_price' => 500.00,
            'coverage_sheets' => 500,
            'usage_per_sheet' => 1
        ];

        // $products = Product::paginate(10); // Commented out for now
        $products = []; // Empty array for now

        // RETURN the admin.products.index view (was view('products.index'))
        return view('admin.products.index', compact('sampleOrder','materials','printing','foil','lamination', 'products'));
    }

    /**
     * Show all inks across products
     */
    public function inks()
    {
        $products = Product::with('inks')->orderBy('created_at', 'desc')->get();
        return view('admin.products.inks', compact('products'));
    }

    /**
     * Show all materials across products
     */
    public function materials()
    {
        $products = Product::with('materials')->orderBy('created_at', 'desc')->get();
        return view('admin.products.materials', compact('products'));
    }

    // Add: Method to show the create invitation form
    public function createInvitation(Request $request)
    {
        $templates = \App\Models\Template::all();
        $materials = \App\Models\Material::all();
        $selectedTemplate = null;
        if ($request->has('template_id')) {
            $selectedTemplate = \App\Models\Template::find($request->input('template_id'));
        }
        $templates = \App\Models\Template::all();
        return view('admin.products.create-invitation', compact('templates', 'materials', 'selectedTemplate'));
    }

    // Add: Method to handle form submission (placeholder, no DB yet)
    public function store(Request $request)
    {
        // Validate main product fields
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'template_id' => 'nullable|exists:templates,id',
            'invitationName' => 'required|string|max:255',
            'eventType' => 'required|string|max:255',
            'productType' => 'required|string|max:255',
            'themeStyle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.item' => 'nullable|string|max:255',
            'materials.*.unitPrice' => 'nullable|numeric',
            'materials.*.qty' => 'nullable|integer',
            'materials.*.cost' => 'nullable|numeric',
            'inks' => 'nullable|array',
            'inks.*.item' => 'nullable|string|max:255',
            'inks.*.usage' => 'nullable|numeric',
            'inks.*.costPerMl' => 'nullable|numeric',
            'inks.*.qty' => 'nullable|numeric',
            'inks.*.totalCost' => 'nullable|numeric',
        ]);

        // Create or update product
        if (!empty($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);
            $product->update([
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['invitationName'],
                'event_type' => $validated['eventType'],
                'product_type' => $validated['productType'],
                'theme_style' => $validated['themeStyle'],
                'description' => $validated['description'] ?? $request->input('description',''),
            ]);
        } else {
            $product = Product::create([
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['invitationName'],
                'event_type' => $validated['eventType'],
                'product_type' => $validated['productType'],
                'theme_style' => $validated['themeStyle'],
                'description' => $validated['description'] ?? $request->input('description',''),
            ]);
        }

        // Track existing ids to determine deletions
        $existingMaterialIds = $product->materials()->pluck('id')->toArray();
        $existingInkIds = $product->inks()->pluck('id')->toArray();

        $receivedMaterialIds = [];
        $receivedInkIds = [];

        // Handle Materials: update existing or create new
        if ($request->has('materials')) {
            foreach ($request->materials as $idx => $material) {
                // Support optional id if present for editing existing material
                $matId = $material['id'] ?? null;
                if (!empty($material['item'])) {
                    $data = [
                        'item'       => $material['item'],
                        'type'       => $material['type'] ?? null,
                        'color'      => $material['color'] ?? null,
                        'size'       => $material['size'] ?? null,
                        'weight'     => $material['weight'] ?? null,
                        'unit_price' => $material['unitPrice'] ?? ($material['unit_price'] ?? null),
                        'qty'        => $material['qty'] ?? null,
                        'cost'       => $material['cost'] ?? null,
                    ];

                    if ($matId) {
                        $pm = ProductMaterial::where('product_id', $product->id)->where('id', $matId)->first();
                        if ($pm) {
                            $pm->update($data);
                            $receivedMaterialIds[] = $pm->id;
                            continue;
                        }
                    }

                    $new = $product->materials()->create($data);
                    if ($new) $receivedMaterialIds[] = $new->id;
                }
            }
        }

        // Handle Inks: update existing or create new
        if ($request->has('inks')) {
            foreach ($request->inks as $idx => $ink) {
                $inkId = $ink['id'] ?? null;
                if (!empty($ink['item'])) {
                    $data = [
                        'item' => $ink['item'],
                        'type' => $ink['type'] ?? null,
                        'usage' => $ink['usage'] ?? null,
                        'qty' => $ink['qty'] ?? null,
                        'cost_per_ml' => $ink['costPerMl'] ?? ($ink['cost_per_ml'] ?? null),
                        'total_cost' => $ink['totalCost'] ?? ($ink['total_cost'] ?? null),
                    ];

                    if ($inkId) {
                        $pi = ProductInk::where('product_id', $product->id)->where('id', $inkId)->first();
                        if ($pi) {
                            $pi->update($data);
                            $receivedInkIds[] = $pi->id;
                            continue;
                        }
                    }

                    $new = $product->inks()->create($data);
                    if ($new) $receivedInkIds[] = $new->id;
                }
            }
        }

        // Delete removed materials/inks
        $toDeleteMaterials = array_diff($existingMaterialIds, $receivedMaterialIds);
        if (!empty($toDeleteMaterials)) {
            ProductMaterial::whereIn('id', $toDeleteMaterials)->delete();
        }

        $toDeleteInks = array_diff($existingInkIds, $receivedInkIds);
        if (!empty($toDeleteInks)) {
            ProductInk::whereIn('id', $toDeleteInks)->delete();
        }

        // Return JSON for AJAX or redirect for normal
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'product_id' => $product->id]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product saved!');
    }

    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    // Edit or show method
    public function edit($id)
    {
        $product = Product::with(['materials', 'inks'])->findOrFail($id);
        $templates = Template::all();
        $materials = Material::all();
        return view('admin.products.create-invitation', compact('product', 'templates', 'materials'));
    }

    // Show product (used by AJAX slide panel)
    public function show($id)
    {
        $product = Product::with(['materials', 'inks', 'template'])->findOrFail($id);

        // If request expects JSON or is AJAX, return the partial HTML for the slide panel
        if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.products.view', compact('product'));
        }

        // Otherwise render a simple page with the slide panel (use layout)
        return view('admin.products.view', compact('product'));
    }
}
