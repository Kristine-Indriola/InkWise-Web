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
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
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
            'full_name'   => $request->input('full_name'),
            'phone'       => $request->input('phone'),
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
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'region' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'street' => 'required|string|max:255',
            'label' => 'required|string|max:50',
        ]);
        // Only update allowed address fields (avoid mass assignment of unrelated input)
        $address->update([
            'full_name' => $request->input('full_name'),
            'phone' => $request->input('phone'),
            'street' => $request->input('street'),
            'barangay' => $request->input('barangay'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'postal_code' => $request->input('postal_code'),
            'country' => $address->country ?? 'Philippines',
            'label' => $request->input('label') ?? ($address->label ?? 'Home'),
        ]);
        return redirect()->route('customer.profile.addresses')->with('success', 'Address updated!');
    }

    public function destroy(Address $address)
    {
        $address->delete();
        return redirect()->route('customer.profile.addresses')->with('success', 'Address deleted!');
    }
}
