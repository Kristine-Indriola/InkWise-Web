<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;

class CustomerProfileController extends Controller
{
    // --- Profile Methods ---
    public function edit()
    {
        $user = Auth::user();
        $customer = $user->customer;
        $address = $user->address;

        return view('customerprofile.profile', compact('customer', 'address'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'phone'       => 'nullable|string|max:255',
            'birthdate'   => 'nullable|date',
            'gender'      => 'nullable|in:male,female,other',
            'photo'       => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        $customer = $user->customer;

        $user->update(['email' => $request->email]);
        $customer->update([
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->phone,
            'date_of_birth'  => $request->birthdate,
            'gender'         => $request->gender,
            'photo'          => $request->hasFile('photo')
                                    ? $request->file('photo')->store('avatars', 'public')
                                    : $customer->photo,
        ]);

        $user->refresh(); // makes sure Auth::user()->customer is updated
    return back()->with('status', 'Profile updated successfully!');
    }

    // --- Address Methods ---
    public function addresses()
    {
        $addresses = Address::where('user_id', Auth::id())->get();
        return view('customerprofile.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request)
    {
        $request->validate([
            'full_name'   => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'region'      => 'required|string|max:255',
            'province'    => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'barangay'    => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'street'      => 'required|string|max:255',
            'label'       => 'required|string|max:50',
        ]);

        Address::create([
            'user_id'     => Auth::id(),
            'full_name'   => $request->full_name,
            'phone'       => $request->phone,
            'region'      => $request->region,
            'province'    => $request->province,
            'city'        => $request->city,
            'barangay'    => $request->barangay,
            'postal_code' => $request->postal_code,
            'street'      => $request->street,
            'label'       => $request->label,
            'country'     => 'Philippines',
        ]);

        return redirect()->route('customerprofile.addresses')->with('success', 'Address added successfully!');
    }

    public function updateAddress(Request $request, Address $address)
    {
        $request->validate([
            'full_name'   => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'region'      => 'required|string|max:255',
            'province'    => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'barangay'    => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'street'      => 'required|string|max:255',
            'label'       => 'required|string|max:50',
        ]);

        $address->update($request->all());

        return redirect()->route('customerprofile.addresses')->with('success', 'Address updated!');
    }

    public function destroyAddress(Address $address)
    {
        $address->delete();
        return redirect()->route('customerprofile.addresses')->with('success', 'Address deleted!');
    }
}
