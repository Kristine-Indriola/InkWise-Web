<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUpload;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\TemplateUploadedNotification;

class TemplateController extends Controller
{
    // Show all templates
    public function index()
    {
        $type = request('type');
        $query = Template::query();

        if ($type && in_array($type, ['invitation', 'giveaway', 'envelope'])) {
            $query->where('product_type', ucfirst($type));
        }

        // Exclude templates that have been uploaded (status = 'uploaded')
        $query->where('status', '!=', 'uploaded');

        $templates = $query->paginate(12); // Show 12 per page

        // Determine if this is admin or staff route
        $prefix = request()->route()->getPrefix();
        $isStaff = str_contains($prefix, 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        // Debug: log the prefix and isStaff value
        Log::info('TemplateController::index - Prefix: ' . $prefix . ', isStaff: ' . ($isStaff ? 'true' : 'false'));

        return view('staff.templates.index', compact('templates', 'type'));
    }

    // Show uploaded templates
    public function uploaded()
    {
        $type = request('type');
        $query = Template::where('status', 'uploaded');

        if ($type && in_array($type, ['invitation', 'giveaway', 'envelope'])) {
            $query->where('product_type', ucfirst($type));
        }

        $templates = $query->paginate(12); // Show 12 per page

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        return view('staff.templates.uploaded', compact('templates', 'type'));
    }

    // Show create form
    public function create()
    {
        $type = request('type', 'invitation');

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        if ($type === 'giveaway') {
            return view('staff.templates.create-giveaway');
        }

        if ($type === 'envelope') {
            return view('staff.templates.create-envelope');
        }

        return view('staff.templates.create');
    }

    // Show edit form
    public function edit($id)
    {
        $template = Template::findOrFail($id);

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        return view('staff.templates.edit', compact('template'));
    }

    // Update template
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];

        // Handle file uploads based on template type
        if ($request->hasFile('front_image')) {
            $rules['front_image'] = 'file|mimes:jpeg,png,jpg,gif,svg|max:5120';
        }

        if ($request->hasFile('back_image')) {
            $rules['back_image'] = 'file|mimes:jpeg,png,jpg,gif,svg|max:5120';
        }

        $validated = $request->validate($rules);

        // Handle front image upload
        if ($request->hasFile('front_image')) {
            $frontImagePath = $request->file('front_image')->store('templates', 'public');
            $validated['front_image'] = $frontImagePath;
        }

        // Handle back image upload
        if ($request->hasFile('back_image')) {
            $backImagePath = $request->file('back_image')->store('templates', 'public');
            $validated['back_image'] = $backImagePath;
        }

        $template->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            // Determine if this is admin or staff route
            $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
            // TEMPORARY: Force staff routes for debugging
            $isStaff = true;
            $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

            return response()->json([
                'success' => true,
                'redirect' => route($redirectRoute),
            ]);
        }

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
        // TEMPORARY: Force staff routes for debugging
        $isStaff = true;
        $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

        return redirect()->route($redirectRoute)->with('success', 'Updated successfully');
    }

    // Store new template
    public function store(Request $request)
    {
        // Check if this is saving a preview
        if ($request->filled('edit_preview_id')) {
            return $this->savePreview($request, $request->input('edit_preview_id'));
        }

        // Determine required file inputs based on type (invitation vs giveaway/envelope)
        $type = $request->input('product_type') ?: $request->query('type', 'invitation');

        $rules = [
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];

        // Check if this is a Figma import (has SVG content or Figma URL)
        $isFigmaImport = $request->filled('front_svg_content') || $request->filled('back_svg_content') || $request->filled('figma_url');

        // Make file uploads optional, but ensure at least one design source is provided
        if ($type === 'invitation') {
            $rules['front_image'] = 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
            $rules['back_image'] = 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
        } else {
            $rules['front_image'] = 'nullable|file|mimes:svg,svg+xml,image/svg+xml,svgz|max:5120';
        }

        $validated = $request->validate($rules);

        // Custom validation: Ensure at least one design source is provided
        $hasManualUpload = $request->hasFile('front_image') || ($type === 'invitation' && $request->hasFile('back_image'));
        $hasFigmaContent = $request->filled('front_svg_content') || ($type === 'invitation' && $request->filled('back_svg_content'));

        if (!$hasManualUpload && !$hasFigmaContent) {
            return back()->withErrors(['design' => 'Please provide at least one design source: upload files manually or import from Figma.']);
        }

        // Handle front image upload or SVG content from Figma
        if ($request->hasFile('front_image')) {
            $frontImagePath = $request->file('front_image')->store('templates', 'public');
            $validated['front_image'] = $frontImagePath;
        } elseif ($request->filled('front_svg_content')) {
            // Handle Figma SVG content
            $frontSvgContent = $request->input('front_svg_content');
            $filename = 'figma_front_' . uniqid() . '.svg';
            $frontImagePath = 'templates/' . $filename;
            Storage::disk('public')->put($frontImagePath, $frontSvgContent);
            $validated['front_image'] = $frontImagePath;
        }

        // Handle back image upload or SVG content from Figma
        if ($request->hasFile('back_image')) {
            $backImagePath = $request->file('back_image')->store('templates', 'public');
            $validated['back_image'] = $backImagePath;
        } elseif ($request->filled('back_svg_content')) {
            // Handle Figma SVG content
            $backSvgContent = $request->input('back_svg_content');
            $filename = 'figma_back_' . uniqid() . '.svg';
            $backImagePath = 'templates/' . $filename;
            Storage::disk('public')->put($backImagePath, $backSvgContent);
            $validated['back_image'] = $backImagePath;
        } else {
            // For giveaway/envelope, back_image may be absent; keep it null
            $validated['back_image'] = $validated['back_image'] ?? null;
        }

        // Store Figma metadata if available
        if ($request->filled('figma_url') && $request->filled('figma_file_key')) {
            $validated['figma_url'] = $request->input('figma_url');
            $validated['figma_file_key'] = $request->input('figma_file_key');
            $validated['figma_synced_at'] = now();
        }

        $template = \App\Models\Template::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            // Determine if this is admin or staff route
            $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
            // TEMPORARY: Force staff routes for debugging
            $isStaff = true;
            $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

            return response()->json([
                'success' => true,
                'template_id' => $template->id,
                'redirect' => route($redirectRoute),
            ]);
        }

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
        // TEMPORARY: Force staff routes for debugging
        $isStaff = true;
        $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

        return redirect()->route($redirectRoute)->with('success', 'Created successfully');
    }

    // Show editor page for a template
    public function editor($id)
    {
        $template = Template::findOrFail($id);

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        return view('staff.templates.editor', compact('template'));
    }
   
public function destroy($id)
{
    $template = Template::findOrFail($id);
    $template->delete();

    // Determine if this is admin or staff route
    $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

    // TEMPORARY: Force staff routes for debugging
    $isStaff = true;

    $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

    return redirect()->route($redirectRoute)->with('success', 'Deleted successfully');
}

public function saveCanvas(Request $request, $id)
{
    $template = Template::findOrFail($id);

    if ($request->has('canvas_image')) {
        $imageData = $request->input('canvas_image');
        // Remove base64 prefix
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);

        $imageName = 'template_' . $id . '_' . time() . '.png';
        $filePath = 'templates/previews/' . $imageName;

        // Save to storage/app/public/templates/previews
        Storage::disk('public')->put($filePath, base64_decode($imageData));

        // Update DB preview column
        $template->preview = $filePath;
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Preview saved successfully',
            'preview' => \App\Support\ImageResolver::url($filePath)
        ]);
    }

    return response()->json(['success' => false, 'message' => 'No image data provided'], 400);
}

public function uploadPreview(Request $request, $id)
{
    $template = Template::findOrFail($id);
    $imgData = $request->input('preview_image');

    // Remove the data:image/png;base64, part
    $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $imgData);
    $imgData = base64_decode($imgData);

    // Save to storage (public disk)
    $filename = 'templates/previews/template_' . $id . '_' . time() . '.png';
    Storage::disk('public')->put($filename, $imgData);

    // Update preview column (store path)
    $template->preview = $filename;
    $template->save();

    return response()->json(['success' => true, 'preview' => $filename]);
}

    // Handle custom front/back template upload from the Templates UI
    public function customUpload(Request $request)
    {
        $validated = $request->validate([
            'front_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'back_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

    // Store images on the dedicated invitation_templates disk (container)
    $frontPath = $request->file('front_image')->store('', 'invitation_templates');
    $backPath = $request->file('back_image')->store('', 'invitation_templates');

    // sanitize paths: remove newlines/tabs and trim whitespace
    $frontPath = str_replace(["\r", "\n", "\t"], '', trim($frontPath));
    $backPath = str_replace(["\r", "\n", "\t"], '', trim($backPath));

        // Create Template record and store front/back image paths on template
        $template = Template::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'product_type' => 'Invitation',
            'preview' => 'invitation_templates/' . ltrim($frontPath, '/'), // keep front as preview
            'front_image' => 'invitation_templates/' . ltrim($frontPath, '/'),
            'back_image' => 'invitation_templates/' . ltrim($backPath, '/'),
        ]);

        // Create product from template (no separate product_images table usage)
        $product = Product::create([
            'template_id' => $template->id,
            'name' => $template->name,
            'event_type' => null,
            'product_type' => 'Invitation',
            'theme_style' => null,
            'description' => $template->description,
            // store the disk-qualified path for easy resolver later
            'image' => 'invitation_templates/' . ltrim($frontPath, '/'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template' => $template,
                'product' => $product,
                'preview_url' => \App\Support\ImageResolver::url($frontPath),
            ]);
        }

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
        // TEMPORARY: Force staff routes for debugging
        $isStaff = true;
        $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

        return redirect()->route($redirectRoute)->with('success', 'Custom template uploaded.');
    }

public function uploadToProduct(Request $request, $id)
{
    $template = Template::findOrFail($id);
    // Check if a product already exists for this template (idempotent)
    $existing = Product::where('template_id', $template->id)->first();
    if ($existing) {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'product_id' => $existing->id, 'existing' => true]);
        }
        return redirect()->route('admin.products.index')->with('success', 'Template already uploaded as product.');
    }

    // Try to copy template preview into products folder so product images are stable
    $imagePath = null;
    if (!empty($template->preview)) {
        $preview = preg_replace('#^/??storage/#i', '', $template->preview);

        // Helper: try to find actual file path (handles missing extension)
        $resolvePreview = function($previewPath) {
            // If exact path exists on public disk, return it
            if (Storage::disk('public')->exists($previewPath)) {
                return $previewPath;
            }

            // Search within same directory for files starting with the basename (handles missing ext)
            $dir = trim(dirname($previewPath), '\\/');
            $base = pathinfo($previewPath, PATHINFO_FILENAME);
            $files = [];
            if ($dir && Storage::disk('public')->exists($dir)) {
                $files = Storage::disk('public')->files($dir);
            } else {
                // fallback: list all files under templates/previews if dir missing
                if (Storage::disk('public')->exists('templates/previews')) {
                    $files = Storage::disk('public')->files('templates/previews');
                }
            }

            foreach ($files as $f) {
                if (Str::startsWith(pathinfo($f, PATHINFO_FILENAME), $base)) {
                    return $f;
                }
            }

            // also try to match on public filesystem (public/storage/...)
            $publicDir = public_path('storage/' . ($dir ?: ''));
            if (is_dir($publicDir)) {
                $glob = glob($publicDir . DIRECTORY_SEPARATOR . $base . '*');
                if ($glob && count($glob)) {
                    // return storage relative path
                    $found = str_replace(public_path('storage') . DIRECTORY_SEPARATOR, '', $glob[0]);
                    return str_replace('\\', '/', $found);
                }
            }

            return $previewPath;
        };

        try {
            $actual = $resolvePreview($preview);

            if (Storage::disk('public')->exists($actual)) {
                $ext = pathinfo($actual, PATHINFO_EXTENSION) ?: 'png';
                $newName = 'products/product_' . $template->id . '_' . time() . '.' . $ext;
                Storage::disk('public')->copy($actual, $newName);
                $imagePath = $newName;
            } else {
                // attempt to copy from public path if exists
                $possible = [
                    public_path('storage/' . $actual),
                    public_path($actual),
                ];
                foreach ($possible as $p) {
                    if (file_exists($p)) {
                        $ext = pathinfo($p, PATHINFO_EXTENSION) ?: 'png';
                        $newName = 'products/product_' . $template->id . '_' . time() . '.' . $ext;
                        $contents = file_get_contents($p);
                        Storage::disk('public')->put($newName, $contents);
                        $imagePath = $newName;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // swallow and fallback to storing the original preview path
            $imagePath = $template->preview;
        }
    }

    // Create product using template info
    $product = Product::create([
        'template_id'    => $template->id,
        'name'           => $template->name,
        'event_type'     => $template->category ?? null,
        'product_type'   => 'Invitation',
        'theme_style'    => $template->theme_style ?? '',
        'description'    => $template->description ?? '',
        'image'          => $imagePath ?? ($template->preview ? $template->preview : null),
        'status'         => 'active',
    ]);

    if ($request->expectsJson() || $request->ajax()) {
        return response()->json(['success' => true, 'product_id' => $product->id, 'existing' => false]);
    }

    return redirect()->route('admin.products.index')->with('success', 'Template uploaded as product!');
}

    // Update canvas settings (size & shape) for a template (saves into design json)
    public function updateCanvasSettings(Request $request, $id)
    {
        $template = Template::findOrFail($id);
        $w = intval($request->input('width', 0));
        $h = intval($request->input('height', 0));
        $shape = $request->input('shape', 'rectangle');

        $design = json_decode($template->design, true) ?: [];
        $design['canvas'] = array_merge($design['canvas'] ?? [], [
            'width' => $w,
            'height' => $h,
            'shape' => $shape,
        ]);
        $template->design = json_encode($design);
        $template->save();

        return response()->json(['success' => true, 'canvas' => $design['canvas']]);
    }

    // Search assets (images, videos, elements) under storage/public/assets/{type}
    public function searchAssets(Request $request, $id)
    {
        $type = $request->query('type', 'image');
        $q = $request->query('q', '');

        $allowed = ['image', 'video', 'element'];
        if (!in_array($type, $allowed)) {
            return response()->json(['success' => false, 'message' => 'Invalid asset type'], 400);
        }

        $dir = 'assets/' . $type;
        $files = Storage::disk('public')->exists($dir) ? Storage::disk('public')->files($dir) : [];

        $results = collect($files)->filter(function($path) use ($q) {
            if (!$q) return true;
            return Str::contains(strtolower(basename($path)), strtolower($q));
        })->map(function($path){
            return [
                'path' => $path,
                'url' => \App\Support\ImageResolver::url($path),
                'name' => basename($path),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $results]);
    }

    // Upload template (create record in product_uploads table)
    public function uploadTemplate(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        // Check if already uploaded to product_uploads
        $existing = ProductUpload::where('template_id', $template->id)->first();
        if ($existing) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Template already uploaded to products']);
            }
            return redirect()->back()->with('success', 'Template already uploaded to products');
        }

        // Create record in product_uploads table
        $productUpload = ProductUpload::create([
            'template_id' => $template->id,
            'template_name' => $template->name,
            'description' => $template->description,
            'product_type' => $template->product_type,
            'event_type' => $template->event_type,
            'theme_style' => $template->theme_style,
            'front_image' => $template->front_image,
            'back_image' => $template->back_image,
            'preview_image' => $template->preview,
            'design_data' => $template->design,
        ]);

        // Update template status to uploaded
        $template->update([
            'status' => 'uploaded'
        ]);

        // Send notification to all admin users
        $admins = User::where('role', 'admin')->get();
        $staff = Auth::user(); // Get the current authenticated user (staff who uploaded)

        foreach ($admins as $admin) {
            $admin->notify(new TemplateUploadedNotification($template, $staff));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Template uploaded to products successfully',
                'redirect' => route('staff.templates.index')
            ]);
        }

        return redirect()->back()->with('success', 'Template uploaded to products successfully');
    }

    // Create a preview of the template (store in session)
    public function preview(Request $request)
    {
        // Validate the request data
        $rules = [
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];

        // Check if this is a Figma import
        $isFigmaImport = $request->filled('front_svg_content') || $request->filled('back_svg_content');

        // For Figma imports, SVG content is required
        if ($isFigmaImport) {
            if (!$request->filled('front_svg_content') && !$request->filled('back_svg_content')) {
                return back()->withErrors(['svg_content' => 'At least one SVG design (front or back) is required for Figma imports.']);
            }
        }

        $validated = $request->validate($rules);

        // Add SVG content if present
        if ($request->filled('front_svg_content')) {
            $validated['front_svg_content'] = $request->input('front_svg_content');
        }
        if ($request->filled('back_svg_content')) {
            $validated['back_svg_content'] = $request->input('back_svg_content');
        }

        // Add Figma metadata if present
        if ($request->filled('figma_url')) {
            $validated['figma_url'] = $request->input('figma_url');
        }
        if ($request->filled('figma_file_key')) {
            $validated['figma_file_key'] = $request->input('figma_file_key');
        }

        // Add design data if present
        if ($request->filled('design')) {
            $validated['design'] = $request->input('design');
        }

        // Store in session with a unique ID
        $previewId = uniqid('preview_');
        $previews = session('preview_templates', []);
        $previews[$previewId] = array_merge($validated, [
            'id' => $previewId,
            'created_at' => now(),
        ]);
        session(['preview_templates' => $previews]);

        // Redirect back to the create form with the preview ID
        return redirect()->route('staff.templates.create', [
            'type' => $request->query('type', 'invitation'),
            'edit_preview' => $previewId
        ])->with('success', 'Template preview created successfully. You can now review and save it.');
    }

    // Save a preview to the database
    public function savePreview(Request $request, $previewId)
    {
        $previews = session('preview_templates', []);
        if (!isset($previews[$previewId])) {
            return response()->json(['success' => false, 'message' => 'Preview not found'], 404);
        }

        $previewData = $previews[$previewId];

        // Create the template
        $template = Template::create([
            'name' => $previewData['name'],
            'event_type' => $previewData['event_type'] ?? null,
            'product_type' => $previewData['product_type'] ?? 'Invitation',
            'theme_style' => $previewData['theme_style'] ?? null,
            'description' => $previewData['description'] ?? null,
            'design' => $previewData['design'] ?? '{}',
            'figma_url' => $previewData['figma_url'] ?? null,
            'figma_file_key' => $previewData['figma_file_key'] ?? null,
            'figma_synced_at' => isset($previewData['figma_url']) ? now() : null,
        ]);

        // Handle SVG content storage
        if (isset($previewData['front_svg_content'])) {
            $filename = 'figma_front_' . uniqid() . '.svg';
            $frontImagePath = 'templates/' . $filename;
            Storage::disk('public')->put($frontImagePath, $previewData['front_svg_content']);
            $template->front_image = $frontImagePath;
        }

        if (isset($previewData['back_svg_content'])) {
            $filename = 'figma_back_' . uniqid() . '.svg';
            $backImagePath = 'templates/' . $filename;
            Storage::disk('public')->put($backImagePath, $previewData['back_svg_content']);
            $template->back_image = $backImagePath;
        }

        $template->save();

        // Remove from session
        unset($previews[$previewId]);
        session(['preview_templates' => $previews]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template_id' => $template->id,
                'redirect' => route('staff.templates.index'),
            ]);
        }

        return redirect()->route('staff.templates.index')->with('success', 'Template created successfully');
    }

    // Remove a preview from session
    public function removePreview(Request $request, $previewId)
    {
        $previews = session('preview_templates', []);
        if (isset($previews[$previewId])) {
            unset($previews[$previewId]);
            session(['preview_templates' => $previews]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('staff.templates.create');
    }
}
