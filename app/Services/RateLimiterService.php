<?php

namespace App\Services;

use App\Contracts\RateLimiterInterface;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService implements RateLimiterInterface
{
    /*public function isAllowed(string $phone): bool
    {
        $key = "rate_limit:phone:$phone";
        $limit = config('sms.rate_limiter.limit');
        $window = config('sms.rate_limiter.window');

        return RateLimiter::attempt($key, $limit, fn() => true, $window);
    } // rate limiting based on phone */

    public function isAllowedForProvider(string $provider): bool
    {
        $key = "rate_limit:provider:$provider";
        $limit = config("sms.rps.$provider.limit", 5);
        $window = config("sms.rps.$provider.window", 1);

        return RateLimiter::attempt($key, $limit, fn () => true, $window);
    }
}
