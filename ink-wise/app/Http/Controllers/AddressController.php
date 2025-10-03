<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function profile()
    {
        $addresses = \App\Models\Address::where('user_id', auth()->id())->get();
        return view('customer.profile.addresses', compact('addresses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'region'      => 'required|string|max:255',
            'province'    => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'barangay'    => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'street'      => 'required|string|max:255',
            'label'    => 'required|string|max:50', 
        ]);

        Address::create([
            'user_id'     => Auth::id(),
            'street'      => $request->street,
            'barangay'    => $request->barangay,
            'city'        => $request->city,
            'province'    => $request->province,
            'postal_code' => $request->postal_code,
            'country'     => 'Philippines',
        ]);

        return redirect()->route('customer.profile.addresses')->with('success', 'Address added successfully!');
    }

    public function update(Request $request, Address $address)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'street' => 'required|string|max:255',
            'label' => 'required|string|max:50',
        ]);
        $address->update($request->all());
        return redirect()->route('customer.profile.addresses')->with('success', 'Address updated!');
    }

    public function destroy(Address $address)
    {
        $address->delete();
        return redirect()->route('customer.profile.addresses')->with('success', 'Address deleted!');
    }
}
