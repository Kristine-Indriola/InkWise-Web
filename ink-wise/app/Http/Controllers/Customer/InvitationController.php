<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;

class InvitationController extends Controller
{
    public function weddingInvitations()
    {
        [$products, $ratingsData] = $this->invitationDataForEvent('Wedding');

        return view('customer.Invitations.weddinginvite', compact('products', 'ratingsData'));
    }

    public function weddingGiveaways()
    {
        $products = $this->giveawayProductsForEvent('Wedding');

        return view('customer.Giveaways.weddinggive', compact('products'));
    }

    public function birthdayGiveaways()
    {
        $products = $this->giveawayProductsForEvent('Birthday');

        return view('customer.Giveaways.birthdaygive', compact('products'));
    }

    public function corporateGiveaways()
    {
        $products = $this->giveawayProductsForEvent('Corporate');

        return view('customer.Giveaways.corporategive', compact('products'));
    }

    public function baptismGiveaways()
    {
        $products = $this->giveawayProductsForEvent('Baptism');

        return view('customer.Giveaways.baptismgive', compact('products'));
    }

    public function corporateInvitations()
    {
        [$products, $ratingsData] = $this->invitationDataForEvent('Corporate');

        return view('customer.Invitations.corporateinvite', compact('products', 'ratingsData'));
    }

    public function birthdayInvitations()
    {
        [$products, $ratingsData] = $this->invitationDataForEvent('Birthday');

        return view('customer.Invitations.birthdayinvite', compact('products', 'ratingsData'));
    }

    public function baptismInvitations()
    {
        [$products, $ratingsData] = $this->invitationDataForEvent('Baptism');

        return view('customer.Invitations.baptisminvite', compact('products', 'ratingsData'));
    }

    protected function invitationDataForEvent(string $eventType): array
    {
        $products = Product::query()
            ->with(['template', 'uploads', 'images', 'materials'])
            ->where('product_type', 'Invitation')
            ->where('event_type', $eventType)
            ->orderByDesc('updated_at')
            ->get();

        $ratingsData = [];
        foreach ($products as $product) {
            $ratings = \App\Models\OrderRating::whereHas('order.items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })->with('customer')->get();

            $product->setRelation('ratings', $ratings);

            $ratingsData[$product->id] = [
                'name' => $product->name,
                'ratings' => $ratings->map(function ($rating) {
                    return [
                        'rating' => $rating->rating,
                        'review' => $rating->review,
                        'submitted_at' => $rating->submitted_at?->format('M d, Y'),
                        'customer_name' => $rating->customer->name ?? 'Customer',
                        'photos' => $rating->photos ?? [],
                    ];
                })->toArray(),
                'average_rating' => $ratings->avg('rating'),
                'rating_count' => $ratings->count(),
            ];
        }

        return [$products, $ratingsData];
    }

    protected function giveawayProductsForEvent(string $eventType)
    {
        return Product::query()
            ->with(['template', 'uploads', 'images', 'materials'])
            ->where('product_type', 'Giveaway')
            ->whereHas('uploads')
            ->where(function ($query) use ($eventType) {
                $query->where('event_type', $eventType)
                      ->orWhereNull('event_type')
                      ->orWhere('event_type', '');
            })
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Product $product) {
                $product->setRelation('bulkOrders', collect());
            });
    }
}