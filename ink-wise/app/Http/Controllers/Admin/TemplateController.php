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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Notifications\TemplateUploadedNotification;
use App\Notifications\TemplateReturnedNotification;
use App\Support\ImageResolver;
use App\Jobs\GenerateTemplatePreview;

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
            $rules['front_image'] = 'file|mimes:jpeg,png,jpg,gif,svg';
        }

        if ($request->hasFile('back_image')) {
            $rules['back_image'] = 'file|mimes:jpeg,png,jpg,gif,svg';
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
            'template_video' => 'nullable|file|mimes:mp4,mov,avi,webm',
        ];

        // Only require files if they are actually being uploaded
        // Allow creating template without files for direct editor access
        if ($request->hasFile('front_image')) {
            $rules['front_image'] = 'file|mimes:jpeg,png,jpg,gif,svg';
        }
        if ($request->hasFile('back_image')) {
            $rules['back_image'] = 'file|mimes:jpeg,png,jpg,gif,svg';
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

        // Fetch available sizes
        $sizes = \App\Models\ProductSize::with('material')
            ->select('id', 'size', 'size_type', 'price', 'material_id')
            ->get()
            ->map(function ($size) {
                return [
                    'id' => $size->id,
                    'size' => $size->size,
                    'type' => $size->size_type,
                    'price' => $size->price,
                    'material' => $size->material ? [
                        'id' => $size->material->material_id,
                        'name' => $size->material->material_name,
                        'type' => $size->material->material_type,
                    ] : null,
                ];
            });

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
            'width_inch' => $template->width_inch,
            'height_inch' => $template->height_inch,
            'fold_type' => $template->fold_type,
            'sizes' => $template->sizes,
            'selected_sizes' => $template->metadata['selected_sizes'] ?? [],
            'available_sizes' => $sizes,
            'updated_at' => optional($template->updated_at)->toIso8601String(),
        ];

        return view('staff.templates.editor', compact('template', 'templateBootstrap'));
    }

    // Load design JSON for editor (returns persisted JSON or DB design)
    public function loadDesign($id)
    {
        $template = Template::findOrFail($id);

        $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);

        // Prefer persisted design JSON file if present
        $jsonPath = $metadata['json_path'] ?? null;
        if ($jsonPath && Storage::disk('public')->exists($jsonPath)) {
            try {
                $contents = Storage::disk('public')->get($jsonPath);
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) {
                    return response()->json([
                        'success' => true,
                        'design' => $decoded,
                        'source' => 'json_file',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to read persisted design JSON', ['template_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        // Fallback to design stored on the model (could be JSON string or array)
        if (!empty($template->design)) {
            $design = $template->design;
            if (is_string($design)) {
                $decoded = json_decode($design, true);
                if (is_array($decoded)) {
                    return response()->json(['success' => true, 'design' => $decoded, 'source' => 'db_string']);
                }
            } elseif (is_array($design)) {
                return response()->json(['success' => true, 'design' => $design, 'source' => 'db_array']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Design not found'], 404);
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
        $this->expandCompressedPayload($request);
        $this->ensureTemplateStorageDirectories();
        
        Log::info('=== saveTemplate called ===', [
            'template_id' => $id,
            'request_keys' => array_keys($request->all()),
            'design_present' => $request->has('design'),
            'preview_image_present' => $request->has('preview_image'),
            'preview_image_length' => $request->has('preview_image') ? strlen($request->input('preview_image')) : 0,
            'svg_markup_present' => $request->has('svg_markup'),
            'svg_markup_length' => $request->has('svg_markup') ? strlen($request->input('svg_markup')) : 0,
        ]);
        
        $template = Template::findOrFail($id);

        // Prevent duplicate saves within a short time window
        if ($template->updated_at && $template->updated_at->diffInSeconds(now()) < 5) {
            Log::info('Template save skipped - recently saved', [
                'template_id' => $id,
                'last_updated' => $template->updated_at,
                'seconds_since' => $template->updated_at->diffInSeconds(now()),
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Template already saved recently',
                'template_id' => $template->id,
                'redirect' => route('staff.templates.index'),
            ]);
        }

        $validated = $request->validate([
            'design' => 'required|array',
            'svg_markup' => 'nullable|string',
            'preview_image' => 'nullable|string',
            'preview_images' => 'nullable|array',
            'preview_images_meta' => 'nullable|array',
            'template_name' => 'nullable|string|max:255',
            'template' => 'nullable|array',
            'template.*.selected_sizes' => 'nullable|array',
            'template.*.selected_sizes.*' => 'integer',
            'category' => 'nullable|string|max:255',
            'assets' => 'nullable|array',
            'preview_video' => 'nullable|string',
        ]);

        // Ensure the incoming design is structured (reject flattened/unsupported templates)
        $validated['design'] = $this->validateDesignStructure($validated['design']);

        Log::info('=== saveTemplate validation complete ===', [
            'id' => $id,
            'has_svg_markup' => !empty($validated['svg_markup']),
            'has_preview_image' => !empty($validated['preview_image']),
            'has_preview_images' => !empty($validated['preview_images']),
            'has_design' => !empty($validated['design']),
            'design_pages_count' => isset($validated['design']['pages']) ? count($validated['design']['pages']) : 0,
            'design_first_page_nodes' => isset($validated['design']['pages'][0]['nodes']) ? count($validated['design']['pages'][0]['nodes']) : 0,
        ]);
        
        // Check if design has actual content
        if (empty($validated['design']['pages']) || empty($validated['design']['pages'][0]['nodes'])) {
            Log::warning('⚠️  Template design has no pages or nodes - will save but preview will be empty', [
                'template_id' => $id,
                'has_pages' => !empty($validated['design']['pages']),
                'nodes_count' => isset($validated['design']['pages'][0]['nodes']) ? count($validated['design']['pages'][0]['nodes']) : 0,
            ]);
        }

        if (!empty($validated['template_name'])) {
            $template->name = $validated['template_name'];
        }

        // Persist design as JSON string in DB (canonical source-of-truth)
        $template->design = json_encode($validated['design'], JSON_UNESCAPED_UNICODE);

        // Save width and height in inches. Prefer explicit template fields if provided.
        if (!empty($validated['template']) && is_array($validated['template'])) {
            if (isset($validated['template']['width_inch'])) {
                $template->width_inch = (float) $validated['template']['width_inch'];
            }
            if (isset($validated['template']['height_inch'])) {
                $template->height_inch = (float) $validated['template']['height_inch'];
            }
            if (isset($validated['template']['fold_type'])) {
                $template->fold_type = $validated['template']['fold_type'];
            }
            if (isset($validated['template']['sizes'])) {
                $template->sizes = $validated['template']['sizes'];
            }
            if (isset($validated['template']['selected_sizes'])) {
                $metadata['selected_sizes'] = $validated['template']['selected_sizes'];
            }
        }

        // Fallback to design page dimensions if explicit template fields were not provided
        if ((is_null($template->width_inch) || is_null($template->height_inch)) && isset($validated['design']['pages'][0])) {
            $page = $validated['design']['pages'][0];
            $template->width_inch = round(($page['width'] ?? 400) / 96, 2);
            $template->height_inch = round(($page['height'] ?? 400) / 96, 2);
        }

        if (!empty($validated['category'])) {
            $template->event_type = $validated['category'];
        } elseif (!empty($validated['design']['category'])) {
            $template->event_type = $validated['design']['category'];
        }

        $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);

        // Persist JSON design file to disk for downstream consumers
        $designPath = $this->persistDesignJson(
            $validated['design'],
            'templates/assets',
            $metadata['json_path'] ?? null
        );
        $metadata['json_path'] = $designPath;

        if (!empty($validated['assets'])) {
            $metadata['assets'] = $validated['assets'];
        }

        if (isset($validated['design']['canvas']) && is_array($validated['design']['canvas'])) {
            $metadata['builder_canvas'] = $validated['design']['canvas'];
        }

        // Handle single preview_image for backward compatibility
        if (!empty($validated['preview_image'])) {
            try {
                Log::info('Processing preview_image', [
                    'length' => strlen($validated['preview_image']),
                    'starts_with_data' => str_starts_with($validated['preview_image'], 'data:'),
                ]);
                
                $template->preview = $this->persistDataUrl(
                    $validated['preview_image'],
                    'templates/preview',
                    'png',
                    $template->preview,
                    'preview_image'
                );
                Log::info('✓ Preview image saved successfully', ['path' => $template->preview]);
                // Also populate preview_front and front_image for consistency
                $template->preview_front = $template->preview;
                if (empty($template->front_image)) {
                    $template->front_image = $template->preview;
                }
            } catch (ValidationException $e) {
                Log::error('❌ Preview image save failed', ['error' => $e->getMessage()]);
            }
        } else {
            // No client preview provided. Persist a lightweight dummy and queue server-side preview generation.
            try {
                $dummyPreview = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
                $template->preview = $this->persistDataUrl(
                    $dummyPreview,
                    'templates/preview',
                    'png',
                    $template->preview,
                    'dummy_preview'
                );
                $template->preview_front = $template->preview;
                if (empty($template->front_image)) {
                    $template->front_image = $template->preview;
                }
                // Mark metadata that preview generation is queued
                $metadata['preview_status'] = 'queued';
                Log::info('Dummy preview saved and server-side preview queued', ['path' => $template->preview]);

                // Dispatch a job to generate a proper preview from the persisted design JSON
                try {
                    dispatch(new GenerateTemplatePreview($template->id, $designPath));
                } catch (\Throwable $jobEx) {
                    Log::warning('Failed to dispatch GenerateTemplatePreview job', ['error' => $jobEx->getMessage()]);
                }
            } catch (ValidationException $e) {
                Log::warning('Dummy preview save failed', ['error' => $e->getMessage()]);
            }
        }

        // Handle multiple preview_images
        if (!empty($validated['preview_images']) && is_array($validated['preview_images'])) {
            $previews = [];
            foreach ($validated['preview_images'] as $key => $imageData) {
                if (is_string($imageData) && !empty($imageData)) {
                    try {
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
                    } catch (ValidationException $e) {
                        Log::warning("Preview image save failed for key {$key}", ['error' => $e->getMessage()]);
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

        $svgPayload = $validated['svg_markup'] ?? null;
        if (!$svgPayload) {
            $svgPayload = $this->extractSvgFromDesign($validated['design']);
        }

        $savedSvg = false;
        if ($svgPayload) {
            try {
                $template->svg_path = $this->persistDataUrl(
                    $this->normalizeSvgPayload($svgPayload),
                    'templates/svg',
                    'svg',
                    $template->svg_path,
                    'provided_svg'
                );
                $savedSvg = true;
                Log::info('SVG saved from client payload/design', ['path' => $template->svg_path]);
            } catch (ValidationException $e) {
                Log::warning('Provided SVG save failed', ['error' => $e->getMessage()]);
            }
        }

        if (!$savedSvg) {
            // Generate SVG from the design JSON that was just saved
            $generatedSvg = null;
            try {
                // Use the design path we just saved to ensure we're using the latest design
                $generatedSvg = $this->generateSvgFromTemplate($template->id, $designPath, 0);
                Log::info('SVG generated from design JSON', ['design_path' => $designPath]);
            } catch (\Exception $e) {
                Log::warning('SVG generation from design failed', ['error' => $e->getMessage()]);
            }

            if ($generatedSvg) {
                try {
                    $template->svg_path = $this->persistDataUrl(
                        $generatedSvg,
                        'templates/svg',
                        'svg',
                        $template->svg_path,
                        'generated_svg'
                    );
                    $savedSvg = true;
                    Log::info('Generated SVG saved successfully', ['path' => $template->svg_path]);
                } catch (ValidationException $e) {
                    Log::warning('Generated SVG save failed', ['error' => $e->getMessage()]);
                }
            }
        }

        // Generate back SVG if there's a back page in the design
        $decodedDesign = json_decode($validated['design'], true);
        $backPageIndex = is_array($decodedDesign) ? $this->findBackPageIndex($decodedDesign) : null;
        if ($backPageIndex !== null) {
            try {
                $generatedBackSvg = $this->generateSvgFromTemplate($template->id, $designPath, $backPageIndex);
                if ($generatedBackSvg) {
                    $template->back_svg_path = $this->persistDataUrl(
                        $generatedBackSvg,
                        'templates/svg',
                        'svg',
                        $template->back_svg_path,
                        'generated_back_svg'
                    );
                    $template->has_back_design = true;
                    Log::info('Back SVG generated successfully', [
                        'path' => $template->back_svg_path,
                        'page_index' => $backPageIndex
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Back SVG generation failed', ['page_index' => $backPageIndex, 'error' => $e->getMessage()]);
            }
        }

        if (!$savedSvg) {
            // Fallback: save dummy SVG if no design data or generation failed
            Log::warning('No SVG could be generated, saving dummy SVG');
            $this->saveDummySvg($template);
        }

        // Optional video preview (base64/data URL)
        if (!empty($validated['preview_video'])) {
            try {
                $videoPath = $this->persistDataUrl(
                    $validated['preview_video'],
                    'templates/videos',
                    'mp4',
                    $metadata['preview_video'] ?? null,
                    'preview_video'
                );
                $metadata['preview_video'] = $videoPath;
            } catch (ValidationException $e) {
                Log::warning('Preview video save failed', ['error' => $e->getMessage()]);
            }
        }

        $template->metadata = $metadata;
        $this->synchronizeTemplateSideState($template, $metadata);
        $template->status = 'draft'; // Always set to draft when manually saving
        $template->save();

        Log::info('Template saved successfully', [
            'id' => $template->id,
            'name' => $template->name,
            'preview' => $template->preview,
            'svg_path' => $template->svg_path,
            'json_path' => $metadata['json_path'] ?? null,
            'updated_at' => $template->updated_at,
        ]);

        $prefix = $request->route()->getPrefix();
        $isStaff = str_contains($prefix ?? '', 'staff');
        $isStaff = true;
        $redirectRoute = $isStaff ? 'staff.templates.index' : 'admin.templates.index';

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
            'redirect' => route($redirectRoute),
            'preview_url' => $template->preview ? \App\Support\ImageResolver::url($template->preview) : null,
            'preview_path' => $template->preview,
            'svg_path' => $template->svg_path,
            'back_svg_path' => $template->back_svg_path,
            'json_path' => $metadata['json_path'] ?? null,
        ]);
    }

    /**
     * Save exported assets (PNG, SVG, JSON) coming from the Konva editor.
     */
    public function saveDesign(Request $request, $id)
    {
        $this->ensureTemplateStorageDirectories();

        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'png' => 'nullable|string',
            'json' => 'required|string',
            'png_back' => 'nullable|string',
            'json_back' => 'nullable|string',
        ]);

        // Decode and validate the structured design JSON (reject flattened/unsupported uploads)
        $decodedDesign = json_decode($validated['json'], true);
        if (!is_array($decodedDesign)) {
            throw ValidationException::withMessages(['json' => 'Invalid design JSON payload.']);
        }
        $normalizedDesign = $this->validateDesignStructure($decodedDesign);

        $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);

        // Persist JSON design file to disk and keep path in metadata
        $designPath = $this->persistDesignJson(
            $normalizedDesign,
            'templates/assets',
            $metadata['json_path'] ?? null
        );
        $metadata['json_path'] = $designPath;

        // Save to DB column only if it fits in standard packets (approx 1MB)
        $designJson = json_encode($normalizedDesign, JSON_UNESCAPED_UNICODE);
        if (strlen($designJson) < 900000) {
            $template->design = $designJson;
        } else {
            Log::info('Design payload too large for DB column update, relied on JSON file', [
                'template_id' => $template->id,
                'length' => strlen($designJson)
            ]);
        }

        // Capture canvas state if present
        if (isset($normalizedDesign['canvas']) && is_array($normalizedDesign['canvas'])) {
            $metadata['builder_canvas'] = $normalizedDesign['canvas'];
        }

        // Persist front preview PNG if provided (optional)
        if (!empty($validated['png'])) {
            $frontPngPath = $this->persistDataUrl(
                $validated['png'],
                'templates/previews',
                'png',
                $template->preview,
                'png'
            );
            $template->preview = $frontPngPath;
            $template->preview_front = $frontPngPath;
        }

        // Generate and persist SVG from the validated design to ensure synchronization
        // This ensures the SVG file matches the JSON logic even if autosave relied on client-side export
        try {
            $generatedSvg = $this->generateSvgFromTemplate($template->id, $designPath, 0);
            if ($generatedSvg) {
                $template->svg_path = $this->persistDataUrl(
                    $generatedSvg,
                    'templates/svg',
                    'svg',
                    $template->svg_path,
                    'generated_svg'
                );
                Log::info('SVG regenerated during saveDesign', ['path' => $template->svg_path]);
            }
        } catch (\Exception $e) {
            Log::warning('SVG regeneration failed during saveDesign', ['template_id' => $id, 'error' => $e->getMessage()]);
        }

        // Generate and persist back SVG if there's a back page in the design
        $backPageIndex = $this->findBackPageIndex($normalizedDesign);
        if ($backPageIndex !== null) {
            try {
                $generatedBackSvg = $this->generateSvgFromTemplate($template->id, $designPath, $backPageIndex);
                if ($generatedBackSvg) {
                    $template->back_svg_path = $this->persistDataUrl(
                        $generatedBackSvg,
                        'templates/svg',
                        'svg',
                        $template->back_svg_path,
                        'generated_back_svg'
                    );
                    $template->has_back_design = true;
                    Log::info('Back SVG regenerated during saveDesign', [
                        'path' => $template->back_svg_path, 
                        'page_index' => $backPageIndex
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Back SVG regeneration failed during saveDesign', [
                    'template_id' => $id, 
                    'page_index' => $backPageIndex,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Optional back side support (JSON + preview only)
        if ($template->has_back_design) {
            if (!empty($validated['png_back'])) {
                $template->preview_back = $this->persistDataUrl(
                    $validated['png_back'],
                    'templates/previews',
                    'png',
                    $template->preview_back,
                    'png_back'
                );
            }

            if (!empty($validated['json_back'])) {
                $decodedBack = json_decode($validated['json_back'], true);
                if (!is_array($decodedBack)) {
                    throw ValidationException::withMessages(['json_back' => 'Invalid back-side design JSON payload.']);
                }
                $normalizedBack = $this->validateDesignStructure($decodedBack);
                $metadata['back_design_json'] = $normalizedBack;
                $metadata['back_json_path'] = $this->persistDesignJson(
                    $normalizedBack,
                    'templates/assets',
                    $metadata['back_json_path'] ?? null
                );
            }
        }

        $template->metadata = $metadata;

        $template->status = $template->status ?: 'draft';
        $template->updated_at = now();
        $template->save();

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
            'preview' => $template->preview,
            'preview_back' => $template->preview_back,
            'svg_path' => $template->svg_path,
            'back_svg_path' => $template->back_svg_path,
            'json_path' => $metadata['json_path'] ?? null,
            'back_json_path' => $metadata['back_json_path'] ?? null,
        ]);
    }

    /**
     * Persist raw SVG markup coming from the staff SVG editor.
     */
    public function saveSvg(Request $request, $id)
    {
        $validated = $request->validate([
            'svg_content' => 'required|string',
            'side' => 'nullable|in:front,back',
        ]);

        $side = $validated['side'] ?? 'front';
        $template = Template::findOrFail($id);

        $this->ensureTemplateStorageDirectories();

        $relativePath = $side === 'back'
            ? "templates/svg/template_{$id}_back.svg"
            : "templates/svg/template_{$id}.svg";

        try {
            $storedPath = $this->storeSvgPayload($validated['svg_content'], $relativePath, 'svg_content');
        } catch (ValidationException $e) {
            Log::warning('saveSvg validation failed', [
                'template_id' => $id,
                'side' => $side,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('saveSvg unexpected failure', [
                'template_id' => $id,
                'side' => $side,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to save SVG. Please try again.',
            ], 500);
        }

        if ($side === 'back') {
            $template->back_svg_path = $storedPath;
            $template->has_back_design = true;
        } else {
            $template->svg_path = $storedPath;
        }

        $template->status = $template->status ?: 'draft';
        $this->synchronizeTemplateSideState($template);
        $template->updated_at = now();
        $template->save();

        return response()->json([
            'success' => true,
            'svg_path' => $template->svg_path,
            'back_svg_path' => $template->back_svg_path,
            'side' => $side,
            'svg_url' => $storedPath ? \App\Support\ImageResolver::url($storedPath) : null,
        ]);
    }

    public function testSave(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        // Create dummy preview image (small white PNG)
        $dummyPreview = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        // Create dummy SVG
        $dummySvg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="white"/></svg>');

        $this->ensureTemplateStorageDirectories();

        $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);

        // Persist dummy design JSON
        $designPath = $this->persistDesignJson(['test' => 'data'], 'templates/assets', $metadata['json_path'] ?? null);
        $metadata['json_path'] = $designPath;

        // Save dummy preview
        $template->preview = $this->persistDataUrl($dummyPreview, 'templates/preview', 'png', $template->preview, 'test_preview');
        $template->preview_front = $template->preview;

        // Save dummy SVG
        $template->svg_path = $this->persistDataUrl($dummySvg, 'templates/svg', 'svg', $template->svg_path, 'test_svg');

        $template->metadata = $metadata;
        $this->synchronizeTemplateSideState($template, $metadata);
        $template->status = 'draft';
        $template->save();

        Log::info('Test save completed', [
            'id' => $template->id,
            'preview' => $template->preview,
            'svg_path' => $template->svg_path,
            'json_path' => $metadata['json_path'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test save completed',
            'preview' => $template->preview,
            'svg_path' => $template->svg_path,
            'json_path' => $metadata['json_path'],
        ]);
    }

public function uploadPreview(Request $request, $id)
{
    $template = Template::findOrFail($id);
    $imgData = $request->input('preview_image');

    // Save to storage with optimization (reduces file size by 60-80%)
    $filename = 'templates/previews/template_' . $id . '_' . time() . '.png';
    \App\Support\ImageOptimizer::optimizePreview($imgData, $filename, 400, 75);

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
        $this->expandCompressedPayload($request);
        $template = Template::findOrFail($id);

        // Prevent duplicate autosaves within a short time window
        if ($template->updated_at && $template->updated_at->diffInSeconds(now()) < 2) {
            Log::info('Template autosave skipped - recently saved', [
                'template_id' => $id,
                'last_updated' => $template->updated_at,
                'seconds_since' => $template->updated_at->diffInSeconds(now()),
            ]);
            return response()->json([
                'success' => true,
                'saved_at' => $template->updated_at->toIso8601String(),
            ]);
        }

        $validated = $request->validate([
            'design' => 'required|array',
            'canvas' => 'nullable|array',
            'template_name' => 'nullable|string|max:255',
            'template' => 'nullable|array',
            'svg_markup' => 'nullable|string',
        ]);

        Log::info('Template autosave request received', [
            'template_id' => $template->id,
            'user_id' => optional($request->user())->id,
            'design_pages' => is_array($validated['design']['pages'] ?? null)
                ? count($validated['design']['pages'])
                : null,
        ]);

        // Validate structure to reject flattened/unsupported autosaves
        $designPayload = $this->validateDesignStructure($validated['design']);

        // Persist JSON design file to disk reliably
        $this->ensureTemplateStorageDirectories();
        $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);
        
        $designPath = $this->persistDesignJson(
            $designPayload,
            'templates/assets',
            $metadata['json_path'] ?? null
        );
        $metadata['json_path'] = $designPath;

        // Save to DB only if within packet limits (approx 1MB safely)
        $designJson = json_encode($designPayload, JSON_UNESCAPED_UNICODE);
        if (strlen($designJson) < 900000) {
            $template->design = $designJson;
        } else {
            Log::info('Design payload too large for DB column update, relied on JSON file', [
                'template_id' => $template->id,
                'length' => strlen($designJson)
            ]);
        }

        if (!empty($validated['template_name'])) {
            $template->name = $validated['template_name'];
        }

        // Persist optional template fields (width/height/fold_type/sizes) if provided during autosave
        if (!empty($validated['template']) && is_array($validated['template'])) {
            if (isset($validated['template']['width_inch'])) {
                $template->width_inch = (float) $validated['template']['width_inch'];
            }
            if (isset($validated['template']['height_inch'])) {
                $template->height_inch = (float) $validated['template']['height_inch'];
            }
            if (isset($validated['template']['fold_type'])) {
                $template->fold_type = $validated['template']['fold_type'];
            }
            if (isset($validated['template']['sizes'])) {
                $template->sizes = $validated['template']['sizes'];
            }
        }

        if (array_key_exists('canvas', $validated)) {
            if (!is_array($metadata)) {
                $metadata = (array) $metadata;
            }
            $metadata['builder_canvas'] = $validated['canvas'];
        }

        $savedSvg = false;

        // Strategy: First attempt to generate SVG from the backend (server-side generation)
        // This ensures structural completeness (all layers, background shapes) which might be missing in client export.
        // The "regenerated" SVG is considered the source of truth for layer presence.
        try {
            // Use the freshly saved designPath to ensure synchronization using the latest JSON
            $generatedSvg = $this->generateSvgFromTemplate($template->id, $designPath);
            if ($generatedSvg) {
                $template->svg_path = $this->persistDataUrl(
                    $generatedSvg,
                    'templates/svg',
                    'svg',
                    $template->svg_path,
                    'generated_svg'
                );
                $savedSvg = true;
                Log::info('Generated SVG saved during autosave', ['path' => $template->svg_path]);
            }
        } catch (\Exception $e) {
            Log::warning('SVG generation from template failed during autosave', ['error' => $e->getMessage()]);
        }

        // Fallback: If backend generation failed, try to use client-supplied SVG
        if (!$savedSvg) {
            $svgPayload = $validated['svg_markup'] ?? null;
            if (!$svgPayload) {
                $svgPayload = $this->extractSvgFromDesign($designPayload);
            }

            if ($svgPayload) {
                try {
                    $template->svg_path = $this->persistDataUrl(
                        $this->normalizeSvgPayload($svgPayload),
                        'templates/svg',
                        'svg',
                        $template->svg_path,
                        'provided_svg'
                    );
                    $savedSvg = true;
                    Log::info('Provided SVG saved during autosave (fallback)', ['path' => $template->svg_path]);
                } catch (ValidationException $e) {
                    Log::warning('Provided SVG save failed during autosave', ['error' => $e->getMessage()]);
                }
            }
        }

        if (!$savedSvg) {
            // Fallback: save dummy SVG if no design data or all attempts fail
            $this->saveDummySvg($template);
        }

        $template->metadata = $metadata;
        
        try {
            $template->save();
        } catch (\Exception $e) {
            Log::error('Template autosave DB persistence failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'design_length' => strlen($template->design ?? ''),
            ]);
            
            // If the error is likely due to packet size, we still returned success if files were saved?
            // Actually, we should probably tell the frontend it failed if we couldn't update the record.
            return response()->json([
                'success' => false,
                'error' => 'Database save failed. The design might be too large for the current server configuration.',
                'message' => $e->getMessage(),
            ], 500);
        }

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
            'front_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg',
            'back_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg',
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
                // fallback: list all files under saved_templates/previews if dir missing
                if (Storage::disk('public')->exists('saved_templates/previews')) {
                    $files = Storage::disk('public')->files('saved_templates/previews');
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
        Log::info("Request received: {$request->method()} {$request->path()}");
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

        // Create or update a product
        $product = $template->products()->first();
        if ($product) {
            // Update existing product
            $product->update([
                'name' => $template->name,
                'product_type' => $template->product_type,
                'event_type' => $template->event_type,
                'base_price' => $template->base_price ?? 100,
                'description' => $template->description,
                'theme_style' => $template->theme_style,
            ]);
        } else {
            // Create new product
            $product = Product::create([
                'name' => $template->name,
                'product_type' => $template->product_type,
                'event_type' => $template->event_type,
                'template_id' => $template->id,
                'base_price' => $template->base_price ?? 100,
                'description' => $template->description,
                'theme_style' => $template->theme_style,
            ]);
        }

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
            'template_video' => 'nullable|file|mimes:mp4,mov,avi,webm',
            'front_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'back_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
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
        
        // Also check if design has a back page
        $hasBackDesignPage = $this->designHasBackPage($template);

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
            || $hasBackDesignPage
            || $this->isNonEmptyString($template->back_image)
            || $this->isNonEmptyString($template->back_svg_path);
    }

    protected function ensureTemplateStorageDirectories(): void
    {
        $paths = [
            storage_path('app/public/templates'),
            storage_path('app/public/templates/svg'),
            storage_path('app/public/templates/preview'),
            storage_path('app/public/templates/previews'),
            storage_path('app/public/templates/assets'),
            storage_path('app/public/templates/videos'),
        ];

        foreach ($paths as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true, true);
            } elseif (!is_writable($dir)) {
                @chmod($dir, 0775);
            }
        }
    }

    protected function persistDesignJson(array $payload, string $directory, ?string $existingPath = null): string
    {
        $directory = trim($directory, '/');
        $disk = Storage::disk('public');

        if ($existingPath) {
            $normalized = ltrim(str_replace(['\\', 'storage/'], ['/', ''], (string) $existingPath), '/');
            if ($disk->exists($normalized)) {
                $disk->delete($normalized);
            }
        }

        $disk->makeDirectory($directory);

        $filename = ($directory ? $directory . '/' : '') . 'template_' . Str::uuid() . '.json';
        $disk->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filename;
    }

    /**
     * Validate that a design payload contains structured pages and layers we can persist.
     * Rejects flattened uploads (e.g., a single rasterized image) by requiring at least one node with a frame.
     */
    protected function validateDesignStructure(array $design): array
    {
        if (empty($design['pages']) || !is_array($design['pages'])) {
            throw ValidationException::withMessages([
                'design' => 'Template must contain at least one page with layers.',
            ]);
        }

        $allowedTypes = ['text', 'image', 'shape'];
        $hasValidNode = false;

        foreach ($design['pages'] as $pageIndex => $page) {
            $nodes = $page['nodes'] ?? $page['layers'] ?? null;
            if (!is_array($nodes) || count($nodes) === 0) {
                continue;
            }

            foreach ($nodes as $nodeIndex => $node) {
                $type = $node['type'] ?? null;
                if (!in_array($type, $allowedTypes, true)) {
                    throw ValidationException::withMessages([
                        'design' => "Unsupported layer type '{$type}' at page {$pageIndex}, node {$nodeIndex}.",
                    ]);
                }

                $frame = $node['frame'] ?? null;
                if (!is_array($frame) || !isset($frame['x'], $frame['y'], $frame['width'], $frame['height'])) {
                    throw ValidationException::withMessages([
                        'design' => 'Each layer must include position (x, y) and size (width, height).',
                    ]);
                }

                // Reject layers with zero dimensions which indicate flattened or invalid content
                if (($frame['width'] ?? 0) <= 0 || ($frame['height'] ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        'design' => 'Layer width and height must be greater than zero.',
                    ]);
                }

                if ($type === 'text') {
                    $text = $node['content'] ?? '';
                    if (!is_string($text) || trim($text) === '') {
                        throw ValidationException::withMessages([
                            'design' => 'Text layers must include text content.',
                        ]);
                    }
                }

                if ($type === 'image') {
                    $src = $node['content'] ?? ($node['src'] ?? null);
                    if (!$src || !is_string($src)) {
                        throw ValidationException::withMessages([
                            'design' => 'Image layers must include an image source.',
                        ]);
                    }
                }

                $hasValidNode = true;
            }
        }

        if (!$hasValidNode) {
            throw ValidationException::withMessages([
                'design' => 'Template appears flattened or unsupported. No editable layers were detected.',
            ]);
        }

        return $design;
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

    /**
     * Check if the template's design JSON contains a back page.
     */
    protected function designHasBackPage(Template $template): bool
    {
        $design = $template->design;
        
        if (is_string($design)) {
            $design = json_decode($design, true);
        }
        
        if (!is_array($design) || empty($design['pages'])) {
            return false;
        }
        
        // Check if there are multiple pages (at least 2 implies front + back)
        if (count($design['pages']) >= 2) {
            return true;
        }
        
        // Also check if any page has a pageType indicating it's a back page
        foreach ($design['pages'] as $page) {
            $pageType = $page['pageType'] ?? null;
            $metadataPageType = $page['metadata']['pageType'] ?? null;
            $metadataSide = $page['metadata']['side'] ?? null;
            
            if ($this->isBackDescriptor($pageType) 
                || $this->isBackDescriptor($metadataPageType)
                || $this->isBackDescriptor($metadataSide)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Find the index of the back page in a design's pages array.
     * Returns null if no back page found.
     * 
     * @param Template|array $designSource Template model or decoded design array
     * @return int|null Page index for the back page, or null if not found
     */
    protected function findBackPageIndex($designSource): ?int
    {
        if ($designSource instanceof Template) {
            $design = $designSource->design;
            if (is_string($design)) {
                $design = json_decode($design, true);
            }
        } else {
            $design = $designSource;
        }
        
        if (!is_array($design) || empty($design['pages'])) {
            return null;
        }
        
        // If exactly 2 pages, assume index 1 is the back page
        if (count($design['pages']) === 2) {
            return 1;
        }
        
        // Otherwise, look for a page explicitly marked as back
        foreach ($design['pages'] as $index => $page) {
            $pageType = $page['pageType'] ?? null;
            $metadataPageType = $page['metadata']['pageType'] ?? null;
            $metadataSide = $page['metadata']['side'] ?? null;
            
            if ($this->isBackDescriptor($pageType) 
                || $this->isBackDescriptor($metadataPageType)
                || $this->isBackDescriptor($metadataSide)) {
                return $index;
            }
        }
        
        // If more than 2 pages but none marked as back, return index 1 as default
        if (count($design['pages']) > 1) {
            return 1;
        }
        
        return null;
    }

    protected function expandCompressedPayload(Request $request): void
    {
        if (!$request->filled('compressed_payload')) {
            return;
        }

        $encoding = strtolower((string) $request->input('payload_encoding', 'deflate-base64'));
        $compressed = $request->input('compressed_payload');

        if (!is_string($compressed) || trim($compressed) === '') {
            throw ValidationException::withMessages([
                'compressed_payload' => 'Compressed payload is empty.',
            ]);
        }

        $binary = base64_decode($compressed, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'compressed_payload' => 'Compressed payload must be valid base64 data.',
            ]);
        }

        switch ($encoding) {
            case 'deflate-base64':
            case 'zlib-base64':
                $json = $this->attemptPayloadInflate($binary);
                break;
            case 'gzip-base64':
                $json = @gzdecode($binary);
                break;
            default:
                throw ValidationException::withMessages([
                    'payload_encoding' => 'Unsupported payload encoding: ' . $encoding,
                ]);
        }

        if ($json === false || $json === null || $json === '') {
            $this->logFailedInflateSample($binary);
            throw ValidationException::withMessages([
                'compressed_payload' => 'Failed to decompress payload contents.',
            ]);
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'compressed_payload' => 'Decompressed payload is not valid JSON.',
            ]);
        }

        $request->merge($decoded);
        $request->request->remove('compressed_payload');
        $request->request->remove('payload_encoding');
        if ($request->request->has('payload_version')) {
            $request->request->remove('payload_version');
        }

        Log::info('Compressed payload expanded', [
            'encoding' => $encoding,
            'expanded_keys' => array_keys($decoded),
            'compressed_bytes' => strlen($binary),
            'expanded_bytes' => strlen($json),
        ]);
    }

    protected function attemptPayloadInflate(string $binary): string|false
    {
        $attempts = [
            'gzuncompress' => fn ($payload) => @gzuncompress($payload),
            'gzinflate' => fn ($payload) => @gzinflate($payload),
            'gzdecode' => fn ($payload) => @gzdecode($payload),
            'zlib_decode' => function ($payload) {
                if (!function_exists('zlib_decode')) {
                    return false;
                }
                return @zlib_decode($payload);
            },
            'inflate_raw' => function ($payload) {
                if (!function_exists('inflate_init') || !defined('ZLIB_ENCODING_RAW')) {
                    return false;
                }
                $resource = @inflate_init(ZLIB_ENCODING_RAW);
                if ($resource === false) {
                    return false;
                }
                $result = @inflate_add($resource, $payload, ZLIB_FINISH);
                if ($result === false || $result === null || $result === '') {
                    return false;
                }
                return $result;
            },
            'inflate_zlib' => function ($payload) {
                if (!function_exists('inflate_init') || !defined('ZLIB_ENCODING_ZLIB')) {
                    return false;
                }
                $resource = @inflate_init(ZLIB_ENCODING_ZLIB);
                if ($resource === false) {
                    return false;
                }
                $result = @inflate_add($resource, $payload, ZLIB_FINISH);
                if ($result === false || $result === null || $result === '') {
                    return false;
                }
                return $result;
            },
            'inflate_gzip' => function ($payload) {
                if (!function_exists('inflate_init') || !defined('ZLIB_ENCODING_GZIP')) {
                    return false;
                }
                $resource = @inflate_init(ZLIB_ENCODING_GZIP);
                if ($resource === false) {
                    return false;
                }
                $result = @inflate_add($resource, $payload, ZLIB_FINISH);
                if ($result === false || $result === null || $result === '') {
                    return false;
                }
                return $result;
            },
        ];

        foreach ($attempts as $label => $callback) {
            $result = $callback($binary);
            if ($result !== false && $result !== null && $result !== '') {
                Log::debug('Compressed payload inflate succeeded', ['strategy' => $label, 'compressed_bytes' => strlen($binary)]);
                return $result;
            }
        }

        Log::warning('Compressed payload inflate failed using available strategies', ['compressed_bytes' => strlen($binary)]);
        return false;
    }

    protected function logFailedInflateSample(string $binary): void
    {
        $prefix = substr($binary, 0, 16);
        $hex = bin2hex($prefix);
        Log::debug('Compressed payload header sample', [
            'hex_prefix' => $hex,
            'length' => strlen($binary),
        ]);
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

    protected function storeDataUrlToPublicPath(string $dataUrl, string $relativePath, string $field): string
    {
        try {
            $contents = $this->decodeDataUrl($dataUrl);
        } catch (\Throwable $e) {
            Log::error('storeDataUrlToPublicPath decode failed', ['field' => $field, 'error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                $field => 'Invalid data payload provided.',
            ]);
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $directory = trim(pathinfo($relativePath, PATHINFO_DIRNAME), '/');
        if ($directory !== '') {
            Storage::disk('public')->makeDirectory($directory);
        }

        Storage::disk('public')->put($relativePath, $contents);

        return $relativePath;
    }

    protected function storeSvgPayload(string $payload, string $relativePath, string $field): string
    {
        return $this->storeDataUrlToPublicPath($this->normalizeSvgPayload($payload), $relativePath, $field);
    }

    /**
     * Pull an SVG string from common design payload shapes (sides/pages/root svg).
     */
    protected function extractSvgFromDesign($design): ?string
    {
        if (!is_array($design)) {
            return null;
        }

        if (!empty($design['svg']) && is_string($design['svg'])) {
            return $design['svg'];
        }

        if (!empty($design['sides']) && is_array($design['sides'])) {
            foreach ($design['sides'] as $side) {
                if (is_array($side) && !empty($side['svg']) && is_string($side['svg'])) {
                    return $side['svg'];
                }
            }
        }

        if (!empty($design['pages']) && is_array($design['pages'])) {
            foreach ($design['pages'] as $page) {
                if (is_array($page) && !empty($page['svg']) && is_string($page['svg'])) {
                    return $page['svg'];
                }
            }
        }

        return null;
    }

    protected function normalizeSvgPayload(string $payload): string
    {
        $trimmed = trim($payload);
        if (Str::startsWith($trimmed, 'data:image/svg+xml')) {
            return $trimmed;
        }

        return 'data:image/svg+xml;base64,' . base64_encode($trimmed);
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

    /**
     * Generate SVG content from design data
     *
     * @param string|array $designData JSON string or array of design data
     * @param int $pageIndex Page index to generate SVG for (0 = front, 1 = back)
     * @return string|null Base64 encoded data URL of generated SVG or null if generation fails
     */
    protected function generateSvgFromTemplate($templateId, $jsonPath = null, int $pageIndex = 0): ?string
    {
        try {
            $template = Template::findOrFail($templateId);
            $metadata = $this->normalizeTemplateMetadata($template->metadata ?? []);

            // Use provided jsonPath if given (for when metadata not yet saved)
            $jsonPath = $jsonPath ?? $metadata['json_path'] ?? null;
            if ($jsonPath && Storage::disk('public')->exists($jsonPath)) {
                try {
                    $contents = Storage::disk('public')->get($jsonPath);
                    $decoded = json_decode($contents, true);
                    if (is_array($decoded)) {
                        $designData = $decoded;
                        Log::info('Loaded design data from asset file for SVG generation', ['template_id' => $templateId, 'json_path' => $jsonPath, 'page_index' => $pageIndex]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to read design JSON from asset file', ['template_id' => $templateId, 'json_path' => $jsonPath, 'error' => $e->getMessage()]);
                }
            }

            // Fallback to design stored on the model
            if (!isset($designData) && !empty($template->design)) {
                $design = $template->design;
                if (is_string($design)) {
                    $decoded = json_decode($design, true);
                    if (is_array($decoded)) {
                        $designData = $decoded;
                        Log::info('Loaded design data from database for SVG generation', ['template_id' => $templateId, 'page_index' => $pageIndex]);
                    }
                } elseif (is_array($design)) {
                    $designData = $design;
                    Log::info('Loaded design data from database array for SVG generation', ['template_id' => $templateId, 'page_index' => $pageIndex]);
                }
            }

            if (!isset($designData)) {
                Log::warning('No design data found for SVG generation', ['template_id' => $templateId]);
                return null;
            }

            // Extract pages and layers - use the specified page index
            $page = null;
            if (isset($designData['pages']) && is_array($designData['pages'])) {
                $page = $designData['pages'][$pageIndex] ?? null;
            }
            
            if (!$page) {
                Log::warning('Requested page not found for SVG generation', ['template_id' => $templateId, 'page_index' => $pageIndex, 'available_pages' => count($designData['pages'] ?? [])]);
                return null;
            }

            $layers = [];
            if ($page) {
                if (isset($page['layers']) && is_array($page['layers'])) {
                    $layers = $page['layers'];
                } elseif (isset($page['nodes']) && is_array($page['nodes'])) {
                    $layers = $page['nodes'];
                }
            }

            // Get canvas dimensions from page definition or fallback
            $canvasWidth = isset($page['width']) ? (int) $page['width'] : 414;
            $canvasHeight = isset($page['height']) ? (int) $page['height'] : 896;
            $pageBackground = $page['background'] ?? null;

            // Separate text, image, and shape layers
            $textElements = [];
            $imageElements = [];
            $shapeElements = [];
            foreach ($layers as $layer) {
                if (!isset($layer['visible']) || !$layer['visible']) {
                    continue;
                }

                $frame = $layer['frame'] ?? [];
                $frameX = $frame['x'] ?? 0;
                $frameY = $frame['y'] ?? 0;
                $frameWidth = $frame['width'] ?? 0;
                $frameHeight = $frame['height'] ?? 0;

                if (($layer['type'] ?? null) === 'text') {
                    $textAlign = $layer['textAlign'] ?? 'center';
                    $x = match ($textAlign) {
                        'right' => $frameX + $frameWidth,
                        'left' => $frameX,
                        default => $frameX + ($frameWidth / 2),
                    };
                    $y = $frameY + ($frameHeight / 2);

                    $textElements[] = [
                        'x' => $x,
                        'y' => $y,
                        'text' => $layer['content'] ?? '',
                        'font_size' => $layer['fontSize'] ?? 24,
                        'color' => $layer['fill'] ?? '#000000',
                        'font_family' => $layer['fontFamily'] ?? 'Arial, sans-serif',
                        'font_weight' => $layer['fontWeight'] ?? 'normal',
                        'text_align' => $textAlign,
                    ];
                } elseif (($layer['type'] ?? null) === 'image') {
                    $rawSrc = $layer['src'] ?? ($layer['content'] ?? null);
                    if ($rawSrc && !str_starts_with($rawSrc, 'data:image')) {
                        $mime = $this->guessImageMime($rawSrc) ?? 'image/jpeg';
                        $rawSrc = 'data:' . $mime . ';base64,' . $rawSrc;
                    }

                    $imageElements[] = [
                        'x' => $frameX,
                        'y' => $frameY,
                        'width' => $frameWidth ?: 150,
                        'height' => $frameHeight ?: 150,
                        'src' => $rawSrc,
                        'borderRadius' => $layer['borderRadius'] ?? 0,
                    ];
                } elseif (($layer['type'] ?? null) === 'shape') {
                    // Check if this is an image frame shape with image content
                    $isImageFrame = $layer['metadata']['isImageFrame'] ?? false;
                    $imageContent = $layer['content'] ?? null;

                    if ($isImageFrame && $imageContent && str_starts_with($imageContent, 'data:image')) {
                        // Render as image instead of shape since it has image content
                        $imageElements[] = [
                            'x' => $frameX,
                            'y' => $frameY,
                            'width' => $frameWidth ?: 150,
                            'height' => $frameHeight ?: 150,
                            'src' => $imageContent,
                            'borderRadius' => $layer['borderRadius'] ?? 0,
                            'objectFit' => $layer['metadata']['objectFit'] ?? 'cover',
                        ];
                    } else {
                        // Regular shape (not an image frame with content)
                        $shapeElements[] = [
                            'x' => $frameX,
                            'y' => $frameY,
                            'width' => $frameWidth ?: 100,
                            'height' => $frameHeight ?: 100,
                            'fill' => $layer['fill'] ?? '#cccccc',
                            'stroke' => $layer['stroke'] ?? null,
                            'borderRadius' => $layer['borderRadius'] ?? 0,
                            'variant' => $layer['variant'] ?? 'rectangle',
                        ];
                    }
                }
            }

            // If no elements, return null to use dummy SVG
            if (empty($textElements) && empty($imageElements) && empty($shapeElements)) {
                return null;
            }

            // Generate SVG content
            $svgContent = $this->buildSvgContent(
                $textElements,
                $imageElements,
                $shapeElements,
                $canvasWidth,
                $canvasHeight,
                $pageBackground
            );

            // Convert to base64 data URL
            return 'data:image/svg+xml;base64,' . base64_encode($svgContent);

        } catch (\Exception $e) {
            Log::warning('Failed to generate SVG from template', ['template_id' => $templateId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build SVG content from text, image, and shape elements
     *
     * @param array $textElements
     * @param array $imageElements
     * @param array $shapeElements
     * @param int $canvasWidth
     * @param int $canvasHeight
     * @return string SVG markup
     */
    protected function buildSvgContent(array $textElements, array $imageElements, array $shapeElements, int $canvasWidth = 414, int $canvasHeight = 896, ?string $backgroundFill = null): string
    {
        $defs = '';
        $gradientCounter = 0;

        $resolveFill = function (?string $fill) use (&$defs, &$gradientCounter): ?string {
            if (!$fill) {
                return null;
            }

            if (stripos($fill, 'linear-gradient') === 0) {
                $gradientCounter++;
                $gradientId = 'inkwise-grad-' . $gradientCounter;
                $defs .= $this->buildLinearGradientDef($gradientId, $fill);
                return 'url(#' . $gradientId . ')';
            }

            if (stripos($fill, 'radial-gradient') === 0) {
                $gradientCounter++;
                $gradientId = 'inkwise-grad-' . $gradientCounter;
                $defs .= $this->buildRadialGradientDef($gradientId, $fill);
                return 'url(#' . $gradientId . ')';
            }

            return $fill;
        };

        $resolvedBackgroundFill = $resolveFill($backgroundFill) ?? '#ffffff';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $canvasWidth . '" height="' . $canvasHeight . '" viewBox="0 0 ' . $canvasWidth . ' ' . $canvasHeight . '" preserveAspectRatio="xMidYMid meet">';

        // Solid/gradient background
        $svg .= '<rect width="' . $canvasWidth . '" height="' . $canvasHeight . '" fill="' . htmlspecialchars($resolvedBackgroundFill) . '"/>';

        // Add shape elements (drawn beneath images/text to preserve stacking order)
        foreach ($shapeElements as $shapeElement) {
            $x = $shapeElement['x'];
            $y = $shapeElement['y'];
            $width = $shapeElement['width'];
            $height = $shapeElement['height'];
            $fill = $resolveFill($shapeElement['fill'] ?? null) ?? 'transparent';
            $stroke = $shapeElement['stroke'] ?? null;
            $borderRadius = $shapeElement['borderRadius'] ?? 0;
            $variant = $shapeElement['variant'] ?? 'rectangle';

            if ($variant === 'rectangle' || !$variant) {
                $rx = $borderRadius > 0 ? ' rx="' . $borderRadius . '" ry="' . $borderRadius . '"' : '';
                $strokeAttr = $stroke ? ' stroke="' . htmlspecialchars($stroke) . '" stroke-width="1"' : '';
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" fill="' . htmlspecialchars($fill) . '"' . $strokeAttr . $rx . '/>';
            }
        }

        // Add image elements
        foreach ($imageElements as $imageElement) {
            $x = $imageElement['x'];
            $y = $imageElement['y'];
            $width = $imageElement['width'];
            $height = $imageElement['height'];
            $src = $imageElement['src'];
            $borderRadius = $imageElement['borderRadius'] ?? 0;

            if ($src) {
                $clipPathId = null;
                if ($borderRadius > 0) {
                    $clipPathId = 'inkwise-img-clip-' . (++$gradientCounter);
                    $defs .= '<clipPath id="' . $clipPathId . '"><rect x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" rx="' . $borderRadius . '" ry="' . $borderRadius . '"/></clipPath>';
                }

                $clipAttr = $clipPathId ? ' clip-path="url(#' . $clipPathId . ')"' : '';
                $svg .= '<image x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . htmlspecialchars($src) . '" preserveAspectRatio="xMidYMid slice"' . $clipAttr . '/>';
            } else {
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" fill="#cccccc" stroke="#999999" stroke-width="2"/>';
                $svg .= '<text x="' . ($x + $width / 2) . '" y="' . ($y + $height / 2) . '" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="14" fill="#666666">Image</text>';
            }
        }

        // Add text elements
        foreach ($textElements as $textElement) {
            $x = $textElement['x'];
            $y = $textElement['y'];
            $text = htmlspecialchars($textElement['text']);
            $fontSize = $textElement['font_size'];
            $color = $textElement['color'];
            $fontFamily = $textElement['font_family'];
            $fontWeight = $textElement['font_weight'] ?? 'normal';
            $textAlign = $textElement['text_align'];

            $textAnchor = match ($textAlign) {
                'left' => 'start',
                'right' => 'end',
                default => 'middle',
            };

            $fontWeightAttr = $fontWeight && $fontWeight !== 'normal'
                ? ' font-weight="' . $fontWeight . '"'
                : '';

            $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="' . $fontFamily . '"' . $fontWeightAttr . ' font-size="' . $fontSize . '" fill="' . $color . '" text-anchor="' . $textAnchor . '" dominant-baseline="middle">' . $text . '</text>';
        }

        // Inject defs just inside the root if any were collected
        if (!empty($defs)) {
            $svg = str_replace('<svg ', '<svg ', $svg); // no-op placeholder to keep structure
            $svg = substr_replace($svg, '<defs>' . $defs . '</defs>', strpos($svg, '>') + 1, 0);
        }

        $svg .= '</svg>';

        return $svg;
    }

    protected function buildLinearGradientDef(string $id, string $cssGradient): string
    {
        // Example input: linear-gradient(90deg, #D5DCE3, #FFFFFF)
        if (!preg_match('/linear-gradient\(([^,]+),\s*([^,]+),\s*([^\)]+)\)/i', $cssGradient, $matches)) {
            return '';
        }

        $angleRaw = trim($matches[1]);
        $colorStart = trim($matches[2]);
        $colorEnd = trim($matches[3]);

        $angleValue = (float) str_replace('deg', '', $angleRaw);
        // CSS 0deg points up; SVG 0deg points right. Shift by -90deg to align.
        $radians = deg2rad($angleValue - 90);
        $dx = cos($radians);
        $dy = sin($radians);

        $x1 = 50 - ($dx * 50);
        $y1 = 50 - ($dy * 50);
        $x2 = 50 + ($dx * 50);
        $y2 = 50 + ($dy * 50);

        return '<linearGradient id="' . $id . '" x1="' . $x1 . '%" y1="' . $y1 . '%" x2="' . $x2 . '%" y2="' . $y2 . '%">'
            . '<stop offset="0%" stop-color="' . $colorStart . '"/>'
            . '<stop offset="100%" stop-color="' . $colorEnd . '"/>'
            . '</linearGradient>';
    }

    protected function buildRadialGradientDef(string $id, string $cssGradient): string
    {
        // Example inputs: 
        // radial-gradient(circle at center, #E5D4FF, #FFFFFF)
        // radial-gradient(circle, #E5D4FF, #FFFFFF)
        // radial-gradient(#E5D4FF, #FFFFFF)
        
        // Match radial-gradient with optional position
        if (!preg_match('/radial-gradient\([^,]*,\s*([^,]+),\s*([^\)]+)\)/i', $cssGradient, $matches)) {
            return '';
        }

        $colorStart = trim($matches[1]);
        $colorEnd = trim($matches[2]);

        // Default center position (50%, 50%)
        $cx = 50;
        $cy = 50;
        $r = 50; // radius as percentage

        return '<radialGradient id="' . $id . '" cx="' . $cx . '%" cy="' . $cy . '%" r="' . $r . '%">'
            . '<stop offset="0%" stop-color="' . $colorStart . '"/>'
            . '<stop offset="100%" stop-color="' . $colorEnd . '"/>'
            . '</radialGradient>';
    }

    protected function guessImageMime(string $raw): ?string
    {
        $prefix = substr($raw, 0, 10);
        if (str_starts_with($raw, '<svg')) {
            return 'image/svg+xml';
        }
        if (str_starts_with($raw, '\\x89PNG') || str_starts_with($raw, 'iVBORw0KGgo')) {
            return 'image/png';
        }
        if (str_starts_with($raw, '/9j/') || str_starts_with($raw, '\\xff\\xd8')) {
            return 'image/jpeg';
        }
        if (str_starts_with($raw, 'R0lGOD')) {
            return 'image/gif';
        }

        // Heuristic: base64 strings often use A-Z a-z 0-9 / + characters; default to jpeg
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $raw)) {
            return 'image/jpeg';
        }

        return null;
    }

    /**
     * Save dummy SVG as fallback
     *
     * @param \App\Models\Template $template
     */
    protected function saveDummySvg($template): void
    {
        try {
            $dummySvg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="white"/></svg>');
            $template->svg_path = $this->persistDataUrl(
                $dummySvg,
                'templates/svg',
                'svg',
                $template->svg_path,
                'dummy_svg'
            );
            Log::info('Dummy SVG saved', ['path' => $template->svg_path]);
        } catch (ValidationException $e) {
            Log::warning('Dummy SVG save failed', ['error' => $e->getMessage()]);
        }
    }
}
