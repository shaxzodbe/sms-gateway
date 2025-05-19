<?php

namespace App\Contracts;

interface ProviderInterface
{
    public function send(string $phone, string $message, array $meta = []): bool;

    public function getProviderModel(): Provider;
}
