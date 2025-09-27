<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OwnerProductsController extends Controller
{
    /**
     * Show an empty products listing for the owner.
     * This always passes an empty collection so the view renders a safe "No products yet" state.
     */
    public function index(Request $request)
    {
        $products = collect(); // intentionally empty
        return view('owner.products.index', compact('products'));
    }
}