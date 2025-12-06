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

        // Set product_images attribute for view compatibility
        $products->each(function (Product $product) {
            $product->setAttribute('product_images', $product->images);
            $product->setAttribute('product_bulk_orders', $product->bulkOrders);
            $product->setAttribute('product_uploads', $product->uploads);
            $product->setAttribute('envelope', $product->envelope);
        });

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

    public function baptismInvitations()
    {
        $products = Product::where('event_type', 'Baptism')
                          ->where('product_type', 'Invitation')
                          ->whereHas('uploads') // Only show products that have been uploaded/published
                          ->with('materials') // if needed
                          ->get();

        // Set product_images attribute for view compatibility
        $products->each(function (Product $product) {
            $product->setAttribute('product_images', $product->images);
            $product->setAttribute('product_bulk_orders', $product->bulkOrders);
            $product->setAttribute('product_uploads', $product->uploads);
            $product->setAttribute('envelope', $product->envelope);
        });

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

        return view('customer.Invitations.baptisminvite', compact('products', 'ratingsData'));
    }

    public function birthdayInvitations()
    {
        $products = Product::where('event_type', 'Birthday')
                          ->where('product_type', 'Invitation')
                          ->whereHas('uploads') // Only show products that have been uploaded/published
                          ->with('materials') // if needed
                          ->get();

        // Set product_images attribute for view compatibility
        $products->each(function (Product $product) {
            $product->setAttribute('product_images', $product->images);
            $product->setAttribute('product_bulk_orders', $product->bulkOrders);
            $product->setAttribute('product_uploads', $product->uploads);
            $product->setAttribute('envelope', $product->envelope);
        });

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

        return view('customer.Invitations.birthdayinvite', compact('products', 'ratingsData'));
    }

    public function corporateInvitations()
    {
        $products = Product::where('event_type', 'Corporate')
                          ->where('product_type', 'Invitation')
                          ->whereHas('uploads') // Only show products that have been uploaded/published
                          ->with('materials') // if needed
                          ->get();

        // Set product_images attribute for view compatibility
        $products->each(function (Product $product) {
            $product->setAttribute('product_images', $product->images);
            $product->setAttribute('product_bulk_orders', $product->bulkOrders);
            $product->setAttribute('product_uploads', $product->uploads);
            $product->setAttribute('envelope', $product->envelope);
        });

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

        return view('customer.Invitations.corporateinvite', compact('products', 'ratingsData'));
    }
}