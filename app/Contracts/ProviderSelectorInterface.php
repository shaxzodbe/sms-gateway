<?php

namespace App\Contracts;

use App\Services\Sms\Providers\Contracts\ProviderInterface;

interface ProviderSelectorInterface
{
    public function selectProvider(string $phone, array $metadata = []): ?ProviderInterface;

    public function handleFailure(ProviderInterface $provider, ?string $uuid, string $type, string $source): void;

    public function handleSuccess(ProviderInterface $provider, string $type, string $source): void;
}
