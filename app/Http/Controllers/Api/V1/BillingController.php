<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * Return the available subscription plans.
     */
    public function plans(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => 'free',
                    'name' => 'Free',
                    'price' => 0,
                    'currency' => 'USD',
                    'interval' => null,
                    'features' => [
                        '1 workspace',
                        '50 transactions / month',
                        'Basic cash flow reports',
                        'Fund management',
                        'Debt tracking',
                    ],
                ],
                [
                    'id' => config('polar.products.pro_monthly'),
                    'name' => 'Pro',
                    'price' => 900,
                    'currency' => 'USD',
                    'interval' => 'month',
                    'features' => [
                        'Unlimited workspaces',
                        'Unlimited transactions',
                        'Advanced projections',
                        'Excel import / export',
                        'Push payment reminders',
                        'Priority support',
                    ],
                ],
                [
                    'id' => config('polar.products.pro_yearly'),
                    'name' => 'Pro (Annual)',
                    'price' => 8900,
                    'currency' => 'USD',
                    'interval' => 'year',
                    'features' => [
                        'Everything in Pro',
                        '2 months free',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Return the current user's subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription();

        return response()->json([
            'data' => [
                'subscribed' => $user->subscribed(),
                'plan' => $subscription ? $subscription->type : 'free',
                'status' => $subscription?->status,
                'ends_at' => $subscription?->ends_at?->toIso8601String(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a Polar checkout session and return the URL.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'string'],
            'success_url' => ['nullable', 'url'],
        ]);

        $checkout = $request->user()
            ->checkout([$request->input('product_id')])
            ->withSuccessUrl($request->input('success_url', config('app.url')));

        return response()->json([
            'data' => ['checkout_url' => $checkout->url()],
        ]);
    }

    /**
     * Return the Polar customer portal URL.
     */
    public function portal(Request $request): JsonResponse
    {
        $portalUrl = $request->user()->customerPortalUrl();

        return response()->json([
            'data' => ['portal_url' => $portalUrl],
        ]);
    }
}
