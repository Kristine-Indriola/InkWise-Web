<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Template;
use App\Models\Material;
use App\Models\ProductEnvelope;
use App\Models\ProductUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['template', 'images', 'envelope.material', 'materials.material', 'uploads']);
            // Removed ->published() to show all products, published and unpublished

        $currentFilter = 'All';
        $typeFilter = $request->query('type');
        if ($typeFilter && in_array($typeFilter, ['Invitation', 'Giveaway', 'Envelope'], true)) {
            $query->where('product_type', $typeFilter);
            $currentFilter = $typeFilter;
        }

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
        $products->getCollection()->each(function (Product $product) {
            $this->hydrateLegacyAttributes($product);
        });

        // Calculate summary counts
        $invitationCount = Product::whereRaw('LOWER(product_type) = ?', ['invitation'])->count();
        $giveawayCount = Product::whereRaw('LOWER(product_type) = ?', ['giveaway'])->count();
        $envelopeCount = Product::whereRaw('LOWER(product_type) = ?', ['envelope'])->count();
        $totalUploads = Template::count();
        $uploadedTemplatesCount = Template::has('products')->count();

        return view('admin.products.index', [
            'products' => $products,
            'currentFilter' => $currentFilter,
            'sort' => $sort,
            'order' => $order,
            'perPage' => $perPage,
            'invitationCount' => $invitationCount,
            'giveawayCount' => $giveawayCount,
            'envelopeCount' => $envelopeCount,
            'totalUploads' => $totalUploads,
            'uploadedTemplatesCount' => $uploadedTemplatesCount,
        ]);
    }

    public function getEnvelopes()
    {
        $products = Product::query()
            ->with(['envelope.material', 'images', 'template'])
            ->where(function ($query) {
                $query->whereRaw('LOWER(product_type) = ?', ['envelope'])
                    ->orWhereRaw('LOWER(product_type) like ?', ['%envelope%']);
            })
            ->orderByDesc('updated_at')
            ->get();

        $envelopes = $products->map(function (Product $product) {
            $envelope = $product->envelope;
            $price = $envelope->price_per_unit
                ?? $product->base_price
                ?? $product->unit_price
                ?? 0;

            $imageCandidates = [
                optional($envelope)->envelope_image,
                optional($product->images)->front,
                optional($product->images)->preview,
                $product->image,
                optional($product->template)->preview_front,
                optional($product->template)->image,
            ];

            $image = collect($imageCandidates)
                ->filter()
                ->map(function ($path) {
                    if (!$path) {
                        return null;
                    }
                    if (preg_match('/^(https?:)?\/\//i', $path) || Str::startsWith($path, '/')) {
                        return $path;
                    }
                    return Storage::url($path);
                })
                ->first() ?? asset('images/no-image.png');

                // Ensure returned image is an absolute URL
                if ($image && !preg_match('/^(https?:)?\/\//i', $image)) {
                    $image = asset(ltrim($image, '/'));
                }

                return [
                    'id' => 'env_' . $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $price,
                    'image' => $image,
                    'material' => optional($envelope->material)->material_name
                        ?? optional($envelope)->envelope_material_name
                        ?? null,
                    'min_qty' => 10, // Default minimum quantity
                    'max_qty' => optional($envelope)->max_quantity ?? optional($envelope)->max_qty,
                ];
        })->values();

        return response()->json($envelopes);
    }

    public function invitation(Request $request)
    {
        $queryParams = array_merge($request->query(), ['type' => 'Invitation']);
        return redirect()->route('admin.products.index', $queryParams);
    }

    /**
     * Show all inks across products
     */
    public function inks()
    {
        $products = Product::with('inks', 'envelope.material')->orderBy('created_at', 'desc')->get();
        return view('admin.products.inks', compact('products'));
    }

    /**
     * Show all materials across products
     */
    public function materials()
    {
        $products = Product::with('materials', 'envelope.material')->orderBy('created_at', 'desc')->get();
        return view('admin.products.materials', compact('products'));
    }

    // Add: Method to show the create invitation form
    public function createInvitation(Request $request)
    {
        $materials = Material::all();
        $selectedTemplate = null;

        if ($request->has('template_id')) {
            $selectedTemplate = Template::find($request->input('template_id'));
        }

        // Load invitation templates regardless of status so the admin can pick any available template
        $templatesQuery = Template::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(product_type) = ?', ['invitation'])
                    ->orWhereNull('product_type');
            })
            ->orderByDesc('updated_at');

        $templates = $templatesQuery->get();

        if ($templates->isEmpty()) {
            $templates = Template::orderByDesc('updated_at')->get();
        }

        if ($selectedTemplate && $templates->where('id', $selectedTemplate->id)->isEmpty()) {
            $templates->push($selectedTemplate);
        }

        return view('admin.products.create-invitation', compact('templates', 'materials', 'selectedTemplate'));
    }

    // Get template data by ID for auto-populating form
    public function getTemplateData($id)
    {
        $template = Template::findOrFail($id);

        $placeholder = \App\Support\ImageResolver::url(null);

        $design = $template->design ?? [];
        $metadata = $template->metadata ?? [];
        $pages = is_array(data_get($design, 'pages')) ? data_get($design, 'pages', []) : [];

        $collectPageSources = function ($page) {
            return [
                data_get($page, 'preview'),
                data_get($page, 'preview_url'),
                data_get($page, 'previewUrl'),
                data_get($page, 'thumbnail'),
                data_get($page, 'thumbnail_url'),
                data_get($page, 'thumbnailUrl'),
                data_get($page, 'image'),
                data_get($page, 'image_url'),
                data_get($page, 'imageUrl'),
            ];
        };

        $frontCandidates = [
            $template->front_image,
            data_get($template, 'preview_front'),
            data_get($metadata, 'front_image'),
            data_get($metadata, 'preview_front'),
            data_get($design, 'preview_front'),
            data_get($design, 'front_image'),
            data_get($design, 'front.preview'),
            $template->preview,
        ];

        $backCandidates = [
            $template->back_image,
            data_get($template, 'preview_back'),
            data_get($metadata, 'back_image'),
            data_get($metadata, 'preview_back'),
            data_get($design, 'preview_back'),
            data_get($design, 'back_image'),
            data_get($design, 'back.preview'),
        ];

        foreach ($pages as $index => $page) {
            $side = strtolower((string) data_get($page, 'side', ''));
            $sources = $collectPageSources($page);

            if ($side === 'front' || ($side === '' && $index === 0)) {
                $frontCandidates = array_merge($frontCandidates, $sources);
            }

            if ($side === 'back' || ($side === '' && $index === 1)) {
                $backCandidates = array_merge($backCandidates, $sources);
            }
        }

        $resolveAsset = function (array $candidates) use ($placeholder) {
            foreach ($candidates as $candidate) {
                if (!$candidate) {
                    continue;
                }

                if (is_string($candidate) && Str::startsWith(trim($candidate), 'data:image')) {
                    return $candidate;
                }

                $url = \App\Support\ImageResolver::url($candidate);
                if ($url && $url !== $placeholder) {
                    return $url;
                }
            }

            return null;
        };

        $frontUrl = $resolveAsset($frontCandidates);
        $backUrl = $resolveAsset($backCandidates);
        $previewUrl = $resolveAsset([
            $template->preview,
            data_get($design, 'preview'),
            data_get($metadata, 'preview'),
            $frontUrl,
        ]);

        return response()->json([
            'template_name' => $template->name,
            'name' => $template->name,
            'description' => $template->description,
            'product_type' => $template->product_type,
            'event_type' => $template->event_type,
            'theme_style' => $template->theme_style,
            'front_image' => $frontUrl,
            'back_image' => $backUrl,
            'front_preview' => $frontUrl,
            'back_preview' => $backUrl,
            'preview_image' => $previewUrl,
            'design_data' => $template->design,
        ]);
    }

    public function createGiveaway(Request $request)
    {
        $product = null;
        if ($request->has('product_id')) {
            $product = Product::with(['template', 'addons', 'colors', 'envelope.material'])->find($request->input('product_id'));
            if ($product) {
                $product->setRelation('bulkOrders', collect());
            }
        }

        $templates = Template::where('product_type', 'Giveaway')->get();
        $materials = Material::whereIn('product_type', ['Giveaway', 'Souvenir'])->where('stock_qty', '>', 0)->get();
        $selectedTemplate = null;
        if ($request->has('template_id')) {
            $selectedTemplate = Template::find($request->input('template_id'));
        }

        return view('admin.products.create-giveaways', compact('product', 'templates', 'materials', 'selectedTemplate'));
    }

    // Add: Method to show the create envelope form
    public function createEnvelope(Request $request)
    {
        $templates = \App\Models\Template::where('product_type', 'Envelope')->get();
        $materials = \App\Models\Material::all();
        $materialTypes = \App\Models\Material::distinct()->pluck('material_type')->filter()->values();
        $envelopeMaterials = \App\Models\Material::where('material_type', 'like', '%envelope%')
            ->orWhere('material_type', 'like', '%paper%')
            ->orWhere('material_name', 'like', '%envelope%')
            ->get();
        $selectedTemplate = null;
        $product = null;
        $envelope = null;

        if ($request->has('template_id')) {
            $selectedTemplate = \App\Models\Template::find($request->input('template_id'));
        }

        // Check if editing an existing envelope product
        if ($request->has('product_id')) {
            $product = Product::with('envelope.material')->find($request->input('product_id'));
            if ($product && $product->envelope) {
                $envelope = $product->envelope;
            }
        }

        return view('admin.products.create-envelope', compact('templates', 'materials', 'materialTypes', 'envelopeMaterials', 'selectedTemplate', 'product', 'envelope'));
    }

    // Add: Method to handle form submission (placeholder, no DB yet)
    public function store(Request $request)
    {
        // Validate main product fields
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'template_id' => 'nullable|exists:templates,id',
            'invitationName' => 'required|string|max:255',
            'eventType' => 'nullable|string|max:255',
            'productType' => 'required|string|max:255',
            'themeStyle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'nullable|numeric',
            'lead_time' => 'nullable|string|max:255',
            'date_available' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            // Envelope specific fields
            'material_type' => 'nullable|string|max:255',
            'envelope_material_id' => 'nullable|exists:materials,material_id',
            'max_qty' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'price_per_unit' => 'nullable|numeric|min:0',
            'envelope_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            // Repeatable product fields (for invitations)
            'paper_stocks' => 'nullable|array',
            'paper_stocks.*.material_id' => 'nullable|exists:materials,material_id',
            'paper_stocks.*.name' => 'nullable|string|max:255',
            'paper_stocks.*.price' => 'nullable|numeric',
            'paper_stocks.*.image_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:5120',

            'addons' => 'nullable|array',
            'addons.*.addon_type' => 'nullable|string|max:100',
            'addons.*.name' => 'nullable|string|max:255',
            'addons.*.price' => 'nullable|numeric',
            'addons.*.image_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:5120',

            'average_usage_ml' => 'nullable|numeric',

            'preview_images' => 'nullable|array',
            'preview_images.front' => 'nullable',
            'preview_images.back' => 'nullable',
            'preview_images.preview' => 'nullable',
            'preview_images_existing' => 'nullable|array',
            'preview_images_existing.front' => 'nullable|string',
            'preview_images_existing.back' => 'nullable|string',
            'preview_images_existing.preview' => 'nullable|string',
        ]);

        $resolveTemplatePreviews = function (?Template $template): array {
            if (!$template) {
                return [
                    'front' => null,
                    'back' => null,
                    'preview' => null,
                ];
            }

            $placeholder = \App\Support\ImageResolver::url(null);

            $resolveCandidate = function ($candidates) use ($placeholder) {
                foreach ((array) $candidates as $candidate) {
                    if (!$candidate) {
                        continue;
                    }

                    if (is_string($candidate) && Str::startsWith(trim($candidate), 'data:image')) {
                        return $candidate;
                    }

                    $url = \App\Support\ImageResolver::url($candidate);
                    if ($url && $url !== $placeholder) {
                        return $url;
                    }
                }

                return null;
            };

            $design = $template->design ?? [];
            $metadata = $template->metadata ?? [];
            $pages = is_array(data_get($design, 'pages')) ? data_get($design, 'pages', []) : [];

            $collectPageSources = function ($page) {
                return [
                    data_get($page, 'preview'),
                    data_get($page, 'preview_url'),
                    data_get($page, 'previewUrl'),
                    data_get($page, 'thumbnail'),
                    data_get($page, 'thumbnail_url'),
                    data_get($page, 'thumbnailUrl'),
                    data_get($page, 'image'),
                    data_get($page, 'image_url'),
                    data_get($page, 'imageUrl'),
                ];
            };

            $frontCandidates = [
                $template->front_image,
                data_get($template, 'preview_front'),
                data_get($metadata, 'front_image'),
                data_get($metadata, 'preview_front'),
                data_get($design, 'front_image'),
                data_get($design, 'preview_front'),
                data_get($design, 'front.preview'),
                $template->preview,
                $template->image,
            ];

            $backCandidates = [
                $template->back_image,
                data_get($template, 'preview_back'),
                data_get($metadata, 'back_image'),
                data_get($metadata, 'preview_back'),
                data_get($design, 'back_image'),
                data_get($design, 'preview_back'),
                data_get($design, 'back.preview'),
            ];

            foreach ($pages as $index => $page) {
                $side = strtolower((string) data_get($page, 'side', ''));
                $sources = $collectPageSources($page);

                if ($side === 'front' || ($side === '' && $index === 0)) {
                    $frontCandidates = array_merge($frontCandidates, $sources);
                }

                if ($side === 'back' || ($side === '' && $index === 1)) {
                    $backCandidates = array_merge($backCandidates, $sources);
                }
            }

            $front = $resolveCandidate($frontCandidates);
            $back = $resolveCandidate($backCandidates);
            $preview = $resolveCandidate([
                $template->preview,
                data_get($design, 'preview'),
                data_get($metadata, 'preview'),
                $front,
            ]);

            return [
                'front' => $front,
                'back' => $back,
                'preview' => $preview ?? $front,
            ];
        };

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Custom image uploaded
            $imagePath = $request->file('image')->store('products', 'public');
        } elseif ($request->input('template_id')) {
            // No custom image, use template image from Template
            $template = Template::find($request->input('template_id'));
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

        // Create or update product and related records inside a transaction
        $product = null;
        $previousTemplateId = null;
        DB::transaction(function() use ($request, $validated, $imagePath, &$product, &$previousTemplateId, $resolveTemplatePreviews) {
            $leadTimeInput = $validated['lead_time'] ?? null;
            $leadTimeDays = is_numeric($leadTimeInput) ? (int) $leadTimeInput : null;

            // Get the actual template_id from Template if provided
            $actualTemplateId = $validated['template_id'] ?? null;

            $data = [
                'template_id' => $actualTemplateId,
                'name' => $validated['invitationName'],
                'event_type' => $validated['eventType'] ?? 'General',
                'product_type' => $validated['productType'],
                'theme_style' => $validated['themeStyle'] ?? null,
                'description' => $validated['description'] ?? $request->input('description',''),
                // ensure the three fields requested are persisted on the product
                'base_price' => isset($validated['base_price']) ? floatval($validated['base_price']) : null,
                'lead_time' => $leadTimeInput,
                'lead_time_days' => $leadTimeDays,
                'date_available' => !empty($validated['date_available']) ? $validated['date_available'] : null,
            ];

            if (!empty($validated['product_id'])) {
                $product = Product::findOrFail($validated['product_id']);
                $previousTemplateId = $product->template_id;
                if ($imagePath) {
                    $data['image'] = $imagePath;
                }
                $product->update($data);
            } else {
                $data['image'] = $imagePath;
                $product = Product::create($data);
            }

            // Get template data if template_id is provided
            $templateData = null;
            $template = null;
            if (!empty($validated['template_id'])) {
                $template = Template::find($validated['template_id']);
                if ($template && $template->design) {
                    $templateData = $template->design;
                }
            }

            // Handle envelope/giveaway materials
            if ($product && ($validated['productType'] === 'Envelope' || $validated['productType'] === 'Giveaway')) {
                // For envelopes, create/update envelope record
                if ($validated['productType'] === 'Envelope') {
                    $envelopeData = [
                        'material_id' => $validated['envelope_material_id'] ?? null,
                        'envelope_material_name' => $request->input('material_type') ?? null,
                        'max_qty' => $validated['max_qty'] ?? null,
                        'max_quantity' => $validated['max_quantity'] ?? null,
                        'price_per_unit' => $validated['price_per_unit'] ?? null,
                    ];

                    $product->envelope()->updateOrCreate([], $envelopeData);
                }
                // For giveaways, create ProductMaterial record
                elseif ($validated['productType'] === 'Giveaway' && !empty($validated['envelope_material_id'])) {
                    // Delete existing materials for this product
                    $product->materials()->delete();

                    // Create new material record
                    $product->materials()->create([
                        'material_id' => $validated['envelope_material_id'],
                        'item' => 'giveaway',
                        'type' => $request->input('material_type') ?? 'giveaway',
                        'qty' => 1,
                        'source_type' => 'product',
                    ]);
                }
            }
            // Paper Stocks
            $paperStocks = $request->input('paper_stocks', []);
            $paperStockFiles = $request->file('paper_stocks') ?? [];

            // If no paper stocks provided but we have template data, use template data
            if (empty($paperStocks) && $templateData && isset($templateData['paper_stocks'])) {
                $paperStocks = $templateData['paper_stocks'];
            }

            if ($product) {
                $product->paperStocks()->delete();
                foreach ($paperStocks as $i => $ps) {
                    $psImagePath = null;
                    if (isset($paperStockFiles[$i]) && is_array($paperStockFiles[$i]) && isset($paperStockFiles[$i]['image_path'])) {
                        $file = $paperStockFiles[$i]['image_path'];
                        if ($file && method_exists($file, 'store')) {
                            $psImagePath = $file->store('Materials/paper_stocks', 'public');
                        }
                    }
                    $product->paperStocks()->create([
                        'material_id' => !empty($ps['material_id']) ? intval($ps['material_id']) : null,
                        'name' => $ps['name'] ?? null,
                        'price' => isset($ps['price']) ? floatval($ps['price']) : null,
                        'image_path' => $psImagePath,
                    ]);
                }

                // IMPORTANT: Link paper stocks to ProductMaterial for inventory deduction
                // This ensures materials are deducted when orders are placed for Invitations
                if ($validated['productType'] === 'Invitation') {
                    // Delete existing product-level material links (not order-specific ones)
                    $product->materials()->whereNull('order_id')->delete();

                    // Re-get the paper stocks we just created
                    $createdPaperStocks = $product->paperStocks()->with('material')->get();
                    
                    foreach ($createdPaperStocks as $paperStock) {
                        if ($paperStock->material_id) {
                            $product->materials()->create([
                                'material_id' => $paperStock->material_id,
                                'item' => $paperStock->name ?? 'paper_stock',
                                'type' => 'paper_stock',
                                'qty' => 1, // 1 paper stock per invitation
                                'source_type' => 'product',
                                'quantity_mode' => 'per_unit', // Deduct 1 per invitation ordered
                            ]);
                        }
                    }
                }
            }

            // Addons
            $addons = $request->input('addons', []);
            $addonFiles = $request->file('addons') ?? [];

            // If no addons provided but we have template data, use template data
            if (empty($addons) && $templateData && isset($templateData['addons'])) {
                $addons = $templateData['addons'];
            }

            if ($product) {
                $product->addons()->delete();
                foreach ($addons as $i => $ad) {
                    $adImagePath = null;
                    if (isset($addonFiles[$i]) && is_array($addonFiles[$i]) && isset($addonFiles[$i]['image_path'])) {
                        $file = $addonFiles[$i]['image_path'];
                        if ($file && method_exists($file, 'store')) {
                            $adImagePath = $file->store('Materials/addons', 'public');
                        }
                    }
                    $product->addons()->create([
                        'addon_type' => $ad['addon_type'] ?? null,
                        'name' => $ad['name'] ?? null,
                        'price' => isset($ad['price']) ? floatval($ad['price']) : null,
                        'image_path' => $adImagePath,
                    ]);
                }
            }

            // Average usage (stored via product_colors for backward compatibility)
            $averageUsage = $request->input('average_usage_ml');

            if ($product) {
                $product->colors()->delete();

                if ($averageUsage !== null && $averageUsage !== '') {
                    $product->colors()->create([
                        'average_usage_ml' => (float) $averageUsage,
                    ]);
                }
            }

            // Preview Images (front/back/preview)
            if ($product) {
                $existingPreview = $product->images;
                $previewInputs = $request->input('preview_images', []);
                $previewFiles = $request->file('preview_images') ?? [];
                $existingPreviewInputs = $request->input('preview_images_existing', []);

                $previewData = [
                    'front' => $existingPreview ? $existingPreview->front : null,
                    'back' => $existingPreview ? $existingPreview->back : null,
                    'preview' => $existingPreview ? $existingPreview->preview : null,
                ];

                foreach (['front', 'back', 'preview'] as $key) {
                    $existingValue = is_array($existingPreviewInputs) ? ($existingPreviewInputs[$key] ?? null) : null;
                    if ($existingValue && empty($previewData[$key])) {
                        $previewData[$key] = $existingValue;
                    }
                }

                foreach (['front', 'back', 'preview'] as $key) {
                    $file = is_array($previewFiles) ? ($previewFiles[$key] ?? null) : null;
                    if ($file && method_exists($file, 'store')) {
                        $previewData[$key] = $file->store('products/previews', 'public');
                        continue;
                    }

                    $value = is_array($previewInputs) ? ($previewInputs[$key] ?? null) : null;
                    if (!empty($value)) {
                        $previewData[$key] = $value;
                    }
                }

                // If no preview images provided but we have template data, use template images
                if (empty(array_filter($previewData)) && $template) {
                    $previewData = $resolveTemplatePreviews($template);
                }

                $hasPreviewValues = array_filter($previewData, fn ($value) => !empty($value));

                if ($hasPreviewValues || $existingPreview) {
                    $product->images()->updateOrCreate([], $previewData);
                }
            }

            if ($template) {
                $template->forceFill([
                    'status' => 'assigned',
                    'status_note' => null,
                    'status_updated_at' => now(),
                ])->save();
            }

            if ($previousTemplateId && $product && $previousTemplateId !== $product->template_id) {
                $previousTemplate = Template::find($previousTemplateId);
                if ($previousTemplate) {
                    $stillAssigned = Product::published()
                        ->where('template_id', $previousTemplateId)
                        ->where('id', '!=', $product->id)
                        ->exists();

                    if (!$stillAssigned) {
                        $previousTemplate->forceFill([
                            'status' => 'uploaded',
                            'status_note' => null,
                            'status_updated_at' => now(),
                        ])->save();
                    }
                }
            }
        });

        // Return JSON for AJAX or redirect for normal
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'product_id' => $product ? $product->id : null]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product saved!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $template = $product->template;
        $productName = $product->name;

        if ($template) {
            $stillAssigned = Product::published()
                ->where('template_id', $template->id)
                ->where('id', '!=', $product->id)
                ->exists();

            if (!$stillAssigned) {
                $template->forceFill([
                    'status' => 'uploaded',
                    'status_note' => null,
                    'status_updated_at' => now(),
                ])->save();
            }
        }

        $product->delete();

        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product "' . $productName . '" deleted successfully.'
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    // Edit or show method
    public function edit($id)
    {
        $product = Product::with([
            'template',
            'uploads',
            'images',
            'paperStocks',
            'addons',
            'colors',
            'materials',
            'envelope.material'
        ])->findOrFail($id);
                $this->hydrateLegacyAttributes($product);
                $templates = Template::all();
                $materials = Material::all();
                // Distinct material types for selector
                $materialTypes = Material::distinct()->pluck('material_type')->filter()->values();
                // Envelope-specific materials
                $envelopeMaterials = Material::where(function ($q) {
                        $q->where('material_type', 'like', '%envelope%')
                            ->orWhere('material_type', 'like', '%paper%')
                            ->orWhere('material_name', 'like', '%envelope%');
                })->get();

                // Return the dedicated edit view (single-container form)
                return view('admin.products.edit', compact('product', 'templates', 'materials', 'materialTypes', 'envelopeMaterials'));
    }

    public function show($id)
    {
        return $this->view($id);
    }

    // Show product (used by AJAX slide panel)
    public function view($id)
    {
        $product = Product::with([
            'template',
            'uploads',
            'images',
            'paperStocks',
            'addons',
            'colors',
            'materials',
            'envelope.material'
        ])->findOrFail($id);
        $this->hydrateLegacyAttributes($product);

        // Load ratings for this product
        $product->ratings = \App\Models\OrderRating::whereHas('order.items', function($query) use ($id) {
            $query->where('product_id', $id);
        })->with('customer')->get();

        // If request expects JSON or is AJAX, return the partial HTML for the slide panel
        if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.products.view', compact('product'));
        }

        // Otherwise render a simple page with the slide panel (use layout)
        return view('admin.products.view', compact('product'));
    }

    protected function hydrateLegacyAttributes(Product $product): void
    {
        $product->setAttribute('product_images', $product->images);
        $product->setAttribute('paper_stocks', $product->paperStocks);
        $product->setAttribute('product_paper_stocks', $product->paperStocks);
        $product->setAttribute('product_addons', $product->addons);
        $product->setAttribute('addOns', $product->addons);
        $product->setAttribute('product_colors', $product->colors);
        $product->setAttribute('product_uploads', $product->uploads);
        $product->setAttribute('envelope', $product->envelope);
    }

    public function upload(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check if this is a publish request (no file) or actual file upload
        if (!$request->hasFile('file')) {
            // This is a publish request - create a ProductUpload record to mark as published
            $upload = ProductUpload::create([
                'product_id' => $product->id,
                'template_id' => $product->template_id,
                'template_name' => $product->name,
                'description' => $product->description,
                'product_type' => $product->product_type,
                'event_type' => $product->event_type,
                'theme_style' => $product->theme_style,
                'front_image' => $product->images ? $product->images->front : null,
                'back_image' => $product->images ? $product->images->back : null,
                'preview_image' => $product->images ? $product->images->preview : ($product->image ?: null),
                'design_data' => null, // Could populate from product data if needed
            ]);

            $product->forceFill([
                'published_at' => now(),
                'unpublished_reason' => null,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Template published to customer pages successfully.',
                'upload' => $upload,
                'published' => true
            ]);
        }

        // Original file upload logic
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();

            // For envelope products, store in envelopes directory and update envelope_image
            if ($product->product_type === 'Envelope') {
                $path = $file->storeAs('envelopes', $filename, 'public');

                // Update the envelope record with the new image
                if ($product->envelope) {
                    $product->envelope->update(['envelope_image' => $path]);
                } else {
                    // Create envelope record if it doesn't exist
                    $product->envelope()->create(['envelope_image' => $path]);
                }
            } else {
                // For other products, use the regular upload path
                $path = $file->storeAs('uploads/products/' . $id, $filename, 'public');
            }

            // Save to database (ProductUpload table for tracking)
            $upload = ProductUpload::create([
                'product_id' => $product->id,
                'template_id' => $product->template_id,
                'template_name' => $product->name,
                'description' => $product->description,
                'product_type' => $product->product_type,
                'event_type' => $product->event_type,
                'theme_style' => $product->theme_style,
                'front_image' => $product->images ? $product->images->front : null,
                'back_image' => $product->images ? $product->images->back : null,
                'preview_image' => $product->images ? $product->images->preview : ($product->image ?: null),
                'design_data' => null,
            ]);

            $product->forceFill([
                'published_at' => now(),
                'unpublished_reason' => null,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'upload' => $upload,
                'is_envelope' => $product->product_type === 'Envelope',
                'envelope_image_url' => $product->product_type === 'Envelope' ? \Illuminate\Support\Facades\Storage::url($path) : null
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }

    public function unupload(Request $request, $id)
    {
        $product = Product::with(['template', 'uploads'])->findOrFail($id);
        $reason = trim((string) $request->input('reason', ''));

        DB::transaction(function () use ($product, $reason) {
            $product->uploads()->delete();

            $product->forceFill([
                'published_at' => null,
                'unpublished_reason' => $reason !== '' ? $reason : null,
            ])->save();

            if ($product->template) {
                $product->template->forceFill([
                    'status' => 'uploaded',
                    'status_note' => null,
                    'status_updated_at' => now(),
                ])->save();
            }
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product unpublished successfully.',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product unpublished successfully.');
    }

    public function weddinginvite($id)
    {
        $products = Product::with([
            'template',
            'uploads',
            'images',
            'paperStocks',
            'addons',
            'colors',
            'materials',
            'envelope.material'
        ])->where('id', $id)->get();

        if ($products->isEmpty()) {
            abort(404);
        }

        $products->each(function (Product $product) {
            $product->setRelation('bulkOrders', collect());
            $this->hydrateLegacyAttributes($product);
        });

        return view('customer.Invitations.weddinginvite', compact('products'));
    }
}
