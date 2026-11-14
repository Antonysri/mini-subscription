<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Eloquent Relationships
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// 🌟 JWT CONTRACT: Intha interface-ah implement panna thaan JWT package namma User-ah identify pannum
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * MASS ASSIGNMENT PROTECTION:
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * HIDDEN ATTRIBUTES:
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
            'password' => 'hashed',
        ];
    }

    /**
     * 🌟 JWT METHOD 1: Unique Identifier
     * Token-kulla user-oda entha ID-ah store pannanum nu solla.
     * $this->getKey() namma user-oda 'id' (Primary key)-ah return pannum.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * 🌟 JWT METHOD 2: Custom Claims
     * Token payload-kulla extra-va ethavathu data poodanuma nu solla.
     * Standard-ah empty array [] thara vendiyathu thaan.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * RELATIONSHIP 1: User panna payments (HasMany)
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * RELATIONSHIP 2: User-oda total subscriptions (HasMany)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * RELATIONSHIP 3: User-oda Current Active Subscription (HasOne)
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }
}