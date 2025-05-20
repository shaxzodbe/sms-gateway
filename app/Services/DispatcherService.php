<?php

namespace App\Services;

use App\Contracts\MessageConsumerInterface;
use Illuminate\Support\Facades\Log;

class DispatcherService
{
    public function __construct(
        protected MessageConsumerInterface $consumer,
        protected SmsService $smsService
    ) {}

    public function consumeHighPriority(): void
    {
        $this->consumer->consumeHighPriority(fn ($msg) => $this->handle($msg));
    }

    public function consumeMediumPriority(): void
    {
        $this->consumer->consumeMediumPriority(fn ($msg) => $this->handle($msg));
    }

    public function consumeLowPriority(): void
    {
        $this->consumer->consumeLowPriority(fn ($msg) => $this->handle($msg));
    }

    protected function handle($msg): void
    {
        $payload = json_decode($msg->body, true);

        $phone = $payload['phone'] ?? null;
        $message = $payload['message'] ?? '';
        $metadata = $payload['metadata'] ?? [];

        if ($phone && $message) {
            $this->smsService->send($phone, $message, $metadata);
        } else {
            Log::warning('Invalid payload: '.json_encode($payload));
        }
    }
}
