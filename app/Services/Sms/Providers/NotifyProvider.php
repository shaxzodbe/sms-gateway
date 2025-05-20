<?php

namespace App\Services\Sms\Providers;

use App\Contracts\Provider;
use App\Contracts\ProviderInterface;

class NotifyProvider implements ProviderInterface
{

    public function send(string $phone, string $message, array $metaData = []): bool
    {
        // TODO: Implement send() method.
    }

    public function getProviderModel(): Provider
    {
        // TODO: Implement getProviderModel() method.
    }
}
