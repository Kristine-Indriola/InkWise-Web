<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function categories() {
        return view('categories'); 
    }

    public function templates($category) {
        return view('templates', ['category' => $category]);
    }

    public function preview($id) {
        return view('template-preview', ['id' => $id]);
    }
}
