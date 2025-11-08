<?php

namespace App\Services\Checkout;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class CheckoutSessionService
{
    protected const CACHE_PREFIX = 'checkout_session_';

    public static function store(string $sessionId, array $payload, int $ttlSeconds = 1800): void
    {
        Cache::put(self::CACHE_PREFIX . $sessionId, $payload, $ttlSeconds);
    }

    public static function retrieve(string $sessionId): ?array
    {
        $data = Cache::get(self::CACHE_PREFIX . $sessionId);
        return is_array($data) ? $data : null;
    }

    public static function consume(string $sessionId): ?array
    {
        $payload = self::retrieve($sessionId);
        if ($payload !== null) {
            Cache::forget(self::CACHE_PREFIX . $sessionId);
        }
        return $payload;
    }

    public static function forget(string $sessionId): void
    {
        Cache::forget(self::CACHE_PREFIX . $sessionId);
    }
}

