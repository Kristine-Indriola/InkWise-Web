<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('query');

        if (!$query) {
            return view('customer.search', [
                'query' => '',
                'templates' => collect(),
                'total' => 0
            ]);
        }

        // Search templates by name, event_type, product_type, or theme_style
        $templates = Template::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('event_type', 'LIKE', "%{$query}%")
                  ->orWhere('product_type', 'LIKE', "%{$query}%")
                  ->orWhere('theme_style', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->paginate(12);

        return view('customer.search', [
            'query' => $query,
            'templates' => $templates,
            'total' => $templates->total()
        ]);
    }
}
