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
                          ->with('materials') // if needed
                          ->get();

        return view('customer.Invitations.weddinginvite', compact('products'));
    }

    public function weddingGiveaways()
    {
        $products = Product::query()
            ->with(['template', 'uploads', 'images', 'materials', 'bulkOrders'])
            ->where('product_type', 'Giveaway')
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