<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;
use Illuminate\Support\Facades\Storage;

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
    $user = Auth::user();
    $customer = $user->customer;

    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'birthdate' => 'nullable|date',
        'gender' => 'nullable|string|in:male,female,other',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'remove_photo' => 'nullable|boolean',
    ]);

    // Update main user table
    $user->update([
        'email' => $validated['email'],
    ]);

    // Handle remove photo
    if ($request->boolean('remove_photo')) {
        if ($customer->photo) {
            Storage::disk('public')->delete($customer->photo);
        }
        $customer->photo = null;
    }
    // Handle upload new photo
    elseif ($request->hasFile('photo')) {
        if ($customer->photo) {
            Storage::disk('public')->delete($customer->photo);
        }
        $customer->photo = $request->file('photo')->store('photos', 'public');
    }

    // Update other fields
    $customer->first_name = $validated['first_name'];
    $customer->middle_name = $validated['middle_name'] ?? null;
    $customer->last_name = $validated['last_name'];
    $customer->contact_number = $validated['phone'] ?? null;
    $customer->date_of_birth = $validated['birthdate'] ?? null;
    $customer->gender = $validated['gender'] ?? null;
    $customer->save();

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
