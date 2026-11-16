<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * TRANSFORM PAYMENT RECORD INTO STANDARDIZED JSON ARRAY:
     * 
     * ❓ INTHA RESOURCE ENNA PANNUTHU?
     * User panna payments history-ah (invoice receipts mathiri)
     * clean JSON envelope-ah format panni anupum.
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. Payment ID:
            'id'                         => $this->id,

            // 2. Paid Amount & Currency:
            'amount'                     => $this->amount,
            'currency'                   => $this->currency,

            // 3. Payment Status ('pending', 'paid', 'failed'):
            'status'                     => $this->status,

            // 4. Stripe Session Reference:
            'stripe_checkout_session_id' => $this->stripe_checkout_session_id,

            // 5. Payment completed timestamp:
            'paid_at'                    => $this->paid_at?->toIso8601String(),

            // 6. Associated Plan Details:
            'plan'                       => [
                'id'   => $this->plan?->id,
                'name' => $this->plan?->name,
            ],

            // 7. Created timestamp:
            'created_at'                 => $this->created_at?->toIso8601String(),
        ];
    }
}