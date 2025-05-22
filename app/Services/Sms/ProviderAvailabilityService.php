<?php

namespace App\Services\Sms;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\ProviderInterface;
use App\Contracts\RateLimiterInterface;

class ProviderAvailabilityService
{
    public function __construct(
        protected CircuitBreakerInterface $circuitBreaker,
        protected RateLimiterInterface $rateLimiter,
    ) {}

    public function isAvailable(ProviderInterface $provider, string $phone): bool
    {
        $providerName = $provider->getProviderModel()->name;
        if (! $this->circuitBreaker->canProceed($providerName)) {
            return false;
        }
        if (! $this->rateLimiter->isAllowedForProvider($providerName)) {
            return false;
        }

        return true;
    }
}
