<?php

namespace App\Contracts;

interface RateLimiterInterface
{
    public function isAllowed(string $phone): bool;
}
