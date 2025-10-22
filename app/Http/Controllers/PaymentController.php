<?php

namespace App\Http\Controllers;

use Stripe\StripeClient;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentController extends Controller
{
    public function getPlans()
    {
        $plans = Plan::all();
        return $plans->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'amount' => $plan->amount,
                'interval' => $plan->interval,
                'has_unlimited_data' => $plan->unlimited_data,
                'has_unlimited_calls' => $plan->unlimited_calls,
                'is_esim_enabled' => $plan->esim_enabled,
                'features' => $plan->features
            ]);
    }

    public function createPaypalPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string'
        ]);
        $provider = new PayPalClient();
        $accessToken = $provider->getAccessToken()['access_token'];

        // Generate a unique PayPal-Request-Id
        $paypalRequestId = (string) Str::uuid();

        // Create PayPal Order
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => $paypalRequestId, // Ensures idempotency
            ])
            ->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', [
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "GBP",
                            "value" => $request->amount,
                        ],
                        "description" => $request->description,
                    ]
                ],
                "payment_source" => [
                    "paypal" => [
                        "experience_context" => [
                            "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                            "landing_page" => "LOGIN",
                            "shipping_preference" => "GET_FROM_FILE",
                            "user_action" => "PAY_NOW",
                            'return_url' => route('payment.paypal.success'),
                            'cancel_url' => route('payment.paypal.cancel'),
                        ]
                    ]
                ],
            ]);

        if (!$response->successful()) return response()->json(['error' => 'Failed to create payment'], 500);

        Transaction::create([
            'user_id' => $request->user()->id,
            'reference' => $response->json('id'),
            'amount' => $request->amount,
            'status' => 'PENDING',
            'type' => 'debit',
            'description' => $request->description
        ]);
        $approvalUrl = collect($response['links'])->where('rel', 'payer-action')->first()['href'];
        return response()->json(['url' => $approvalUrl]);
        // return response()->json($response->json());
    }

    public function paypalPaymentSuccess(Request $request)
    {
        $orderId = $request->token;
        $provider = new PayPalClient();
        $accessToken = $provider->getAccessToken()['access_token'];
        Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json'
            ])
            ->post("https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderId/capture", null);

        return approvePayment($orderId);
        // return response()->json(['message' => 'Your payment is being processed. Your payment status will be updated as possible']);
    }

    public function paypalPaymentCancel(Request $request)
    {
        Transaction::where('reference', $request->token)->delete();
        return response()->json(['message' => 'Transaction cancelled']);
    }

    public function createStripePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string'
        ]);
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        // Create a Stripe Checkout session
        $response = $stripe->checkout->sessions->create([
            'client_reference_id' => uniqid(more_entropy: true),
            // 'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'gbp',
                    'product_data' => [
                        'name' => 'Wallet Funding',
                        'description' => $request->description,
                    ],
                    'unit_amount' => $request->amount*100, // Amount in cents (20.00 GBP)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.stripe.cancel').'?session_id={CHECKOUT_SESSION_ID}',
        ]);
        if (!$response) {
            return response()->json(['error' => 'Failed to create payment'], 500);
        }

        Transaction::create([
            'user_id' => $request->user()->id,
            'reference' => $response->client_reference_id,
            'amount' => $request->amount,
            'status' => 'PENDING',
            'type' => 'debit',
            'description' => $request->description
        ]);
        return response()->json(['url' => $response['url']]);
    }

    public function stripePaymentSuccess(Request $request)
    {
        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $session = $stripe->checkout->sessions->retrieve($request->session_id);
        return approvePayment($session->client_reference_id);
    }

    public function stripePaymentCancel(Request $request)
    {
        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $session = $stripe->checkout->sessions->retrieve($request->session_id);
        Transaction::where('reference', $session->client_reference_id)->delete();
        return response()->json(['message' => 'Transaction cancelled']);
    }

    public function createPaystackPayment(Request $request)
    {
         $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string'
        ]);
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->withHeader('Content-Type', 'application/json')
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $request->user()->preference()->email ?? 'doe@email.com',
                'amount' => $request->amount*100,
                'callback_url' => route('payment.paystack.success'),
                'metadata' => [
                    'cancel_action' => route('payment.paystack.cancel')
                ]
            ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to create payment'], 500);
        }

        Transaction::create([
            'user_id' => $request->user()->id,
            'reference' => $response->json('data.reference'),
            'amount' => $request->amount,
            'status' => 'PENDING',
            'type' => 'debit',
            'description' => $request->description
        ]);
        return response()->json(['url' => $response->json()['data']['authorization_url']]);
    }

    public function paystackPaymentSuccess(Request $request)
    {
        return approvePayment($request->reference);
        // return response()->json(['message' => 'Your payment is being processed. Your payment status will be updated as possible']);
    }

    public function paystackPaymentCancel(Request $request)
    {
        // dd($request->all());
        // Transaction::where('reference', $session->client_reference_id)->delete();
        return response()->json(['message' => 'Transaction cancelled']);
    }
}
