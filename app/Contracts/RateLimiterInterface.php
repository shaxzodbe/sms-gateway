<?php

namespace App\Contracts;

interface RateLimiterInterface
{
    public function isAllowedForProvider(string $provider): bool;
}
