<?php

namespace App\Services\Sms;

use App\Contracts\ProviderInterface;
use App\Models\Provider;

class ProviderFactory
{
    public function __construct(
        protected array $providerMap
    ) {}

    public function make(Provider $provider): ?ProviderInterface
    {
        $providerClass = $this->providerMap[$provider->name] ?? null;
        if ($providerClass && is_subclass_of($providerClass, ProviderInterface::class)) {
            return new $providerClass($provider);
        }
        return null;
    }
}
