<?php

namespace App\Services;

use App\Contracts\MessageConsumerInterface;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Log;

class DispatcherService
{
    public function __construct(
        protected MessageConsumerInterface $consumer,
        protected SmsService $smsService
    ) {}

    protected function handleSingle(array $msg): void
    {
        $phone = $msg['phone'] ?? null;
        $message = $msg['message'] ?? '';
        $metadata = $msg['metadata'] ?? [];
        $priority = $msg['priority'] ?? 'low';
        $attempts = $metadata['retry_attempts'] ?? 0;

        if ($phone && $message) {
            $success = $this->smsService->send($phone, $message, $metadata);
            if (! $success) {
                $this->retrySingle($phone, $message, $metadata, $priority, $attempts + 1);
            }
        } else {
            Log::warning('Invalid payload: '.json_encode($msg));
        }
    }

    protected function handleBatch(array $msg): void
    {
        $messages = $msg['messages'] ?? [];
        $metadata = $msg['metadata'] ?? [];
        $priority = $metadata['priority'] ?? 'low';
        $attempts = $metadata['retry_attempts'] ?? 0;

        if (empty($messages) || ! is_array($messages)) {
            Log::warning('Invalid batch SMS payload: '.json_encode($msg));

            return;
        }

        // messages = [['phone' => '998900000000', 'message' => 'text'],['phone' => '998900000000', 'message' => 'text']]
        $success = $this->smsService->sendBatch($messages, $metadata);

        if (! $success) {
            $this->retryBatch($messages, $metadata, $priority, $attempts + 1);
        }
    }

    protected function retrySingle(string $phone, string $message, array $metadata, string $priority, int $attempts): void
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

    protected function retryBatch(array $messages, array $metadata, string $priority, int $attempts): void
    {
        $delay = config('rabbitmq.delay');
        $metadata['retry_attempts'] = $attempts;
        $metadata['timestamp'] = time();

        RabbitMQService::getInstance()->publishWithDelay(
            [
                'messages' => $messages,
                'metadata' => $metadata,
            ],
            $priority === 'high' ? 10 : ($priority === 'medium' ? 5 : 1),
            $delay
        );

        Log::info("Retrying batch SMS with delay {$delay}ms (attempt $attempts)");
    }

    public function consume(): void
    {
        $this->consumer->consume(function ($msg) {
            if (isset($msg['messages'])) {
                $this->handleBatch($msg);
            } elseif (isset($msg['phone']) && isset($msg['message'])) {
                $this->handleSingle($msg);
            } else {
                Log::warning('Unknown message format: '.json_encode($msg));
            }
        });
    }
}
