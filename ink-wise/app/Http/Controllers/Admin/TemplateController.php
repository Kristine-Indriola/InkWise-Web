<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    // Show all templates
    public function index()
    {
        $templates = Template::all();
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
        // 1. Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'size' => 'nullable|string|max:50',
        ]);

        // 2. Save template
        $template = Template::create([
            'name' => $request->name,
            'design' => json_encode([
                'category' => $request->category,
                'description' => $request->description,
                'primary_color' => $request->primary_color,
                'secondary_color' => $request->secondary_color,
                'size' => $request->size,
                
            ]),
        ]);
       // Save uploaded preview
       if ($request->hasFile('preview')) {
        $path = $request->file('preview')->store('templates/previews', 'public');
        $template->preview = $path;
        $template->save();
    }

        // 3. Redirect to editor page with new template ID
        return redirect()->route('admin.templates.editor', $template->id)
                         ->with('success', 'Template created! Now start editing.');
                         
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
            'preview' => asset('storage/' . $filePath)
        ]);
    }

    return response()->json(['success' => false, 'message' => 'No image data provided'], 400);
}


public function uploadPreview(Request $request, $id)
{
    $request->validate([
        'preview' => 'required|image|max:2048'
    ]);
    $template = Template::findOrFail($id);
    $path = $request->file('preview')->store('templates/previews', 'public');
    $template->preview = $path;
    $template->save();

    return redirect()->route('admin.templates.index')->with('success', 'Preview image uploaded!');
}
}
