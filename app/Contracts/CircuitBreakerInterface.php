<?php

namespace App\Contracts;

interface CircuitBreakerInterface
{
    public function canProceed(string $provider): bool;

    public function recordFailure(string $provider): void;

    public function recordSuccess(string $provider): void;
}
