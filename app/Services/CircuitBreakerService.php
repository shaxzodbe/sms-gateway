<?php

namespace App\Services;

use App\Contracts\CircuitBreakerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreakerService implements CircuitBreakerInterface
{
    private function getDisabledKey(string $provider): string
    {
        return "circuit_breaker_disabled_$provider";
    }

    private function getFailureKey(string $provider): string
    {
        return "circuit_breaker_failures_$provider";
    }

    public function canProceed(string $provider): bool
    {
        $disabledKey = $this->getDisabledKey($provider);
        if (Cache::has($disabledKey)) {
            Log::info("Circuit breaker: Provider $provider temporarily disabled.");
            return false;
        }
        return true;
    }

    public function recordFailure(string $provider): void
    {
        $failureKey = $this->getFailureKey($provider);
        $disabledKey = $this->getDisabledKey($provider);
        Cache::add($failureKey, 0, config('sms.circuit_breaker.cooldown_seconds'));
        $failures = Cache::increment($failureKey);
        Log::warning("Circuit breaker: Failure #$failures for provider $provider");
        if ($failures >= config('sms.circuit_breaker.failure_threshold')) {
            Cache::put($disabledKey, true, config('sms.circuit_breaker.cooldown_seconds'));
            Log::warning("Circuit breaker: Provider $provider disabled for ".config('sms.circuit_breaker.cooldown_seconds').' seconds');
        }
        Cache::put($failureKey, $failures, config('sms.circuit_breaker.cooldown_seconds'));
    }

    public function recordSuccess(string $provider): void
    {
        $failureKey = $this->getFailureKey($provider);
        $disabledKey = $this->getDisabledKey($provider);
        Cache::forget($failureKey);
        Cache::forget($disabledKey);
        Log::info("Circuit breaker: Success recorded for provider $provider, failures reset.");
    }
}
