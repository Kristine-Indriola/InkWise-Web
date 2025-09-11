<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class customerProfileController extends Controller
{
    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
        ]);

        $customer->update($request->only('name', 'email'));

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}


