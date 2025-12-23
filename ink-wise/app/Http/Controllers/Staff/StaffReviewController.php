<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\OrderRating;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffReviewController extends Controller
{
    public function index()
    {
        $reviews = OrderRating::with(['customer', 'order', 'staffReplyBy.staff'])
            ->latest('submitted_at')
            ->paginate(20);

        return view('staff.reviews.index', compact('reviews'));
    }

    public function reply(Request $request, OrderRating $review)
    {
        $request->validate([
            'staff_reply' => 'required|string|max:1000',
        ]);

        $review->update([
            'staff_reply' => $request->staff_reply,
            'staff_reply_at' => now(),
            'staff_reply_by' => Auth::id(),
        ]);

        // Send notification message to customer
        if ($review->customer) {
            Message::create([
                'sender_type' => 'App\Models\User',
                'sender_id' => Auth::id(),
                'receiver_type' => 'App\Models\Customer',
                'receiver_id' => $review->customer_id,
                'name' => Auth::user()->name ?? 'Staff Member',
                'email' => Auth::user()->email ?? 'staff@inkwise.com',
                'message' => "Thank you for your feedback! Our staff has responded to your review for Order #{$review->order->order_number}:\n\n\"{$request->staff_reply}\"",
            ]);
        }

        return redirect()->back()->with('success', 'Reply sent successfully and customer notified.');
    }
}