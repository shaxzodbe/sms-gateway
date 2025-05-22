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

    protected function handle(array $msg): void
    {
        $phone = $msg['phone'] ?? null;
        $message = $msg['message'] ?? '';
        $metadata = $msg['metadata'] ?? [];
        $priority = $msg['priority'] ?? 'low';
        $attempts = $metadata['retry_attempts'] ?? 0;

        if ($phone && $message) {
            $success = $this->smsService->send($phone, $message, $metadata);
            if (! $success) {
                $this->retry($phone, $message, $metadata, $priority, $attempts + 1);
            }
        } else {
            Log::warning('Invalid payload: '.json_encode($msg));
        }
    }

    protected function retry(string $phone, string $message, array $metadata, string $priority, int $attempts): void
    {
        $delay = config('rabbitmq.delay');
        $metadata['retry_attempts'] = $attempts;
        $metadata['timestamp'] = time();

        RabbitMQService::getInstance()->publishWithDelay(
            [
                'phone' => $phone,
                'message' => $message,
                'metadata' => $metadata,
            ],
            $priority === 'high' ? 10 : ($priority === 'medium' ? 5 : 1),
            $delay
        );

        Log::info("Retrying message to $phone with delay {$delay}ms (attempt $attempts)");
    }

    public function consume(): void
    {
        $this->consumer->consume(fn ($msg) => $this->handle($msg));
    }
}
