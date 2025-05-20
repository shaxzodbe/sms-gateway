<?php

namespace App\Services\Sms\Providers;

use App\Contracts\ProviderInterface;
use App\Models\Provider;
use Illuminate\Support\Facades\Http;

class EskizProvider implements ProviderInterface
{
    public function __construct(protected Provider $provider) {}

    public function send(string $phone, string $message, array $metaData = []): bool
    {
        try {
            $payload = [
                'mobile_phone' => $phone,
                'message' => $message,
                'from' => '4546',
                'callback_url' => route('sms.eskiz.callback'),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->provider->token,
                'Content-Type' => 'application/json',
            ])->post($this->provider->endpoint.'/send', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $requestId = $responseData['id'] ?? null;
                $providerStatus = $responseData['status'] ?? null;

                $internalStatus = match ($providerStatus) {
                    'waiting' => History::STATUS_SENT,
                    default => History::STATUS_FAILED,
                };

                if ($requestId) {
                    History::create([
                        'phone' => $phone,
                        'message' => $message,
                        'sms_provider_id' => $this->provider->id,
                        'status' => $internalStatus,
                        'uuid' => $metadata['uuid'],
                        'metadata' => json_encode($metadata),
                        'external_id' => $requestId,
                    ]);
                }
                Log::info("[Eskiz] SMS sent to $phone successfully.");

                return true;
            } else {
                Log::error("[Eskiz] Failed to send SMS $phone. Response: ".$response->body());

                return false;
            }
        } catch (\Exception $e) {
            Log::error('[Eskiz] Failed to send SMS to '.$phone.'. Exception: '.$e->getMessage());

            return false;
        }
    }

    public function getProviderModel(): Provider
    {
        return Provider::query()
            ->where('name', 'Eskiz')
            ->where('is_active', 1)
            ->first();
    }
}
