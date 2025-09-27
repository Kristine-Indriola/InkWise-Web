<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminCustomerController extends Controller
{
    public function index()
    {
        // Load customers + their related customer profile
        $customers = User::where('role', 'customer')
            ->with('customer') // eager load customer table
            ->get();

        return view('admin.customers.index', compact('customers'));
    }
}
