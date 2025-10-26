<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerProfileController extends Controller
{
    private function checkCustomerRole()
    {
        if (!Auth::check() || Auth::user()->role !== 'customer') {
            return redirect('/unauthorized');
        }
        return null;
    }

    // --- Profile Methods ---
    public function index()
    {
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
        $user = Auth::user();
        $customer = $user->customer;
        return view('customer.profile.index', compact('customer'));
    }

    public function edit()
    {
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
        $user = Auth::user();
        $customer = $user->customer;
        $address = $user->address;

        return view('customer.profile.update', compact('customer', 'address'));
    }

    public function update(Request $request)
    {
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
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
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
        $addresses = Address::where('user_id', Auth::id())->get();
        return view('customer.profile.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request)
    {
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
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
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
        $data = $this->validateAddress($request);

        $address->update(array_merge($data, [
            'country' => 'Philippines', // still force Philippines
        ]));

        return redirect()->route('customer.profile.addresses')->with('success', 'Address updated successfully!');
    }

    public function destroyAddress(Address $address)
    {
        if ($redirect = $this->checkCustomerRole()) return $redirect;
        
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

    public function confirmReceived(Request $request, Order $order): RedirectResponse
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

        if ($order->status === 'completed') {
            return redirect()->back()->with('status', 'Thanks! This order is already marked as completed.');
        }

        if (!in_array($order->status, ['to_receive', 'confirmed'], true)) {
            return redirect()->back()->with('error', 'This order cannot be marked as received yet.');
        }

        $metadata = $order->metadata ?? [];
        $metadata['customer_confirmed_received_at'] = now()->toIso8601String();
        $metadata['customer_confirmed_received_by'] = $user->getAuthIdentifier();

        $order->update([
            'status' => 'completed',
            'metadata' => $metadata,
        ]);

        return redirect()->back()->with('status', 'Thank you! The order is now marked as completed.');
    }

    public function rate()
    {
        $user = Auth::user();
        $customer = $user?->customer;

        if (!$customer) {
            return view('customer.profile.purchase.rate', ['orders' => collect()]);
        }

        $orders = Order::query()
            ->with('rating')
            ->where('customer_id', $customer->getKey())
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->get();

        return view('customer.profile.purchase.rate', compact('orders'));
    }

    public function storeRating(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:600',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
        ]);

        $user = Auth::user();
        $customer = $user?->customer;

        if (!$customer) {
            return redirect()->back()->withErrors(['order' => 'Unable to locate your customer record.']);
        }

        $order = Order::query()
            ->with('rating')
            ->where('id', $request->order_id)
            ->where('customer_id', $customer->getKey())
            ->where('status', 'completed')
            ->firstOrFail();

        if ($order->rating) {
            return redirect()->back()->withErrors(['order' => 'This order has already been rated.']);
        }

        $photos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('ratings', 'public');
            }
        }

        DB::transaction(function () use ($order, $customer, $user, $request, $photos) {
            $rating = OrderRating::create([
                'order_id' => $order->id,
                'customer_id' => $customer->getKey(),
                'submitted_by' => $user->getAuthIdentifier(),
                'rating' => (int) $request->rating,
                'review' => $request->review,
                'photos' => $photos ?: null,
                'metadata' => null,
                'submitted_at' => now(),
            ]);

            $metadata = $order->metadata ?? [];
            $metadata['rating'] = $rating->rating;
            $metadata['review'] = $rating->review;
            $metadata['rating_photos'] = $photos;
            $metadata['rating_submitted_at'] = optional($rating->submitted_at)->toIso8601String();

            $order->update(['metadata' => $metadata]);
        });

        return redirect()->back()->with('status', 'Thank you for your rating!');
    }
}
