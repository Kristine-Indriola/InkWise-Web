<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRating;

class OwnerRatingsController extends Controller
{
    public function index()
    {
        $ratings = Order::query()
            ->with(['rating.staffReplyBy.staff', 'customer'])
            ->whereHas('rating')
            ->latest('updated_at')
            ->paginate(12)
            ->through(function (Order $order) {
                $rating = $order->rating;

                return [
                    'id' => $order->getKey(),
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer?->name ?? 'Guest Customer',
                    'rating' => $rating?->rating,
                    'review' => $rating?->review,
                    'submitted_at' => $rating?->submitted_at,
                    'staff_reply' => $rating?->staff_reply,
                    'staff_reply_at' => $rating?->staff_reply_at,
                    'staff_reply_by' => $rating?->staffReplyBy,
                ];
            });

        $averageRating = OrderRating::query()->avg('rating');
        $averageRating = $averageRating ? round($averageRating, 2) : null;

        $ratingCounts = OrderRating::query()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        $totalRatings = array_sum($ratingCounts);

        return view('owner.ratings.index', [
            'ratings' => $ratings,
            'averageRating' => $averageRating,
            'ratingCounts' => $ratingCounts,
            'totalRatings' => $totalRatings,
        ]);
    }
}
