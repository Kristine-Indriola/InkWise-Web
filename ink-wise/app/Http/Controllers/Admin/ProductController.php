<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
        // $products = Product::paginate(10); // Commented out for now
        $products = []; // Empty array for now
        return view('admin.products.index', compact('products'));
    }

    public function invitation()
    {
        // Sample order & material data (based on the info you gave)
        $sampleOrder = [
            'order_name' => 'Elegant Pearl Wedding Invitation',
            'qty' => 100
        ];

        $materials = [
            [
                'id' => 'cardstock',
                'name' => 'Pearl Metallic Cardstock – Champagne',
                'size' => 'A7',
                'weight' => '270 GSM',
                'unit_price' => 12.00,
                'qty_needed' => 100,
            ],
            [
                'id' => 'envelope',
                'name' => 'Metallic Envelope – Gold',
                'size' => 'A7',
                'unit_price' => 8.00,
                'qty_needed' => 100,
            ],
        ];

        $printing = [
            'ink_per_invite_ml' => 0.23,
            'cost_per_invite_ink' => 0.26, // given
        ];

        $foil = [
            'item' => 'Foil Roll – Gold',
            'usage_per_invite_cm' => 0.5,
            'roll_length_cm' => 3000,
            'roll_price' => 150.00
        ];

        $lamination = [
            'item' => 'Matte Lamination Film',
            'roll_price' => 500.00,
            'coverage_sheets' => 500,
            'usage_per_sheet' => 1
        ];

        // $products = Product::paginate(10); // Commented out for now
        $products = []; // Empty array for now

        // RETURN the admin.products.index view (was view('products.index'))
        return view('admin.products.index', compact('sampleOrder','materials','printing','foil','lamination', 'products'));
    }

    // Add: Method to show the create invitation form
    public function createInvitation()
    {
        return view('admin.products.create-invitation');
    }

    // Add: Method to handle form submission (placeholder, no DB yet)
    public function store(Request $request)
    {
        // Placeholder: Add validation and logic here later
        // For now, redirect back with a success message
        return redirect()->route('admin.products.index')->with('success', 'Invitation created successfully.');
    }
}
