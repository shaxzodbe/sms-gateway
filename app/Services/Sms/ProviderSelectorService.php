<?php

namespace App\Services\Sms;

use App\Contracts\ProviderInterface;

class ProviderSelectorService
{
    public function create(string $name): ?ProviderInterface
    {
        return match ($name) {
            'Eskiz' => new EskizProvider(),
            'Getsms' => new GetsmsProvider(),
            'Notify' => new NotifyProvider(),
            'Playmobile' => new PlaymobileProvider(),
            default => null,
        };
    }

    public function fromModel(Provider $provider): ?ProviderInterface
    {
        return $this->create($provider->name);
    }
}
