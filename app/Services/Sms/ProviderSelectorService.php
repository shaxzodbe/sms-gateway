<?php

namespace App\Services\Sms;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\ProviderInterface;
use App\Contracts\ProviderSelectorInterface;
use App\Models\Provider;
use Illuminate\Support\Facades\Log;

class ProviderSelectorService implements ProviderSelectorInterface
{
    public function __construct(
        protected ProviderAvailabilityService $availabilityService,
        protected ProviderFactory $providerFactory,
        protected CircuitBreakerInterface $circuitBreaker,
    ) {}

    public function selectProvider(string $phone, array $metadata = []): ?ProviderInterface
    {
        $providers = Provider::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($providers as $providerModel) {
            $provider = $this->providerFactory->make($providerModel);
            if ($provider && $this->availabilityService->isAvailable($provider, $phone)) {
                return $provider;
            }
        }
        Log::warning("No available providers found for phone: $phone");

        return null;
    }

    public function handleFailure(ProviderInterface $provider, ?string $uuid, string $type, string $source): void
    {
        $providerName = $provider->getProviderModel()->name;
        $this->circuitBreaker->recordFailure($providerName);
    }

    public function handleSuccess(ProviderInterface $provider, string $type, string $source): void
    {
        $providerName = $provider->getProviderModel()->name;
        $this->circuitBreaker->recordSuccess($providerName);
    }
}
