<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Template;
use App\Models\Material;
use App\Models\ProductUpload;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Product::query();

        // Search
        if ($search = $request->query('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%");
            });
        }

        // Status filtering removed - status column no longer exists

        // Sorting
        $allowedSorts = ['created_at','name'];
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
        $invitationCount = \App\Models\Product::where('product_type', 'Invitation')->count();
        $giveawayCount = \App\Models\Product::where('product_type', 'Giveaway')->count();
        $totalUploads = \App\Models\ProductUpload::count();
        $totalQuantity = 0; // Column removed from table
        $totalSales = 0; // Column removed from table
        $activeProducts = 0; // Status column removed from table
        $inactiveProducts = 0; // Status column removed from table

        return view('admin.products.index', compact(
            'products',
            'totalProducts',
            'invitationCount',
            'giveawayCount',
            'totalUploads',
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

        // Provide counts so the index view's summary cards render correctly even in this sample action
        $totalProducts = \App\Models\Product::count();
        $invitationCount = \App\Models\Product::where('product_type', 'Invitation')->count();
        $giveawayCount = \App\Models\Product::where('product_type', 'Giveaway')->count();
        $totalUploads = \App\Models\ProductUpload::count();

        // RETURN the admin.products.index view (was view('products.index'))
        return view('admin.products.index', compact(
            'sampleOrder','materials','printing','foil','lamination', 'products',
            'totalProducts','invitationCount','giveawayCount','totalUploads'
        ));
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
            'minOrderQtyCustomization' => 'nullable|integer|min:1',
            'leadTime' => 'nullable|string|max:255',
            'stockAvailability' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'materials' => 'nullable|array',
            'materials.*.type' => 'nullable|string|max:255',
            'materials.*.item' => 'nullable|string|max:255',
            'materials.*.color' => 'nullable|string|max:255',
            'materials.*.size' => 'nullable|string|max:255',
            'materials.*.weight' => 'nullable|integer',
            'materials.*.unitPrice' => 'nullable|numeric',
        ]);

        // Get the first material (if any) to store directly in products table
        $material = $request->input('materials.0', []);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Custom image uploaded
            $imagePath = $request->file('image')->store('products', 'public');
        } elseif ($request->input('template_id')) {
            // No custom image, use template image
            $template = \App\Models\Template::find($request->input('template_id'));
            if ($template && $template->preview) {
                // Copy template image to products directory
                $templatePath = $template->preview;
                if (!preg_match('/^(https?:)?\/\//i', $templatePath) && !str_starts_with($templatePath, '/')) {
                    // It's a relative path in storage
                    $sourcePath = storage_path('app/public/' . $templatePath);
                    if (file_exists($sourcePath)) {
                        $filename = 'products/template_' . $template->id . '_' . time() . '.' . pathinfo($sourcePath, PATHINFO_EXTENSION);
                        $destinationPath = storage_path('app/public/' . $filename);
                        if (copy($sourcePath, $destinationPath)) {
                            $imagePath = $filename;
                        }
                    }
                }
            }
        }

        // Create or update product
        if (!empty($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);
            $updateData = [
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['invitationName'],
                'event_type' => $validated['eventType'],
                'product_type' => $validated['productType'],
                'theme_style' => $validated['themeStyle'],
                'description' => $validated['description'] ?? $request->input('description',''),
                'min_order_qty' => $validated['minOrderQtyCustomization'] ?? null,
                'lead_time' => $validated['leadTime'] ?? null,
                'stock_availability' => $validated['stockAvailability'] ?? null,
                'type' => $material['type'] ?? null,
                'item' => $material['item'] ?? null,
                'color' => $material['color'] ?? null,
                'size' => $material['size'] ?? null,
                'weight' => $material['weight'] ?? null,
                'unit_price' => $material['unitPrice'] ?? null,
            ];
            
            // Only update image if a new one was uploaded
            if ($imagePath) {
                $updateData['image'] = $imagePath;
            }
            
            $product->update($updateData);
        } else {
            $product = Product::create([
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['invitationName'],
                'event_type' => $validated['eventType'],
                'product_type' => $validated['productType'],
                'theme_style' => $validated['themeStyle'],
                'description' => $validated['description'] ?? $request->input('description',''),
                'image' => $imagePath,
                'min_order_qty' => $validated['minOrderQtyCustomization'] ?? null,
                'lead_time' => $validated['leadTime'] ?? null,
                'stock_availability' => $validated['stockAvailability'] ?? null,
                'type' => $material['type'] ?? null,
                'item' => $material['item'] ?? null,
                'color' => $material['color'] ?? null,
                'size' => $material['size'] ?? null,
                'weight' => $material['weight'] ?? null,
                'unit_price' => $material['unitPrice'] ?? null,
            ]);
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
        $product = Product::findOrFail($id);
        $templates = Template::all();
        $materials = Material::all();
        return view('admin.products.create-invitation', compact('product', 'templates', 'materials'));
    }

    // Show product (used by AJAX slide panel)
    public function view($id)
    {
        $product = Product::with(['template'])->findOrFail($id);

        // If request expects JSON or is AJAX, return the partial HTML for the slide panel
        if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.products.view', compact('product'));
        }

        // Otherwise render a simple page with the slide panel (use layout)
        return view('admin.products.view', compact('product'));
    }

    public function upload(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/products/' . $id, $filename, 'public');

            // Save to database
            $upload = ProductUpload::create([
                'product_id' => $product->id,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'upload' => $upload
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }

    public function weddinginvite($id)
    {
        $product = Product::with('uploads')->findOrFail($id);
        // Reuse the customer-facing wedding invite template and supply a products collection
        $products = collect([$product]);
        return view('customer.Invitations.weddinginvite', compact('products'));
    }
}
