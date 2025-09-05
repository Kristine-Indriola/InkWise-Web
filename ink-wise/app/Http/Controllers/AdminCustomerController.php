<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // assuming customers are in users table
use Illuminate\Support\Facades\DB;

class AdminCustomerController extends Controller
{
    public function index()
    {
        // If you have a 'role' column or separate customer table, adjust accordingly
        $customers = User::where('role', 'customer')->get(); 
        return view('admin.customers.index', compact('customers'));
    }
}
