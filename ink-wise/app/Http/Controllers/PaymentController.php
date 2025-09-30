<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Paymongo\Paymongo;

class PaymentController extends Controller
{
    public function createGCashPayment(Request $request)
    {
        $paymongo = new Paymongo(env('PAYMONGO_SECRET_KEY'));

        // Amount is in centavos (10000 = ₱100.00)
        $paymentIntent = $paymongo->paymentIntent()->create([
            'amount' => 10000,
            'payment_method_allowed' => ['gcash'],
            'currency' => 'PHP',
        ]);

        // Create payment method for GCash
        $paymentMethod = $paymongo->paymentMethod()->create([
            'type' => 'gcash',
            'billing' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ],
        ]);

        // Attach method to intent
        $attached = $paymongo->paymentIntent()->attach(
            $paymentIntent['id'],
            ['payment_method' => $paymentMethod['id']]
        );

        return response()->json([
            'redirect_url' => $attached['next_action']['redirect']['url']
        ]);
    }

    // Webhook for payment status
    public function webhook(Request $request)
    {
        $event = $request->all();

        if ($event['data']['attributes']['status'] === 'succeeded') {
            // Handle successful payment (update DB, mark order as paid)
        }

        return response()->json(['status' => 'ok']);
    }
}
