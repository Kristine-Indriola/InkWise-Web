<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
   // Show chat with a customer
    public function chatWithcustomer($customerId)
    {
        $customer = customer::findOrFail($customerId);

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

        return view('chat', compact('messages', 'customer'));
    }

    // Send message
    public function sendTocustomer(Request $request, $customerId)
    {
        $customer = customer::findOrFail($customerId);

        Message::create([
            'sender_id'   => Auth::id(),
            'sender_type' => 'user',
            'receiver_id' => $customer->id,
            'receiver_type' => 'customer',
            'message' => $request->message,
        ]);

        return redirect()->route('messages.chat', $userId);
    }
}

