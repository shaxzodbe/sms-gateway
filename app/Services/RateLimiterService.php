<?php

namespace App\Services;

use App\Contracts\RateLimiterInterface;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService implements RateLimiterInterface
{
    public function isAllowedForProvider(string $provider): bool
    {
        $key = "rate_limit:provider:$provider";
        $maxAttempts = config("sms.rate_limiter.$provider.limit", 5);
        $decaySeconds = config("sms.rate_limiter.$provider.window", 1);

        return RateLimiter::attempt($key, $maxAttempts, fn () => true, $decaySeconds);
    }
}
