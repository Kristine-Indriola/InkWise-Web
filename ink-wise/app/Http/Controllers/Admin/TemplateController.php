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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Notifications\TemplateUploadedNotification;
use App\Notifications\TemplateReturnedNotification;
use App\Support\ImageResolver;

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

        // Exclude templates that are uploaded or already assigned to products
        $query->whereNotIn('status', ['uploaded', 'assigned']);

        $templates = $query->paginate(12); // Show 12 per page

        // Determine if this is admin or staff route
        $prefix = request()->route()->getPrefix();
        $isStaff = str_contains($prefix, 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        // Debug: log the prefix and isStaff value
        Log::info('TemplateController::index - Prefix: ' . $prefix . ', isStaff: ' . ($isStaff ? 'true' : 'false'));

        $templateBootstrap = null;

        return view('staff.templates.index', compact('templates', 'type', 'templateBootstrap'));
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
            $validated['preview_front'] = $frontImagePath;
        }

        // Handle back image upload
        if ($request->hasFile('back_image')) {
            $backImagePath = $request->file('back_image')->store('templates', 'public');
            $validated['back_image'] = $backImagePath;
            $validated['preview_back'] = $backImagePath;
        }

        $template->fill($validated);
        $this->synchronizeTemplateSideState($template);
        $template->save();

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
            'template_video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200',
        ];

        // Only require files if they are actually being uploaded
        // Allow creating template without files for direct editor access
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
        } else {
            // For giveaway/envelope, back_image may be absent; keep it null
            $validated['back_image'] = $validated['back_image'] ?? null;
        }

        // Handle optional template preview video
        $videoPath = null;
        if ($request->hasFile('template_video')) {
            $videoPath = $request->file('template_video')->store('templates/videos', 'public');
        }

        if ($videoPath) {
            $metadata = [];
            if (isset($validated['metadata']) && is_array($validated['metadata'])) {
                $metadata = $validated['metadata'];
            }
            $metadata['preview_video'] = $videoPath;
            $validated['metadata'] = $metadata;
        }

        $validated['has_back_design'] = !empty($validated['back_image']);

        if (!empty($validated['front_image'])) {
            $validated['preview_front'] = $validated['front_image'];
        }

        if (!empty($validated['back_image'])) {
            $validated['preview_back'] = $validated['back_image'];
        }

        $template = new Template($validated);
        $this->synchronizeTemplateSideState($template);
        $template->save();

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
        // TEMPORARY: Force staff routes for debugging
        $isStaff = true;
        $editorRouteName = $isStaff ? 'staff.templates.editor' : 'admin.templates.editor';
        $redirectUrl = route($editorRouteName, $template->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template_id' => $template->id,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->route($editorRouteName, $template->id)->with('success', 'Template created. You can continue editing it now.');
    }

    // Show editor page for a template
    public function editor($id)
    {
        $template = Template::findOrFail($id);

        // Determine if this is admin or staff route
        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');

        // TEMPORARY: Force staff views for debugging
        $isStaff = true;

        $templateBootstrap = [
            'id' => $template->id,
            'name' => $template->name,
            'has_back_design' => false,
            'svg_path' => $template->svg_path ? \App\Support\ImageResolver::url($template->svg_path) : null,
            'back_svg_path' => null,
            'svg_source' => $template->svg_path,
            'back_svg_source' => null,
            'preview' => $template->preview ? \App\Support\ImageResolver::url($template->preview) : null,
            'preview_front' => $template->preview_front ? \App\Support\ImageResolver::url($template->preview_front) : null,
            'preview_back' => null,
            'front_image' => $template->front_image ? \App\Support\ImageResolver::url($template->front_image) : null,
            'back_image' => null,
            'updated_at' => optional($template->updated_at)->toIso8601String(),
        ];

        return view('staff.templates.editor', compact('template', 'templateBootstrap'));
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
        $filePath = 'templates/preview/' . $imageName;

        // Save to storage/app/public/templates/preview
        Storage::disk('public')->put($filePath, base64_decode($imageData));

        // Update DB preview column
        $template->preview = $filePath;
        $this->synchronizeTemplateSideState($template);
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Preview saved successfully',
            'preview' => \App\Support\ImageResolver::url($filePath)
        ]);
    }

    return response()->json(['success' => false, 'message' => 'No image data provided'], 400);
}

    public function saveTemplate(Request $request, $id)
    {
        Log::info('saveTemplate called for template', ['id' => $id, 'request_data_keys' => array_keys($request->all())]);
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'design' => 'required|array',
            'svg_markup' => 'nullable|string',
            'preview_image' => 'nullable|string',
            'preview_images' => 'nullable|array',
            'preview_images_meta' => 'nullable|array',
            'template_name' => 'nullable|string|max:255',
        ]);

        Log::info('saveTemplate called', [
            'id' => $id,
            'has_svg_markup' => !empty($validated['svg_markup']),
            'has_preview_image' => !empty($validated['preview_image']),
            'has_preview_images' => !empty($validated['preview_images']),
        ]);

        if (!empty($validated['template_name'])) {
            $template->name = $validated['template_name'];
        }

        $template->design = $validated['design'];

        $metadata = $template->metadata ?? [];
        if (!is_array($metadata)) {
            $metadata = is_string($metadata) ? json_decode($metadata, true) ?? [] : (array) $metadata;
        }

        if (isset($validated['design']['canvas']) && is_array($validated['design']['canvas'])) {
            $metadata['builder_canvas'] = $validated['design']['canvas'];
        }

        // Handle single preview_image for backward compatibility
        if (!empty($validated['preview_image'])) {
            $template->preview = $this->persistDataUrl(
                $validated['preview_image'],
                'templates/preview',
                'png',
                $template->preview,
                'preview_image'
            );
            Log::info('Preview image saved', ['path' => $template->preview]);
            // Also populate preview_front and front_image for consistency
            $template->preview_front = $template->preview;
            if (empty($template->front_image)) {
                $template->front_image = $template->preview;
            }
        }

        // Handle multiple preview_images
        if (!empty($validated['preview_images']) && is_array($validated['preview_images'])) {
            $previews = [];
            foreach ($validated['preview_images'] as $key => $imageData) {
                if (is_string($imageData) && !empty($imageData)) {
                    $filename = $this->persistDataUrl(
                        $imageData,
                        'templates/preview',
                        'png',
                        null,
                        "preview_{$key}"
                    );
                    if ($filename) {
                        $previews[$key] = $filename;
                    }
                }
            }
            if (!empty($previews)) {
                $metadata['previews'] = $previews;
                Log::info('Multiple previews saved', ['previews' => $previews]);
                // Also populate preview_front/front_image from the 'front' preview
                if (isset($previews['front'])) {
                    $template->preview_front = $previews['front'];
                    if (empty($template->front_image)) {
                        $template->front_image = $previews['front'];
                    }
                }
                // Populate back fields if back preview exists
                if (isset($previews['back'])) {
                    $template->preview_back = $previews['back'];
                    if (empty($template->back_image)) {
                        $template->back_image = $previews['back'];
                    }
                }
            }
        } elseif (array_key_exists('preview_images', $validated)) {
            unset($metadata['previews']);
        }

        // Store preview_images_meta if provided
        if (!empty($validated['preview_images_meta']) && is_array($validated['preview_images_meta'])) {
            $metadata['preview_images_meta'] = $validated['preview_images_meta'];
        } elseif (array_key_exists('preview_images_meta', $validated)) {
            unset($metadata['preview_images_meta']);
        }

        if (!empty($validated['svg_markup'])) {
            $template->svg_path = $this->persistDataUrl(
                $validated['svg_markup'],
                'templates/svg',
                'svg',
                $template->svg_path,
                'svg_markup'
            );
            Log::info('SVG saved', ['path' => $template->svg_path]);
        }

        $template->metadata = $metadata;
        $this->synchronizeTemplateSideState($template, $metadata);
        $template->status = $template->status ?? 'draft';
        $template->save();

        $prefix = $request->route()->getPrefix();
        $isStaff = str_contains($prefix ?? '', 'staff');
        $isStaff = true;
        $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
            'redirect' => route($redirectRoute),
            'preview_url' => $template->preview ? \App\Support\ImageResolver::url($template->preview) : null,
            'svg_path' => $template->svg_path,
        ]);
    }

public function uploadPreview(Request $request, $id)
{
    $template = Template::findOrFail($id);
    $imgData = $request->input('preview_image');

    // Remove the data:image/png;base64, part
    $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $imgData);
    $imgData = base64_decode($imgData);

    // Save to storage (public disk)
    $filename = 'templates/preview/template_' . $id . '_' . time() . '.png';
    Storage::disk('public')->put($filename, $imgData);

    // Update preview column (store path)
    $template->preview = $filename;
    $template->preview_front = $filename;

    if (empty($template->front_image)) {
        $template->front_image = $filename;
    }

    $this->synchronizeTemplateSideState($template);
    $template->save();

    return response()->json([
        'success' => true,
        'preview' => $filename,
        'preview_url' => ImageResolver::url($filename),
    ]);
}

    public function autosave(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'design' => 'required|array',
            'canvas' => 'nullable|array',
            'template_name' => 'nullable|string|max:255',
        ]);

        Log::info('Template autosave request received', [
            'template_id' => $template->id,
            'user_id' => optional($request->user())->id,
            'design_pages' => is_array($validated['design']['pages'] ?? null)
                ? count($validated['design']['pages'])
                : null,
        ]);

        $designPayload = $validated['design'];

        $template->design = json_encode($designPayload, JSON_UNESCAPED_UNICODE);

        if (!empty($validated['template_name'])) {
            $template->name = $validated['template_name'];
        }

        if (array_key_exists('canvas', $validated)) {
            $metadata = $template->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = (array) $metadata;
            }
            $metadata['builder_canvas'] = $validated['canvas'];
            $template->metadata = $metadata;
        }

        $template->save();

        Log::info('Template autosave persisted', [
            'template_id' => $template->id,
            'updated_at' => optional($template->updated_at)->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'saved_at' => optional($template->updated_at)->toIso8601String(),
        ]);
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
        $template = new Template([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'product_type' => 'Invitation',
            'preview' => 'invitation_templates/' . ltrim($frontPath, '/'),
            'front_image' => 'invitation_templates/' . ltrim($frontPath, '/'),
            'back_image' => 'invitation_templates/' . ltrim($backPath, '/'),
            'preview_front' => 'invitation_templates/' . ltrim($frontPath, '/'),
            'preview_back' => 'invitation_templates/' . ltrim($backPath, '/'),
            'has_back_design' => true,
        ]);

        $this->synchronizeTemplateSideState($template);
        $template->save();

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
                // fallback: list all files under templates/preview if dir missing
                if (Storage::disk('public')->exists('templates/preview')) {
                    $files = Storage::disk('public')->files('templates/preview');
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

        // Since design column was removed, store canvas settings in metadata instead
        $metadata = $template->metadata ?? [];
        if (!is_array($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        $metadata['canvas'] = [
            'width' => $w,
            'height' => $h,
            'shape' => $shape,
        ];

        $template->metadata = json_encode($metadata);
        $template->save();

        return response()->json(['success' => true, 'canvas' => $metadata['canvas']]);
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

        $productUploadData = [
            'product_id' => null,
            'template_name' => $template->name,
            'description' => $template->description,
            'product_type' => $template->product_type,
            'event_type' => $template->event_type,
            'theme_style' => $template->theme_style,
            'front_image' => $template->front_image,
            'back_image' => $template->back_image,
            'preview_image' => $template->preview,
            'design_data' => $template->metadata,
        ];

        // Always refresh the upload entry so returned templates can be re-submitted
        $productUpload = ProductUpload::updateOrCreate(
            ['template_id' => $template->id],
            $productUploadData
        );

        // Capture who uploaded and when for downstream notifications
        $metadata = $template->metadata ?? [];
        $metadata['last_uploaded_by_user_id'] = $staff->id ?? null;
        $metadata['last_uploaded_by_name'] = $staff->name ?? null;
        $metadata['last_uploaded_at'] = now()->toIso8601String();

        // Ensure the template returns to uploaded status with a fresh timestamp
        $template->forceFill([
            'status' => 'uploaded',
            'status_note' => null,
            'status_updated_at' => now(),
            'metadata' => $metadata,
        ])->save();

        // Flip any related products back to published once the template is re-uploaded
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

    public function reback(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($template, $data) {
            $template->forceFill([
                'status' => 'returned',
                'status_note' => $data['note'],
                'status_updated_at' => now(),
            ])->save();

            $products = Product::with(['uploads'])->where('template_id', $template->id)->get();

            // Remove stale uploads so staff can re-upload the returned template cleanly
            ProductUpload::where('template_id', $template->id)->delete();

            foreach ($products as $product) {
                $product->uploads()->delete();
                $product->forceFill([
                    'published_at' => null,
                    'unpublished_reason' => $data['note'],
                ])->save();
            }
        });

        // Notify the last uploader (staff) that the template was returned
        $metadata = $template->metadata ?? [];
        $staffUserId = $metadata['last_uploaded_by_user_id'] ?? null;
        $staffUser = $staffUserId ? User::find($staffUserId) : null;
        if ($staffUser) {
            $adminUser = Auth::user();
            $staffUser->notify(new TemplateReturnedNotification($template, $adminUser, $data['note'] ?? null));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Template sent back to staff for revisions.',
            ]);
        }

        return redirect()->back()->with('success', 'Template sent back to staff for revisions.');
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
            'template_video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200', // 50MB max for video
            'front_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'back_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ];

        $validated = $request->validate($rules);

        // Handle file uploads and store temporarily
        $previewData = $validated;

        // Store video file temporarily
        if ($request->hasFile('template_video')) {
            $videoPath = $request->file('template_video')->store('temp/previews/videos', 'public');
            $previewData['template_video_path'] = $videoPath;
            $previewData['template_video_name'] = $request->file('template_video')->getClientOriginalName();
        }

        // Store front image temporarily
        if ($request->hasFile('front_image')) {
            $frontPath = $request->file('front_image')->store('temp/previews/images', 'public');
            $previewData['front_image_path'] = $frontPath;
            $previewData['front_image_name'] = $request->file('front_image')->getClientOriginalName();
        }

        // Store back image temporarily
        if ($request->hasFile('back_image')) {
            $backPath = $request->file('back_image')->store('temp/previews/images', 'public');
            $previewData['back_image_path'] = $backPath;
            $previewData['back_image_name'] = $request->file('back_image')->getClientOriginalName();
        }

        // Store in session with a unique ID
        $previewId = uniqid('preview_');
        $previews = session('preview_templates', []);
        $previews[$previewId] = array_merge($previewData, [
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
        $template = new Template([
            'name' => $previewData['name'],
            'event_type' => $previewData['event_type'] ?? null,
            'product_type' => $previewData['product_type'] ?? 'Invitation',
            'theme_style' => $previewData['theme_style'] ?? null,
            'description' => $previewData['description'] ?? null,
        ]);

        // Prepare metadata array for additional preview assets

        $metadata = [];

        // Handle file storage - move from temp to permanent location
        if (isset($previewData['template_video_path'])) {
            $videoPath = str_replace('temp/previews/videos/', 'templates/videos/', $previewData['template_video_path']);
            if (Storage::disk('public')->exists($previewData['template_video_path'])) {
                Storage::disk('public')->move($previewData['template_video_path'], $videoPath);
                $metadata['preview_video'] = $videoPath;
            }
        }

        if (isset($previewData['front_image_path'])) {
            $frontPath = str_replace('temp/previews/images/', 'templates/', $previewData['front_image_path']);
            if (Storage::disk('public')->exists($previewData['front_image_path'])) {
                Storage::disk('public')->move($previewData['front_image_path'], $frontPath);
                $template->front_image = $frontPath;
            }
        }

        if (isset($previewData['back_image_path'])) {
            $backPath = str_replace('temp/previews/images/', 'templates/', $previewData['back_image_path']);
            if (Storage::disk('public')->exists($previewData['back_image_path'])) {
                Storage::disk('public')->move($previewData['back_image_path'], $backPath);
                $template->back_image = $backPath;
            }
        }

        if (!empty($metadata)) {
            $template->metadata = $metadata;
        }

        $this->synchronizeTemplateSideState($template, $metadata);

        $template->save();

        // Remove from session
        unset($previews[$previewId]);
        session(['preview_templates' => $previews]);

        $isStaff = str_contains(request()->route()->getPrefix(), 'staff');
        $isStaff = true;
        $editorRouteName = $isStaff ? 'staff.templates.editor' : 'admin.templates.editor';
        $redirectUrl = route($editorRouteName, $template->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template_id' => $template->id,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->route($editorRouteName, $template->id)->with('success', 'Template created successfully. You can continue editing it now.');
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

    /**
     * Align preview assets and back-side flags with the latest template data.
     */
    protected function synchronizeTemplateSideState(Template $template, ?array $metadata = null): void
    {
        $metadataArray = $metadata ?? $this->normalizeTemplateMetadata($template->metadata ?? []);

        $previews = [];
        if (isset($metadataArray['previews']) && is_array($metadataArray['previews'])) {
            $previews = $metadataArray['previews'];
        }

        $previewMeta = [];
        if (isset($metadataArray['preview_images_meta']) && is_array($metadataArray['preview_images_meta'])) {
            $previewMeta = $metadataArray['preview_images_meta'];
        }

        if (array_key_exists('front', $previews)) {
            $template->preview_front = $this->normalizePreviewPath($previews['front']);
        }

        $hasBackPreview = $this->previewCollectionHasBack($previews, $previewMeta);

        if ($hasBackPreview) {
            if (array_key_exists('back', $previews)) {
                $template->preview_back = $this->normalizePreviewPath($previews['back']);
            } elseif (!$this->isNonEmptyString($template->preview_back)) {
                $candidate = $this->findBackPreviewCandidate($previews, $previewMeta);
                if ($candidate !== null) {
                    $template->preview_back = $candidate;
                }
            }
        } elseif (!$this->isNonEmptyString($template->back_image)) {
            $template->preview_back = null;
        }

        if (!$this->isNonEmptyString($template->preview_front)) {
            $template->preview_front = $this->resolvePreviewFallback(
                $template,
                $previews,
                $template->front_image ?? null
            );
        }

        if ($this->isNonEmptyString($template->back_image) && !$this->isNonEmptyString($template->preview_back)) {
            $template->preview_back = $this->normalizePreviewPath($template->back_image);
        }

        if (!$this->isNonEmptyString($template->preview)) {
            if ($this->isNonEmptyString($template->preview_front)) {
                $template->preview = $template->preview_front;
            } elseif ($this->isNonEmptyString($template->front_image)) {
                $template->preview = $this->normalizePreviewPath($template->front_image);
            }
        }

        $template->has_back_design = $hasBackPreview
            || $this->isNonEmptyString($template->back_image)
            || $this->isNonEmptyString($template->back_svg_path);
    }

    protected function normalizeTemplateMetadata($metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if ($metadata instanceof \Illuminate\Support\Collection) {
            return $metadata->toArray();
        }

        if ($metadata instanceof \JsonSerializable) {
            $encoded = $metadata->jsonSerialize();
            return is_array($encoded) ? $encoded : [];
        }

        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($metadata)) {
            if (method_exists($metadata, 'toArray')) {
                $result = $metadata->toArray();
                return is_array($result) ? $result : (array) $result;
            }

            return (array) $metadata;
        }

        if ($metadata === null) {
            return [];
        }

        return (array) $metadata;
    }

    protected function previewCollectionHasBack(array $previews, array $previewMeta): bool
    {
        foreach ($previews as $key => $path) {
            if (!$this->isNonEmptyString($path)) {
                continue;
            }

            if ($this->isBackDescriptor((string) $key)) {
                return true;
            }

            $meta = $previewMeta[$key] ?? [];
            if (is_array($meta)) {
                foreach (['label', 'pageType', 'page_type'] as $field) {
                    if (isset($meta[$field]) && $this->isBackDescriptor((string) $meta[$field])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function findBackPreviewCandidate(array $previews, array $previewMeta): ?string
    {
        foreach ($previews as $key => $path) {
            if (!$this->isNonEmptyString($path)) {
                continue;
            }

            if ($this->isBackDescriptor((string) $key)) {
                return $this->normalizePreviewPath($path);
            }

            $meta = $previewMeta[$key] ?? [];
            if (is_array($meta)) {
                foreach (['label', 'pageType', 'page_type'] as $field) {
                    if (isset($meta[$field]) && $this->isBackDescriptor((string) $meta[$field])) {
                        return $this->normalizePreviewPath($path);
                    }
                }
            }
        }

        $entries = [];
        foreach ($previews as $key => $path) {
            if (!$this->isNonEmptyString($path)) {
                continue;
            }

            $order = isset($previewMeta[$key]['order']) ? (int) $previewMeta[$key]['order'] : PHP_INT_MAX;
            $entries[] = [
                'order' => $order,
                'key' => (string) $key,
                'path' => $this->normalizePreviewPath($path),
            ];
        }

        if (count($entries) < 2) {
            return null;
        }

        usort($entries, function (array $left, array $right) {
            if ($left['order'] === $right['order']) {
                return strcmp($left['key'], $right['key']);
            }

            return $left['order'] <=> $right['order'];
        });

        return $entries[1]['path'] ?? null;
    }

    protected function resolvePreviewFallback(Template $template, array $previews, ?string $directFallback = null): ?string
    {
        if ($this->isNonEmptyString($directFallback)) {
            return $this->normalizePreviewPath($directFallback);
        }

        if ($this->isNonEmptyString($template->preview)) {
            return $this->normalizePreviewPath($template->preview);
        }

        foreach ($previews as $path) {
            if ($this->isNonEmptyString($path)) {
                return $this->normalizePreviewPath($path);
            }
        }

        return null;
    }

    protected function isBackDescriptor(?string $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        $keywords = ['back', 'reverse', 'rear', 'backside', 'back-side', 'back_cover', 'backcover'];
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizePreviewPath($value): ?string
    {
        if (!$this->isNonEmptyString($value)) {
            return null;
        }

        return str_replace(["\r", "\n", "\t"], '', trim((string) $value));
    }

    protected function isNonEmptyString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    protected function persistDataUrl(string $dataUrl, string $directory, string $extension, ?string $existingPath, string $field): string
    {
        Log::info('persistDataUrl called', ['directory' => $directory, 'extension' => $extension, 'field' => $field, 'dataUrl_length' => strlen($dataUrl)]);
        if (trim((string) $dataUrl) === '') {
            Log::warning('persistDataUrl: empty dataUrl', ['field' => $field]);
            if ($existingPath) {
                return $existingPath;
            }
            throw ValidationException::withMessages([
                $field => 'Missing data payload.',
            ]);
        }

        try {
            $contents = $this->decodeDataUrl($dataUrl);
        } catch (\Throwable $e) {
            Log::error('persistDataUrl: decode failed', ['field' => $field, 'error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                $field => 'Invalid data payload provided.',
            ]);
        }

        $normalizedExistingPath = null;
        if ($existingPath) {
            $normalizedExistingPath = ltrim(str_replace('\\', '/', (string) $existingPath), '/');
            $normalizedExistingPath = preg_replace('#^/?storage/#i', '', $normalizedExistingPath) ?? $normalizedExistingPath;
        }

        if ($normalizedExistingPath && Storage::disk('public')->exists($normalizedExistingPath)) {
            Storage::disk('public')->delete($normalizedExistingPath);
        }

        $directory = trim($directory, '/');
        if ($directory !== '') {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = ($directory ? $directory . '/' : '') . 'template_' . Str::uuid() . '.' . $extension;

        $stored = Storage::disk('public')->put($filename, $contents);

        if (!$stored) {
            throw ValidationException::withMessages([
                $field => 'Failed to persist exported asset on disk.',
            ]);
        }

        return $filename;
    }

    protected function decodeDataUrl(string $dataUrl): string
    {
        if (!Str::startsWith($dataUrl, 'data:')) {
            throw new \InvalidArgumentException('Invalid data URL header.');
        }

        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid data URL structure.');
        }

        [$meta, $payload] = $parts;

        if (str_contains($meta, ';base64')) {
            $decoded = base64_decode($payload, true);
        } else {
            $decoded = rawurldecode($payload);
        }

        if ($decoded === false || $decoded === null) {
            throw new \RuntimeException('Unable to decode payload.');
        }

        return $decoded;
    }
}
