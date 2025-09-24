<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Template;
use App\Models\Material;
use App\Models\Ink;
use Illuminate\Http\Request;
use App\Models\ProductMaterial;
use App\Models\ProductInk;

class ProductController extends Controller
{
    public function index()
    {
        $products = \App\Models\Product::with(['materials', 'inks'])->orderBy('created_at', 'desc')->paginate(10);

        $totalProducts = \App\Models\Product::count();
        $totalQuantity = \App\Models\Product::sum('quantity_ordered');
        $totalSales = \App\Models\Product::sum(\DB::raw('selling_price * quantity_ordered'));
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
            'template_id' => 'nullable|exists:templates,id',
            'invitationName' => 'required|string|max:255',
            'eventType' => 'required|string|max:255',
            'productType' => 'required|string|max:255',
            'themeStyle' => 'required|string|max:255',
            // ...other fields...
        ]);

        // Save the product
        $product = Product::create([
            'template_id' => $validated['template_id'] ?? null,
            'name' => $validated['invitationName'],
            'event_type' => $validated['eventType'],
            'product_type' => $validated['productType'],
            'theme_style' => $validated['themeStyle'],
            'description' => $request->input('description', ''),
            // ...other fields...
        ]);

        // Save Materials
        if ($request->has('materials')) {
            foreach ($request->materials as $material) {
                if (!empty($material['item'])) {
                    $product->materials()->create([
                        'item'       => $material['item'],
                        'type'       => $material['type'] ?? null,
                        'color'      => $material['color'] ?? null,
                        'size'       => $material['size'] ?? null,
                        'weight'     => $material['weight'] ?? null,
                        'unit_price' => $material['unitPrice'] ?? null,
                        'qty'        => $material['qty'] ?? null,
                        'cost'       => $material['cost'] ?? null,
                    ]);
                }
            }
        }

        // Save Inks
        if ($request->has('inks')) {
            foreach ($request->inks as $ink) {
                if (!empty($ink['item'])) {
                    $product->inks()->create([
                        'item'        => $ink['item'],
                        'type'        => $ink['type'] ?? null,
                        'usage'       => $ink['usage'] ?? null,
                        'cost_per_ml' => $ink['costPerMl'] ?? null,
                        'total_cost'  => $ink['totalCost'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created!');
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
}
