<?php

namespace App\Services\Sms;

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

    public function sendBatch(array $messages, array $metadata = []): bool
    {
        $provider = $this->providerSelector->selectProvider($messages[0]['phone'], $metadata);

        if (! $provider) {
            Log::error("No provider selected for batch send (provider_id: {$metadata['provider_id']})");

            return false;
        }

        try {
            $success = $provider->sendBatch($messages, $metadata);

            foreach ($messages as $msg) {
                if ($success) {
                    $this->providerSelector->handleSuccess($provider, $msg['type'] ?? 'general', $msg['source'] ?? 'default');
                } else {
                    $this->providerSelector->handleFailure($provider, $msg['uuid'] ?? null, $msg['type'] ?? 'general', $msg['source'] ?? 'default');
                }
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Batch send failed: '.$e->getMessage());

            foreach ($messages as $msg) {
                $this->providerSelector->handleFailure($provider, $msg['uuid'] ?? null, $msg['type'] ?? 'general', $msg['source'] ?? 'default');
            }

            return false;
        }
    }
}
