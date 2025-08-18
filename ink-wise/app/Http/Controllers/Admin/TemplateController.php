<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Template;

class TemplateController extends Controller
{
    public function editor($id = null)
    {
        $template = $id ? Template::find($id) : null;
        return view('admin.templates.editor', compact('template'));
    }

    public function store(Request $request)
    {
        $template = Template::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name, 'design' => $request->design]
        );

        return response()->json(['success' => true, 'id' => $template->id]);
    }

    public function index()
    {
        $templates = Template::all();
        return view('admin.templates.index', compact('templates'));
    }
}
