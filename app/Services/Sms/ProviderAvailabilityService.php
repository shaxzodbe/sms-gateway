<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Cache;

class ProviderAvailabilityService
{
    private const CIRCUIT_BREAKER_LIMIT = 3;
    private const CIRCUIT_BREAKER_UUID_LIMIT = 1;
    private const CIRCUIT_BREAKER_GLOBAL_FAILURES = 10;
    private const CIRCUIT_BREAKER_WINDOW = 300;
    private const CIRCUIT_BREAKER_TTL = 300;

    public function isAvailable(Provider $provider, ?string $uuid, string $type, string $source): bool
    {
        $globalFailureKey = "provider_{$provider->id}_{$type}_{$source}_global_failures";
        $disabledKey = "provider_{$provider->id}_{$type}_{$source}_disabled";
        $uuidFailureKey = "provider_{$provider->id}_{$type}_{$source}_uuid_{$uuid}_failures";

        if (Cache::has($disabledKey)) return false;
        if (Cache::get($globalFailureKey, 0) >= self::CIRCUIT_BREAKER_GLOBAL_FAILURES) {
            Cache::put($disabledKey, true, self::CIRCUIT_BREAKER_TTL);
            return false;
        }

        if ($uuid && Cache::get($uuidFailureKey, 0) >= self::CIRCUIT_BREAKER_UUID_LIMIT) {
            return false;
        }

        return true;
    }

    public function recordFailure(Provider $provider, ?string $uuid, string $type, string $source): void
    {
        $failureKey = "provider_{$provider->id}_{$type}_{$source}_failures";
        $globalFailureKey = "provider_{$provider->id}_{$type}_{$source}_global_failures";
        $uuidFailureKey = "provider_{$provider->id}_{$type}_{$source}_uuid_{$uuid}_failures";
        $disabledKey = "provider_{$provider->id}_{$type}_{$source}_disabled";

        Cache::increment($failureKey);
        Cache::increment($globalFailureKey);

        if ($uuid) {
            Cache::put($uuidFailureKey, 1, self::CIRCUIT_BREAKER_TTL);
        }

        if (Cache::get($failureKey, 0) >= self::CIRCUIT_BREAKER_LIMIT) {
            Cache::put($disabledKey, true, self::CIRCUIT_BREAKER_TTL);
        }

        Cache::put($globalFailureKey, Cache::get($globalFailureKey), self::CIRCUIT_BREAKER_WINDOW);
    }

    public function recordSuccess(Provider $provider, string $type, string $source): void
    {
        $failureKey = "provider_{$provider->id}_{$type}_{$source}_failures";
        $globalFailureKey = "provider_{$provider->id}_{$type}_{$source}_global_failures";

        Cache::forget($failureKey);

        if (Cache::has($globalFailureKey)) {
            Cache::decrement($globalFailureKey);
        }
    }
}
