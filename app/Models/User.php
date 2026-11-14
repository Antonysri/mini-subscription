<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Oru user-ku niraiya records iruntha HasMany use panrom (eg: multiple payments)
use Illuminate\Database\Eloquent\Relations\HasMany;

// Oru user-ku ore oru record mattum filter panni edukka HasOne use panrom (eg: currently active subscription)
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * MASS ASSIGNMENT PROTECTION:
     * User register aagum pothu intha 3 fields mattum thaan client side-la irunthu allow aagum.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * HIDDEN ATTRIBUTES:
     * User details API JSON response-ah veliya pogum pothu
     * password hash-um remember_token-um veliya theriyaama hide pannidum.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ATTRIBUTE CASTING:
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Password create aagum bothey automatic-ah bcrypt hash aagidum
        ];
    }

    /**
     * RELATIONSHIP 1: Oru user-ku niraiya payments irukalam (HasMany Payments)
     * 
     * Example:
     * $user = auth()->user();
     * foreach ($user->payments as $payment) {
     *     echo $payment->amount; // User panna ella payments list-um edukalam (Payment History API)
     * }
     */
    public function payments(): HasMany
    {
        // payments table-la irukura 'user_id' column-ah vachu map pannum
        return $this->hasMany(Payment::class);
    }

    /**
     * RELATIONSHIP 2: Oru user-ku niraiya subscriptions (pazhayathu + puthusu) irukalam (HasMany Subscriptions)
     * 
     * Example:
     * $user = auth()->user();
     * echo $user->subscriptions()->count(); // Intha user total-ah ethana thadava subscribe pannirukaru nu paக்கலாம்
     */
    public function subscriptions(): HasMany
    {
        // subscriptions table-la irukura 'user_id' column-ah vachu map pannum
        return $this->hasMany(Subscription::class);
    }

    /**
     * RELATIONSHIP 3: Oru user-ku CURRENT-AH ACTIVE-AH IRUKURA subscription mattum eduka (HasOne Active Subscription)
     * 
     * Ithu romba smart-ana relationship:
     * 1. status 'active'-ah irukanum
     * 2. expires_at innum mudiyama irukanum (> now())
     * 3. latestOfMany() moolama kadasia vangunatha mattum edukkum
     * 
     * Example:
     * $user = auth()->user();
     * if ($user->activeSubscription) {
     *     echo "Welcome! Your plan: " . $user->activeSubscription->plan->name;
     *     echo "Days left: " . $user->activeSubscription->days_remaining;
     * } else {
     *     echo "No active subscription. Please upgrade!";
     * }
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }
}