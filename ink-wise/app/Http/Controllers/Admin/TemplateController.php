<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

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
    $path = $request->file('preview')->store('templates/previews', 'public');
    $template->preview = $path;
    $template->save();
    

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
}
