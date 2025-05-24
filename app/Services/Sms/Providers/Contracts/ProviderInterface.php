<?php

namespace App\Services\Sms\Providers\Contracts;

use App\Models\Provider;

interface ProviderInterface
{
    public function send(string $phone, string $message, array $metadata = []): bool;

    public function getProviderModel(): Provider;
}
