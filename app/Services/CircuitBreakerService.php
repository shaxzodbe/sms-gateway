<?php

namespace App\Services;

use App\Contracts\CircuitBreakerInterface;

class CircuitBreakerService implements CircuitBreakerInterface
{
    protected array $failures = [];
    protected array $lastFailedAt = [];
    protected int $threshold = 5;
    protected int $cooldown = 60; // секунд

    public function canProceed(string $provider): bool
    {
        // TODO: Implement canProceed() method.
    }

    public function recordFailure(string $provider): void
    {
        // TODO: Implement recordFailure() method.
    }

    public function recordSuccess(string $provider): void
    {
        // TODO: Implement recordSuccess() method.
    }
}
