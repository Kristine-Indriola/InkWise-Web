<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function weddingInvitations()
    {
        $products = Product::where('event_type', 'Wedding')
                          ->where('product_type', 'Invitation')
                          ->whereHas('uploads') // Only show products that have been uploaded/published
                          ->with('materials') // if needed
                          ->get();

        // Load ratings for each product
        foreach ($products as $product) {
            $product->ratings = \App\Models\OrderRating::whereHas('order.items', function($query) use ($product) {
                $query->where('product_id', $product->id);
            })->with('customer')->get();
        }

        // Prepare ratings data for JavaScript
        $ratingsData = [];
        foreach ($products as $product) {
            $ratings = $product->ratings ?? collect();
            $ratingsData[$product->id] = [
                'name' => $product->name,
                'ratings' => $ratings->map(function($rating) {
                    return [
                        'rating' => $rating->rating,
                        'review' => $rating->review,
                        'submitted_at' => $rating->submitted_at?->format('M d, Y'),
                        'customer_name' => $rating->customer->name ?? 'Customer',
                        'photos' => $rating->photos ?? []
                    ];
                })->toArray(),
                'average_rating' => $ratings->avg('rating'),
                'rating_count' => $ratings->count()
            ];
        }

        return view('customer.Invitations.weddinginvite', compact('products', 'ratingsData'));
    }

    public function weddingGiveaways()
    {
        $products = Product::query()
            ->with(['template', 'uploads', 'images', 'materials', 'bulkOrders'])
            ->where('product_type', 'Giveaway')
            ->whereHas('uploads') // Only show products that have been uploaded/published
            ->where(function ($query) {
                $query->where('event_type', 'Wedding')
                      ->orWhereNull('event_type')
                      ->orWhere('event_type', '');
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('customer.Giveaways.weddinggive', compact('products'));
    }
}