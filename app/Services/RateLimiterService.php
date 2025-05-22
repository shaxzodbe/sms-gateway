<?php

namespace App\Services;

use App\Contracts\RateLimiterInterface;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService implements RateLimiterInterface
{
    public function isAllowedForProvider(string $provider): bool
    {
        $key = "rate_limit:provider:$provider";
        $limit = config("sms.rate_limiter.$provider.limit", 5);
        $window = config("sms.rate_limiter.$provider.window", 1);

        return RateLimiter::attempt($key, $limit, fn () => true, $window);
    }
}
