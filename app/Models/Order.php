<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'provider_ids',
        'total_amount',
        'order_status',
        'shipping_address',
        'shipping_address_id',
        'payment_method_id',
        'notes',
        'discount_code',
        'discount_amount'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'provider_ids' => 'array', // Cast JSON to array
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Products\Models\Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payment(): HasMany
    {
        // Some stores may allow multiple payment attempts per order
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all providers for this order (from provider_ids array)
     */
    public function providers()
    {
        if (!$this->provider_ids || empty($this->provider_ids)) {
            return collect([]);
        }
        return User::whereIn('id', $this->provider_ids)->get();
    }

    /**
     * Get the primary provider (first in the array)
     */
    public function getPrimaryProvider()
    {
        if ($this->provider_ids && count($this->provider_ids) > 0) {
            return User::find($this->provider_ids[0]);
        }
        return null;
    }

    /**
     * Get the primary provider ID
     */
    public function getPrimaryProviderId()
    {
        if ($this->provider_ids && count($this->provider_ids) > 0) {
            return $this->provider_ids[0];
        }
        return null;
    }

    /**
     * Get all unique provider IDs from this order
     */
    public function getAllProviderIds()
    {
        return $this->provider_ids ?? [];
    }

    /**
     * Add a provider ID to the array
     */
    public function addProviderId($providerId)
    {
        $providerIds = $this->provider_ids ?? [];
        if (!in_array($providerId, $providerIds)) {
            $providerIds[] = $providerId;
            $this->provider_ids = $providerIds;
        }
    }

    /**
     * Check if order contains items from a specific provider
     */
    public function containsProvider($providerId)
    {
        return in_array($providerId, $this->provider_ids ?? []);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
