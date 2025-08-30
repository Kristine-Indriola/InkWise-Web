<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        // later you can fetch templates from DB
        return view('admin.templates.index');
    }

    public function create()
    {
        return view('admin.templates.create');
    }

    public function store(Request $request)
    {
        // save template logic later
    }
}
