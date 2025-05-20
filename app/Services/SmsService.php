<?php

namespace App\Services;

use App\Contracts\ProviderSelectorInterface;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        protected ProviderSelectorInterface $providerSelector,
    ) {}

    public function send(string $phone, string $message, array $metadata = []): bool
    {
        $provider = $this->providerSelector->selectProvider($phone, $metadata);
        if (! $provider) {
            Log::error("No provider selected for phone: $phone");

            return false;
        }
        try {
            $success = $provider->send($phone, $message, $metadata);
            if ($success) {
                $this->providerSelector->handleSuccess($provider, $metadata['type'] ?? 'general', $metadata['source'] ?? 'default');

                return true;
            }
            $this->providerSelector->handleFailure($provider, $metadata['uuid'] ?? null, $metadata['type'] ?? 'general', $metadata['source'] ?? 'default');
        } catch (\Throwable $e) {
            Log::error('Error sending SMS: '.$e->getMessage());
            $this->providerSelector->handleFailure($provider, $metadata['uuid'] ?? null, $metadata['type'] ?? 'general', $metadata['source'] ?? 'default');
        }

        return false;
    }
}
