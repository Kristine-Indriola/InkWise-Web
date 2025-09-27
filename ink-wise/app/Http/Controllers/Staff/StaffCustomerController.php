<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StaffCustomerController extends Controller
{
    public function index()
    {
        // get all customers (adjust if you have a different role system)
        $customers = User::where('role', 'customer')->get(); 

        return view('staff.customer_profile', compact('customers'));
    }
}
