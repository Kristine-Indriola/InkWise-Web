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
use App\Services\SvgAutoParser;
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

    // Load any session-stored preview templates (created but not yet saved to DB)
    $previewTemplates = session('preview_templates', []);

    return view('staff.templates.index', compact('templates', 'type', 'previewTemplates'));
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
        // Determine required file inputs based on type (invitation vs giveaway/envelope)
        $type = $request->input('product_type') ?: $request->query('type', 'invitation');

        $rules = [
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];

        // Invitations require both front and back images; giveaways/envelopes only front_image
        if ($type === 'invitation') {
            $rules['front_image'] = 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
            $rules['back_image'] = 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
        } else {
            $rules['front_image'] = 'required|file|mimes:svg,svg+xml,image/svg+xml,svgz|max:5120';
        }

        $validated = $request->validate($rules);

        // Handle front image upload
        if ($request->hasFile('front_image')) {
            $frontImagePath = $request->file('front_image')->store('templates', 'public');
            $validated['front_image'] = $frontImagePath;

            // Parse SVG if it's an SVG file
            if ($this->isSvgFile($request->file('front_image'))) {
                $svgParser = new SvgAutoParser();
                $parsedSvg = $svgParser->parseSvg($frontImagePath);

                // Store parsed SVG data in design field
                $designData = $request->input('design', []);
                if (!is_array($designData)) {
                    $designData = json_decode($designData, true) ?: [];
                }

                $designData['svg_parsed'] = true;
                $designData['svg_data'] = [
                    'text_elements' => $parsedSvg['text_elements'],
                    'image_elements' => $parsedSvg['image_elements'],
                    'changeable_images' => $parsedSvg['changeable_images'],
                    'metadata' => $parsedSvg['metadata']
                ];

                $validated['design'] = json_encode($designData);

                // Use processed SVG path if available
                if ($parsedSvg['processed_path'] !== $frontImagePath) {
                    $validated['front_image'] = $parsedSvg['processed_path'];
                }
            }
        }

        // Handle back image upload
        if ($request->hasFile('back_image')) {
            $backImagePath = $request->file('back_image')->store('templates', 'public');
            $validated['back_image'] = $backImagePath;
        } else {
            // For giveaway/envelope, back_image may be absent; keep it null
            $validated['back_image'] = $validated['back_image'] ?? null;
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

    /**
     * Store a preview of a template in session (do not persist to templates table yet)
     */
    public function preview(Request $request)
    {
        // Similar validation rules to store(), but we keep data in session and store uploaded files under templates/previews
        $type = $request->input('product_type') ?: $request->query('type', 'invitation');

        $rules = [
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];

        // Make file uploads conditional based on whether imported paths are provided
        $hasImportedFront = $request->has('imported_front_path') && $request->input('imported_front_path');
        $hasImportedBack = $request->has('imported_back_path') && $request->input('imported_back_path');
        
        if ($type === 'invitation') {
            if (!$hasImportedFront) {
                $rules['front_image'] = 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
            }
            if (!$hasImportedBack) {
                $rules['back_image'] = 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120';
            }
        } else {
            if (!$hasImportedFront) {
                $rules['front_image'] = 'required|file|mimes:svg,svg+xml,image/svg+xml,svgz|max:5120';
            }
        }
        
        // Add validation for imported paths if they exist
        if ($hasImportedFront) {
            $rules['imported_front_path'] = 'required|string';
        }
        if ($hasImportedBack) {
            $rules['imported_back_path'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Check if we're editing an existing preview
        $editPreviewId = $request->input('edit_preview_id');
        $previews = session('preview_templates', []);
        $existingPreviewKey = null;

        if ($editPreviewId) {
            foreach ($previews as $k => $p) {
                if (isset($p['id']) && $p['id'] === $editPreviewId) {
                    $existingPreviewKey = $k;
                    break;
                }
            }
        }

        // If editing existing preview, remove old files
        if ($existingPreviewKey !== null) {
            $existingPreview = $previews[$existingPreviewKey];
            try {
                if (!empty($existingPreview['front_image']) && Storage::disk('public')->exists($existingPreview['front_image'])) {
                    Storage::disk('public')->delete($existingPreview['front_image']);
                }
                if (!empty($existingPreview['back_image']) && Storage::disk('public')->exists($existingPreview['back_image'])) {
                    Storage::disk('public')->delete($existingPreview['back_image']);
                }
            } catch (\Throwable $e) {
                // ignore cleanup errors
            }
        }

        // Save uploaded files into a previews folder on the public disk
        $frontPath = null;
        $backPath = null;
        
        // Check for imported SVG paths first
        if ($request->has('imported_front_path') && $request->input('imported_front_path')) {
            $frontPath = $request->input('imported_front_path');
        } elseif ($request->hasFile('front_image')) {
            $frontPath = $request->file('front_image')->store('templates/previews', 'public');
        }
        
        if ($request->has('imported_back_path') && $request->input('imported_back_path')) {
            $backPath = $request->input('imported_back_path');
        } elseif ($request->hasFile('back_image')) {
            $backPath = $request->file('back_image')->store('templates/previews', 'public');
        }

        // Build preview object
        if ($existingPreviewKey !== null) {
            // Update existing preview
            $previewId = $editPreviewId;
            $previews[$existingPreviewKey] = [
                'id' => $previewId,
                'name' => $validated['name'] ?? '',
                'description' => $validated['description'] ?? null,
                'product_type' => $validated['product_type'] ?? ucfirst($type),
                'event_type' => $validated['event_type'] ?? null,
                'theme_style' => $validated['theme_style'] ?? null,
                'front_image' => $frontPath,
                'back_image' => $backPath,
                'preview' => $frontPath,
                'design' => $request->input('design') ?? null,
                'created_at' => $previews[$existingPreviewKey]['created_at'] ?? now()->toDateTimeString(),
            ];
        } else {
            // Create new preview
            $previewId = uniqid('preview_');
            $preview = [
                'id' => $previewId,
                'name' => $validated['name'] ?? '',
                'description' => $validated['description'] ?? null,
                'product_type' => $validated['product_type'] ?? ucfirst($type),
                'event_type' => $validated['event_type'] ?? null,
                'theme_style' => $validated['theme_style'] ?? null,
                'front_image' => $frontPath,
                'back_image' => $backPath,
                'preview' => $frontPath,
                'design' => $request->input('design') ?? null,
                'created_at' => now()->toDateTimeString(),
            ];
            $previews[] = $preview;
        }

        session(['preview_templates' => $previews]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'preview_id' => $previewId, 'redirect' => route('staff.templates.index')]);
        }

        return redirect()->route('staff.templates.index')->with('success', 'Preview ' . ($existingPreviewKey !== null ? 'updated' : 'created'));
    }

    /**
     * Persist a session preview into the templates DB table (called when staff confirms Upload)
     */
    public function savePreview(Request $request, $previewId)
    {
        $previews = session('preview_templates', []);
        $foundKey = null;
        $found = null;
        foreach ($previews as $k => $p) {
            if (isset($p['id']) && $p['id'] === $previewId) {
                $foundKey = $k;
                $found = $p;
                break;
            }
        }

        if (!$found) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Preview not found'], 404);
            }
            return redirect()->route('staff.templates.index')->with('error', 'Preview not found');
        }

        // Create Template from preview data
        $templateData = [
            'name' => $found['name'] ?? 'Untitled',
            'description' => $found['description'] ?? null,
            'product_type' => $found['product_type'] ?? 'Invitation',
            'event_type' => $found['event_type'] ?? null,
            'theme_style' => $found['theme_style'] ?? null,
            'front_image' => $found['front_image'] ?? null,
            'back_image' => $found['back_image'] ?? null,
            'preview' => $found['preview'] ?? ($found['front_image'] ?? null),
            'design' => $found['design'] ?? null,
        ];

    // Mark this template as uploaded so it appears in the "uploaded" listing
    $templateData['status'] = 'uploaded';
    $template = Template::create($templateData);

        // Remove preview from session
        array_splice($previews, $foundKey, 1);
        session(['preview_templates' => $previews]);

        $redirectRoute = route('staff.templates.uploaded');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'template_id' => $template->id, 'redirect' => $redirectRoute]);
        }

        return redirect($redirectRoute)->with('success', 'Template saved and uploaded');
    }

    /**
     * Remove a session preview entry (AJAX)
     */
    public function removePreview(Request $request, $previewId)
    {
        $previews = session('preview_templates', []);
        $foundKey = null;
        foreach ($previews as $k => $p) {
            if (isset($p['id']) && $p['id'] === $previewId) {
                $foundKey = $k;
                break;
            }
        }

        if ($foundKey === null) {
            return response()->json(['success' => false, 'message' => 'Preview not found'], 404);
        }

        $entry = $previews[$foundKey];

        // Remove any stored files for this preview (best-effort)
        try {
            if (!empty($entry['front_image']) && Storage::disk('public')->exists($entry['front_image'])) {
                Storage::disk('public')->delete($entry['front_image']);
            }
            if (!empty($entry['back_image']) && Storage::disk('public')->exists($entry['back_image'])) {
                Storage::disk('public')->delete($entry['back_image']);
            }
        } catch (\Throwable $e) {
            // ignore file deletion errors
        }

        array_splice($previews, $foundKey, 1);
        session(['preview_templates' => $previews]);

        return response()->json(['success' => true]);
    }

    // Show editor page for a template
    public function editor($id)
    {
        $template = Template::findOrFail($id);

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        // Check if template has SVG design data
        $designData = json_decode($template->design, true);
        $hasSvgData = $designData && (
            isset($designData['text_elements']) ||
            isset($designData['changeable_images']) ||
            $template->svg_path
        );

        // Use SVG editor if template has SVG data, otherwise use regular canvas editor
        if ($hasSvgData) {
            return view('staff.templates.svg-editor', compact('template'));
        }

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
                return response()->json([
                    'success' => false, 
                    'message' => 'Template already uploaded to products'
                ]);
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
                'product_upload_id' => $productUpload->id
            ]);
        }

        return redirect()->back()->with('success', 'Template uploaded to products successfully');
    }

    /**
     * Check if an uploaded file is an SVG
     */
    private function isSvgFile($file): bool
    {
        if (!$file) return false;

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($mimeType, ['image/svg+xml', 'text/xml', 'application/xml', 'application/svg+xml']) ||
               in_array($extension, ['svg', 'svgz']);
    }

    /**
     * Save SVG content from the editor
     */
    public function saveSvg(Request $request, $id)
    {
        $request->validate([
            'svg_content' => 'required|string',
            'side' => 'nullable|string|in:front,back'
        ]);

        $template = Template::findOrFail($id);
        $side = $request->input('side', 'front');

        try {
            // Store the SVG content
            $svgContent = $request->input('svg_content');

            // Generate a filename for the processed SVG
            $filename = 'processed_' . $side . '_' . time() . '_' . Str::random(8) . '.svg';
            $path = 'templates/svg/' . $filename;

            // Store the SVG file
            Storage::disk('public')->put($path, $svgContent);

            // Update template with the appropriate SVG path
            if ($side === 'back') {
                $template->update([
                    'back_svg_path' => $path,
                    'processed_at' => now(),
                ]);
            } else {
                $template->update([
                    'svg_path' => $path,
                    'processed_at' => now(),
                ]);
            }

            // Log the save action
            Log::info('SVG saved for template', [
                'template_id' => $template->id,
                'side' => $side,
                'path' => $path,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($side) . ' SVG saved successfully',
                'path' => $path,
                'side' => $side
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save SVG', [
                'template_id' => $id,
                'side' => $side,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save ' . $side . ' SVG: ' . $e->getMessage()
            ], 500);
        }
    }
}
