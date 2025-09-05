<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{

    public function index()
{
    // Get all customers who have messages
    $customerIds = Message::select('sender_id')
        ->where('sender_type', 'customer')
        ->distinct()
        ->pluck('sender_id');

    $customers = \App\Models\Customer::whereIn('id', $customerIds)->get();

    return view('admin.messages.index', compact('customers'));
}

    // Show chat with a customer
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

    // Send message to customer
    public function sendToCustomer(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        Message::create([
            'sender_id'   => Auth::id(),
            'sender_type' => 'admin',   // make it explicit
            'receiver_id' => $customer->id,
            'receiver_type' => 'customer',
            'message' => $request->message,
        ]);

        return redirect()->route('admin.messages.chat', $customerId);
    }
}
