<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 🌟 OBSERVER IMPORTS:
// 1. Namma create panna PlanObserver-ah import panrom
use App\Observers\PlanObserver;

// 2. Laravel 13-oda modern Attribute class-ah import panrom
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * 🌟 OBSERVED BY ATTRIBUTE (PHP 8 / Laravel 13 Feature):
 * 
 * ❓ ITHU ENNA PANNUTHU?
 * Intha 'Plan' model-la epo create / update / delete nadanthaalum,
 * antha nodiyilaye 'PlanObserver'-ku notification anupu nu Laravel-ku solrom!
 * 
 * 💡 Old Laravel-la AppServiceProvider-la poi `Plan::observe(...)` nu ezhuthanum.
 * Aana modern Laravel 13-la Model-oda thalaiyilaye intha Attribute pottu direct-ah link pannidalaam!
 */
#[ObservedBy([PlanObserver::class])]
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
     * Oru Plan-ku pala Subscriptions irukalam (hasMany)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Oru Plan-ku pala Payments irukalam (hasMany)
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}