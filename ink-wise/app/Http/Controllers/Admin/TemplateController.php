<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    // Show all templates
    public function index()
    {
        $templates = Template::paginate(12); // Show 12 per page
        return view('admin.templates.index', compact('templates'));
    }

    // Show create form
    public function create()
    {
        return view('admin.templates.create');
    }

    // Store new template
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'theme_style' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'front_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'back_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

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

        $template = \App\Models\Template::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template_id' => $template->id,
                'redirect' => route('admin.templates.index'),
            ]);
        }

        return redirect()->route('admin.templates.index')->with('success', 'Template created successfully!');
    }

    // Show editor page for a template
    public function editor($id)
    {
        $template = Template::findOrFail($id);
        return view('admin.templates.editor', compact('template'));
    }
   
public function destroy($id)
{
    $template = Template::findOrFail($id);
    $template->delete();

    return redirect()->route('admin.templates.index')->with('success', 'Template deleted successfully.');
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

        return redirect()->route('admin.templates.index')->with('success', 'Custom template uploaded.');
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
}
