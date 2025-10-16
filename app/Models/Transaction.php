<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'gateway',
        'gateway_payment_id',
        'gateway_order_id',
        'amount',
        'currency',
        'status',
        'processed_via',
        'error_code',
        'error_message',
        'payload',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payload' => 'array',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


