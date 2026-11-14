<?php

namespace App\Models;

// Laravel-oda base Model class
use Illuminate\Database\Eloquent\Model;

// Oru table innoru table-oda child-ah iruntha (Foreign Key vachuruka table) BelongsTo use panrom
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Oru row-ku innoru table-la ore oru row mattum associate aagum pothu HasOne use panrom
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    /**
     * MASS ASSIGNMENT PROTECTION:
     * Intha array-kulla irukura columns-la mattum thaan `Payment::create([...])`
     * vaazhiyaaga database-la insert panna mudiyum.
     * Yaravathu thevaillatha column-ah (eg: is_admin = 1) anupuna athu thadukapadum.
     */
    protected $fillable = [
        'user_id',                     // Intha payment pannina user-oda ID (Foreign key)
        'plan_id',                     // Entha plan vanga pay pannanga nu solra ID
        'stripe_checkout_session_id',  // Stripe create pannina Unique session ID (eg: cs_test_123)
        'stripe_payment_intent_id',    // Stripe-la real-ah debit aana transaction ID (eg: pi_456)
        'amount',                      // Pay panna snapshot amount (eg: 29.00). Plan price maarinallum intha history maarathu!
        'currency',                    // Currency type (eg: 'usd', 'inr')
        'status',                      // Payment status: 'pending' (redirect aagum pothu), 'paid' (webhook confirm aana pothu), 'failed'
        'paid_at',                     // Stripe webhook payment success aana antha date & time
    ];

    /**
     * ATTRIBUTE CASTING:
     * Database-la irunthu vara string data-va automatic-ah proper PHP types-ah mathum.
     */
    protected function casts(): array
    {
        return [
            // DB-la '29.00' nu string-ah irunthalum code-la 29.00 Float/Decimal number-ah kedaikum
            'amount' => 'decimal:2',

            // Intha string date-ah Carbon Date Object-ah mathum.
            // Example: $payment->paid_at->diffForHumans() -> "2 hours ago" nu format pannalam
            'paid_at' => 'datetime',
        ];
    }

    /**
     * RELATIONSHIP 1: Intha payment entha user pannaru? (BelongsTo User)
     * 
     * Example:
     * $payment = Payment::find(1);
     * echo $payment->user->name;   // Output: "Antony"
     * echo $payment->user->email;  // Output: "antony@example.com"
     */
    public function user(): BelongsTo
    {
        // payments table-la irukura 'user_id' column-ah vachu users table-oda id-ah map pannum
        return $this->belongsTo(User::class);
    }

    /**
     * RELATIONSHIP 2: Intha payment entha plan-kaga kattirukanga? (BelongsTo Plan)
     * 
     * Example:
     * $payment = Payment::find(1);
     * echo $payment->plan->name;   // Output: "Professional Plan"
     * echo $payment->plan->price;  // Output: "29.00"
     */
    public function plan(): BelongsTo
    {
        // payments table-la irukura 'plan_id' column-ah vachu plans table-oda id-ah map pannum
        return $this->belongsTo(Plan::class);
    }

    /**
     * RELATIONSHIP 3: Intha payment moolama entha subscription activate aachu? (HasOne Subscription)
     * 
     * Example:
     * $payment = Payment::find(1);
     * echo $payment->subscription->status;      // Output: "active"
     * echo $payment->subscription->expires_at;  // Output: "2026-12-14 15:30:00"
     */
    public function subscription(): HasOne
    {
        // subscriptions table-la irukura 'payment_id' column-oda map pannum
        return $this->hasOne(Subscription::class);
    }
}