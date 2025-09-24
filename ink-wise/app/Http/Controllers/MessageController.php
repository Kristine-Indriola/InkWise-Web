<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // List all customers who have messages
    public function index()
    {
        $customerIds = Message::select('sender_id')
            ->where('sender_type', 'customer')
            ->distinct()
            ->pluck('sender_id');

        $customers = Customer::whereIn('id', $customerIds)->get();

        return view('admin.messages.index', compact('customers'));
    }

    // Show chat with a specific customer
    public function chatWithCustomer($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $messages = Message::where(function ($q) use ($customer) {
                $q->where('sender_id', $customer->id)
                  ->where('sender_type', 'customer');
            })
            ->orWhere(function ($q) use ($customer) {
                $q->where('receiver_id', $customer->id)
                  ->where('receiver_type', 'customer');
            })
            ->orderBy('created_at')
            ->get();

        return view('admin.messages.chat', compact('messages', 'customer'));
    }

    // Admin sends message to customer
    public function sendToCustomer(Request $request, $customerId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $customer = Customer::findOrFail($customerId);

        Message::create([
            'sender_id'    => Auth::id(),
            'sender_type'  => 'user',       // use "user" for admin/staff
            'receiver_id'  => $customer->id,
            'receiver_type'=> 'customer',
            'message'      => $request->message,
        ]);

        return redirect()->route('admin.messages.chat', $customerId)
                         ->with('success', 'Message sent successfully!');
    }

    // Store message from Contact Form (public side)
    public function storeFromContact(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:1000',
        ]);

        Message::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'message'      => $validated['message'],
            'sender_id'    => null,
            'sender_type'  => 'guest',
            'receiver_id'  => 1, // Example: first admin/user account
            'receiver_type'=> 'user',
        ]);

        return back()->with('success', 'Your message has been sent successfully!');

    }
}
