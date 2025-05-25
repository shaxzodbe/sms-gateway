<?php

namespace App\Services\Sms\Providers;

use App\Contracts\ProviderInterface;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function sendBatch(array $messages, array $metadata = []): bool
    {
        DB::beginTransaction();

        try {
            $preparedMessages = [];
            $messageMap = [];

            foreach ($messages as $msg) {
                $messageModel = Message::create([
                    'phone' => $msg['phone'],
                    'text' => $msg['message'],
                    'provider_id' => $this->provider->id,
                    'batch_id' => $metadata['batch_id'] ?? null,
                    'status' => MessageStatus::PENDING->value,
                    'metadata' => json_encode($metadata),
                ]);

                $userSmsId = 'm'.(string) $messageModel->id;
                $preparedMessages[] = [
                    'user_sms_id' => $userSmsId,
                    'to' => $msg['phone'],
                    'text' => $msg['message'],
                ];
                $messageMap[$userSmsId] = $messageModel;
            }

            $payload = [
                'messages' => $preparedMessages,
                'from' => '4546',
                'dispatch_id' => $metadata['dispatch_id'] ?? time(),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->provider->token,
                'Content-Type' => 'application/json',
            ])->post($this->provider->endpoint.'/send-batch', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Eskiz sendBatch success: ', $responseData);

                $statuses = $responseData['status'] ?? [];
                $requestId = $responseData['id'] ?? null;

                foreach ($preparedMessages as $index => $msgPayload) {
                    $userSmsId = $msgPayload['user_sms_id'];
                    $message = $messageMap[$userSmsId] ?? null;

                    if (! $message) {
                        continue;
                    }

                    $providerStatus = $statuses[$index] ?? 'unknown';

                    $internalStatus = match ($providerStatus) {
                        'waiting' => MessageStatus::SENT->value,
                        default => MessageStatus::FAILED->value,
                    };

                    $message->update([
                        'status' => $internalStatus,
                        'request_id' => $requestId,
                        'sent_at' => now(),
                    ]);
                }

                DB::commit();

                return true;
            }

            DB::rollBack();
            Log::error('Eskiz sendBatch failed: '.$response->body());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Eskiz sendBatch exception: '.$e->getMessage());
        }

        return false;
    }

    public function getProviderModel(): Provider
    {
        return Provider::query()
            ->where('name', 'Eskiz')
            ->where('is_active', 1)
            ->first();
    }

    public function getBatchSizeLimit(): ?int
    {
        return 200;
    }
}
