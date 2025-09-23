<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerProfileController extends Controller
{

    // --- Profile Methods ---
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customer;
        return view('customer.profile.index', compact('customer'));
    }


    public function edit()
    {
        $user = Auth::user();
        $customer = $user->customer;
        $address = $user->address;


        return view('customer.profile.index', compact('customer', 'address'));

        return view('customer.profile.update', compact('customer', 'address'));

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


        // Handle photo removal
        if ($request->boolean('remove_photo')) {
            if ($customer->photo && Storage::disk('public')->exists($customer->photo)) {
                Storage::disk('public')->delete($customer->photo);
            }
            $customer->photo = null;
        }

        // Handle new photo upload
        if ($request->hasFile('photo')) {
            $customer->photo = $request->file('photo')->store('avatars', 'public');
        }

        // Update other fields
        $customer->update([
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->phone,
            'date_of_birth'  => $request->birthdate,
            'gender'         => $request->gender,
            'photo'          => $customer->photo,
        ]);

        return back()->with('status', 'Profile updated successfully!');
    }

    // --- Address Methods ---
    public function addresses()
    {
        $addresses = Address::where('user_id', Auth::id())->get();
        return view('customer.profile.addresses', compact('addresses'));
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

        return redirect()->route('customer.profile.addresses')->with('success', 'Address added successfully!');
    }

    public function destroyAddress(Address $address)
    {
        $address->delete();
        return redirect()->route('customer.profile.addresses')->with('success', 'Address deleted!');
    }
}
