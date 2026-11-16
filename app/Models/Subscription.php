<?php

namespace App\Models;

// Laravel-oda base Model class
use Illuminate\Database\Eloquent\Model;

// Oru table innoru table-oda child-ah iruntha (Foreign Key irukura table) BelongsTo use panrom
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /**
     * MASS ASSIGNMENT PROTECTION:
     * Intha array-kulla irukura columns-la mattum thaan `Subscription::create([...])`
     * vaazhiyaaga database-la insert / update panna mudiyum.
     */
    protected $fillable = [
        'user_id',     // Intha subscription yaarodathu nu solra User ID (Foreign key)
        'plan_id',     // User entha plan-la irukaaru nu solra Plan ID (Foreign key)
        'payment_id',  // Entha payment moolama intha subscription activate aachu nu solra Payment ID (Unique Foreign key)
        'status',      // Subscription status: 'active' (valid-ah iruku), 'expired' (mudinjiduchu), 'canceled'
        'starts_at',   // Intha subscription thuvangina Date & Time
        'expires_at',  // Intha subscription mudiyum Date & Time (starts_at + plan duration_days)
    ];

    /**
     * ATTRIBUTE CASTING:
     * Database-la string-ah irukura dates-ah automatic-ah Carbon Date Object-ah mathum.
     * Ithaala namakku Date calculations (remaining days, formatting) romba easy aagum.
     */
    protected function casts(): array
    {
        return [
            // Example: $subscription->starts_at->format('d-M-Y') -> "14-Nov-2026"
            'starts_at' => 'datetime',

            // Example: $subscription->expires_at->isPast() -> expired aayiducha illaya nu boolean tharum
            'expires_at' => 'datetime',
        ];
    }

    /**
     * HELPER METHOD: Intha subscription ippo unmaiyave active-ah iruka nu check panna.
     * Status 'active'-ah irukanum, athoda expires_at innum mudiyama future date-la irukanum.
     * 
     * Example:
     * if ($subscription->isActive()) {
     *     echo "User can access premium features!";
     * }
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    /**
     * HELPER ACCESSOR: Innum ethana naatkal intha subscription baaki iruku (Days Remaining)?
     * Phase 7 API-ku ithu direct-ah use aagum.
     * 
     * Example:
     * echo $subscription->days_remaining; // Output: 28 (days left)
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->expires_at->isPast()) {
            return 0; // Already mudinjiduchu na 0 days
        }

        // Current time-kum expires_at-kum nadula irukura days difference (ceil for accurate remaining calendar days):
        return (int) ceil(now()->floatDiffInDays($this->expires_at, false));
    }

    /**
     * RELATIONSHIP 1: Intha subscription entha user-ku sonthamaanathu? (BelongsTo User)
     * 
     * Example:
     * $sub = Subscription::find(1);
     * echo $sub->user->name;   // Output: "Antony"
     * echo $sub->user->email;  // Output: "antony@example.com"
     */
    public function user(): BelongsTo
    {
        // subscriptions table-la irukura 'user_id' column-ah vachu users table-oda map pannum
        return $this->belongsTo(User::class);
    }

    /**
     * RELATIONSHIP 2: Intha subscription entha plan-oda sernthathu? (BelongsTo Plan)
     * 
     * Example:
     * $sub = Subscription::find(1);
     * echo $sub->plan->name;          // Output: "Professional Plan"
     * echo $sub->plan->duration_days; // Output: 30
     */
    public function plan(): BelongsTo
    {
        // subscriptions table-la irukura 'plan_id' column-ah vachu plans table-oda map pannum
        return $this->belongsTo(Plan::class);
    }

    /**
     * RELATIONSHIP 3: Intha subscription-kaga kattiya payment details enna? (BelongsTo Payment)
     * 
     * Example:
     * $sub = Subscription::find(1);
     * echo $sub->payment->amount;   // Output: "29.00"
     * echo $sub->payment->status;   // Output: "paid"
     */
    public function payment(): BelongsTo
    {
        // subscriptions table-la irukura 'payment_id' column-ah vachu payments table-oda map pannum
        return $this->belongsTo(Payment::class);
    }
}