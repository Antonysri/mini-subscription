<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'duration_days',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * ஒரு Plan-க்கு பல Subscriptions இருக்கலாம் (hasMany)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * ஒரு Plan-க்கு பல Payments இருக்கலாம் (hasMany)
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}