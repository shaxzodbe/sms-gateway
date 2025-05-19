<?php

namespace App\Services;

use App\Contracts\RateLimiterInterface;
use Illuminate\Support\Facades\Cache;

class RateLimiterService implements RateLimiterInterface
{
    public function isAllowed(string $phone): bool
    {
        $key = "rate_limit:sms:{$phone}";
        $limit = 5;
        $ttl = 60;

        return Cache::remember($key, $ttl, function () {
                return 1;
            }) <= $limit;
    }
}
