<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
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

        return view('customer.profile.update', compact('customer', 'address'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'contact_number'       => 'nullable|string|max:255',
            'date_of_birth'   => 'nullable|date',
            'gender'      => 'nullable|in:male,female,other',
            'photo'       => 'nullable|image|max:2048',
            'remove_photo'=> 'nullable|boolean',
        ]);

        // Update user email
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
        // Accept full_name and phone as optional inputs in the form; Address model doesn't have these columns yet.
        $data = $this->validateAddress($request);

        Address::create(array_merge($data, [
            'user_id' => Auth::id(),
            'country' => 'Philippines',
        ]));

        return redirect()->route('customer.profile.addresses')->with('success', 'Address added successfully!');
    }

    public function updateAddress(Request $request, Address $address)
{
    $data = $this->validateAddress($request);

    $address->update(array_merge($data, [
        'country' => 'Philippines', // still force Philippines
    ]));

    return redirect()->route('customer.profile.addresses')->with('success', 'Address updated successfully!');
}

    public function destroyAddress(Address $address)
    {
        $address->delete();
        return redirect()->route('customer.profile.addresses')->with('success', 'Address deleted!');
    }

    // --- Shared Validation ---
    protected function validateAddress(Request $request)
{
    return $request->validate([
        'full_name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:255',
        'region'      => 'required|string|max:255',
        'province'    => 'required|string|max:255',
        'city'        => 'required|string|max:255',
        'barangay'    => 'required|string|max:255',
        'postal_code' => 'required|string|max:20',
        'street'      => 'required|string|max:255',
        'label'       => 'required|string|max:50',
    ]);
}

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('customer.login.form');
        }

        $customer = $user->customer;
        if (!$customer) {
            abort(403);
        }

        $ownsOrder = ((int) $order->customer_id === (int) $customer->customer_id)
            || ((int) $order->user_id === (int) $user->id);

        if (!$ownsOrder) {
            abort(403);
        }

        $cancellableStatuses = ['pending', 'awaiting_payment'];

        if (!in_array($order->status, $cancellableStatuses, true)) {
            return redirect()->back()->with('error', 'Order can no longer be cancelled once it is in progress.');
        }

        $metadata = $order->metadata ?? [];
        $metadata['cancelled_by'] = 'customer';
        $metadata['cancelled_at'] = now()->toIso8601String();

        if ($request->filled('cancel_reason')) {
            $metadata['cancel_reason'] = trim((string) $request->input('cancel_reason'));
        }

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        return redirect()->back()->with('status', 'Order cancelled successfully.');
    }
}
