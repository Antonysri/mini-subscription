<?php

namespace App\Http\Controllers;

// 🌟 API Resources (Plating Layer):
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubscriptionResource;

// 🌟 Standard JSON envelope trait: { success, message, data }
use App\Traits\ApiResponse;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * ApiResponse TRAIT:
     * Standard JSON envelope format { success: true, message: "...", data: [...] }
     */
    use ApiResponse;

    /**
     * GET CURRENT ACTIVE SUBSCRIPTION (GET /api/me/subscription)
     * 
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * 1. Login panna user-oda JWT token-la irunthu user identity edukum.
     * 2. User-oda active subscription-ah Eager-load 'plan'-oda fetch pannum.
     * 3. Subscription illana graceful-ah { data: null } return pannum (No crash).
     * 4. Subscription iruntha SubscriptionResource moolama days_remaining,
     *    plan name, formatted dates-oda clean JSON-ah anupum!
     */
    public function subscription(): JsonResponse
    {
        // 1. Current logged-in user:
        $user = auth('api')->user();

        // 2. Active Subscription Query (Eager-loading 'plan' to prevent N+1):
        // Phase 2-la User model-la namma ezhuthina activeSubscription() relationship!
        $subscription = $user->activeSubscription()
            ->with('plan')
            ->first();

        // 3. Graceful Null Check:
        if (! $subscription) {
            return $this->successResponse(null, 'No active subscription found');
        }

        // 4. Return formatted resource:
        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Active subscription retrieved successfully'
        );
    }

    /**
     * GET PAGINATED PAYMENT HISTORY (GET /api/me/payments)
     * 
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * 1. Authenticated user panna ella payments history-aiyum edukkum.
     * 2. Strict Tenant Isolation: User A cannot see User B's payments.
     * 3. Eager Loading ('with plan'): Single query-la plan names-ah load pannum (Zero N+1).
     * 4. Pagination: Total count, current page, per page metadata-voda neat-ah return pannum!
     */
    public function payments(Request $request): JsonResponse
    {
        // 1. Current logged-in user:
        $user = auth('api')->user();

        // 2. Query Parameter / Pagination Limits (Max 100 per page to prevent memory abuse):
        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        // 3. Eager loaded paginated query:
        $payments = $user->payments()
            ->with('plan')
            ->latest()
            ->paginate($perPage);

        // 4. Format data with PaymentResource and pagination metadata:
        $data = [
            'items'      => PaymentResource::collection($payments),
            'pagination' => [
                'total'        => $payments->total(),
                'per_page'     => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'has_more'     => $payments->hasMorePages(),
            ],
        ];

        return $this->successResponse($data, 'Payment history retrieved successfully');
    }
}