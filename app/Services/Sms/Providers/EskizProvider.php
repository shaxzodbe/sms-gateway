<?php

namespace App\Services\Sms\Providers;

use App\Contracts\ProviderInterface;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EskizProvider implements ProviderInterface
{
    public function __construct(protected Provider $provider) {}

    public function send(string $phone, string $message, array $metadata = []): bool
    {
        try {
            $payload = [
                'mobile_phone' => $phone,
                'message' => $message,
                'from' => '4546',
                'callback_url' => route('eskiz.callback'),
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
                    'waiting' => MessageStatus::SENT->value,
                    default => MessageStatus::FAILED->value,
                };

                if ($requestId) {
                    Message::create([
                        'phone' => $phone,
                        'text' => $message,
                        'provider_id' => $this->provider->id,
                        'status' => $internalStatus,
                        'metadata' => json_encode($metadata),
                        'request_id' => $requestId,
                        'sent_at' => now(),
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

    public function batchSend(array $phones, string $message, array $metadata = []): bool
    {
        try {
            if (! $this->provider) {
                Log::error('[Eskiz] SMS Provider not found or inactive.');

                return false;
            }

            $messages = collect($phones)->map(function ($phone, $index) use ($message, $metadata) {
                $uuid = $metadata['uuid'][$index] ?? Str::uuid()->toString();

                return [
                    'user_sms_id' => $uuid,
                    'to' => $phone,
                    'text' => $message,
                ];
            });

            $payload = [
                'messages' => $messages,
                'from' => '4546',
                'callback_url' => route('eskiz.callback'),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->provider->token,
                'Content-Type' => 'application/json',
            ])->post($this->provider->endpoint.'send-batch/', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $requestId = $responseData['id'] ?? null;
                $providerStatuses = $responseData['status'] ?? [];

                foreach ($messages as $index => $msg) {
                    $providerStatus = $providerStatuses[$index] ?? 'unknown';
                    $internalStatus = match ($providerStatus) {
                        'waiting' => MessageStatus::PENDING->value,
                        default => MessageStatus::FAILED->value,
                    };

                    Message::create([
                        'phone' => $msg['to'],
                        'message' => $msg['text'],
                        'sms_provider_id' => $this->provider->id,
                        'status' => $internalStatus,
                        'uuid' => $msg['user_sms_id'],
                        'metadata' => json_encode($metadata),
                        'external_id' => $requestId,
                        'sent_at' => now(),
                    ]);
                    Log::info("[Eskiz] SMS batch item for {$msg['to']} stored with status $internalStatus.");
                }

                return true;
            } else {
                Log::error('[Eskiz] Failed to send batch SMS. Response: '.$response->body());

                return false;
            }
        } catch (\Exception $e) {
            Log::error('[Eskiz] Batch send failed. Exception: '.$e->getMessage());

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
