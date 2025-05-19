<?php

namespace Tests\Feature;

use App\Contracts\RateLimiterInterface;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('rate_limit:provider:Eskiz');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('rate_limit:provider:Eskiz');
        parent::tearDown();
    }

    public function test_rate_limiter_allows_requests_within_limit(): void
    {
        /** @var RateLimiterInterface $rateLimiter */
        $rateLimiter = $this->app->make(RateLimiterInterface::class);
        $limit = config('sms.rate_limiter.Eskiz.limit', 5);

        for ($i = 0; $i < $limit; $i++) {
            $this->assertTrue($rateLimiter->isAllowedForProvider('Eskiz'), "Request $i should be allowed");
        }
    }

    public function test_rate_limiter_blocks_requests_above_limit(): void
    {
        /** @var RateLimiterInterface $rateLimiter */
        $rateLimiter = $this->app->make(RateLimiterInterface::class);

        $limit = config('sms.rate_limiter.Eskiz.limit', 5);

        // Превышаем лимит
        for ($i = 0; $i < $limit; $i++) {
            $rateLimiter->isAllowedForProvider('Eskiz');
        }

        $this->assertFalse($rateLimiter->isAllowedForProvider('Eskiz'));
    }

    public function test_rate_limiter_resets_after_window(): void
    {
        /** @var RateLimiterInterface $rateLimiter */
        $rateLimiter = $this->app->make(RateLimiterInterface::class);

        $limit = config('sms.rate_limiter.Eskiz.limit', 5);
        $window = config('sms.rate_limiter.Eskiz.window', 1);

        for ($i = 0; $i < $limit; $i++) {
            $rateLimiter->isAllowedForProvider('Eskiz');
        }

        sleep($window + 1);

        $this->assertTrue($rateLimiter->isAllowedForProvider('Eskiz'));
    }
}
