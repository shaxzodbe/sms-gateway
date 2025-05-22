<?php

namespace App\Providers;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\MessageConsumerInterface;
use App\Contracts\ProviderSelectorInterface;
use App\Contracts\RateLimiterInterface;
use App\Services\CircuitBreakerService;
use App\Services\RabbitMQService;
use App\Services\RateLimiterService;
use App\Services\Sms\ProviderFactory;
use App\Services\Sms\ProviderSelectorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderFactory::class, function ($app) {
            return new ProviderFactory(config('sms.providers'));
        });
        $this->app->bind(MessageConsumerInterface::class, RabbitMQService::class);
        $this->app->bind(ProviderSelectorInterface::class, ProviderSelectorService::class);
        $this->app->bind(CircuitBreakerInterface::class, CircuitBreakerService::class);
        $this->app->bind(RateLimiterInterface::class, RateLimiterService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
