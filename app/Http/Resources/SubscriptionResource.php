<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * TRANSFORM THE RESOURCE INTO A STANDARDIZED JSON ARRAY:
     * 
     * ❓ INTHA RESOURCE ENNA PANNUTHU?
     * Database-la irukura Subscription record-ah frontend (React/Vue/Mobile App)
     * thevaipadra ultra-clean JSON structure-ah transform pannum.
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. Subscription Unique ID:
            'id'             => $this->id,

            // 2. Status ('active', 'expired', 'canceled'):
            'status'         => $this->status,

            // 3. Computed Boolean Helper (Phase 2-la namma model-la ezhuthina isActive() method):
            'is_active'      => $this->isActive(),

            // 4. Computed Days Remaining Accessor (Innum ethana naal expire aaga baaki irukku):
            'days_remaining' => $this->days_remaining,

            // 5. Dates in ISO 8601 Standard Format (JavaScript-friendly):
            'starts_at'      => $this->starts_at?->toIso8601String(),
            'expires_at'     => $this->expires_at?->toIso8601String(),

            // 6. Nested Plan Details (Plan table data):
            'plan'           => [
                'id'            => $this->plan?->id,
                'name'          => $this->plan?->name,
                'price'         => $this->plan?->price,
                'currency'      => $this->plan?->currency,
                'duration_days' => $this->plan?->duration_days,
            ],

            // 7. Record created timestamp:
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}