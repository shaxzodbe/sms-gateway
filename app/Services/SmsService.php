<?php

namespace App\Services;

use App\Contracts\ProviderInterface;
use App\Contracts\RateLimiterInterface;

class SmsService
{
    public function __construct(
        private ProviderInterface $smsProvider,
        private RateLimiterInterface $rateLimiter
    )
    {}
}
