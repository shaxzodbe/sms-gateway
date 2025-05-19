<?php

namespace App\Services;

use App\Contracts\MassDispatchTimeValidatorInterface;
use App\Contracts\MessageConsumerInterface;
use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DispatcherService
{
    public function __construct(
        protected MessageConsumerInterface $consumer,
        protected SmsService $smsService,
        protected MassDispatchTimeValidatorInterface $validator
    ) {}

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

        $startTime = $this->validator->getStartTime();
        $endTime = $this->validator->getEndTime();
        $currentTime = now();

        $deferredUntil = isset($metadata['deferred_until'])
            ? Carbon::createFromTimestamp($metadata['deferred_until'])
            : null;

        if ($deferredUntil && $currentTime->lt($deferredUntil)) {
            Log::info("Batch already deferred until {$deferredUntil->toDateTimeString()}, skipping re-queue.");

            return;
        }

        if ($currentTime->lt($startTime)) {
            $delaySeconds = $startTime->diffInSeconds($startTime, false);

            RabbitMQService::getInstance()->publishWithDelay(
                [
                    'messages' => $messages,
                    'metadata' => array_merge($metadata, [
                        'retry_attempts' => $attempts + 1,
                        'timestamp' => time(),
                        'deferred_until' => $startTime->timestamp,
                    ]),
                ],
                $priority === 'high' ? 10 : ($priority === 'medium' ? 5 : 1),
                $delaySeconds * 1000
            );
            Log::info("Batch SMS deferred for {$delaySeconds} seconds due to time constraints, will be sent at {$startTime->format('H:i')}.");

            return;
        }
        if ($currentTime->gt($endTime)) {
            $nextStartTime = $this->validator->nextStartTime();
            $delaySeconds = $currentTime->diffInSeconds($nextStartTime, false);

            RabbitMQService::getInstance()->publishWithDelay(
                [
                    'messages' => $messages,
                    'metadata' => array_merge($metadata, [
                        'retry_attempts' => $attempts + 1,
                        'timestamp' => time(),
                        'deferred_until' => $nextStartTime->timestamp,
                    ]),
                ],
                $priority === 'high' ? 10 : ($priority === 'medium' ? 5 : 1),
                $delaySeconds * 1000
            );
            Log::info("Batch SMS deferred for {$delaySeconds} seconds as current time exceeds allowed window. It will be sent at {$nextStartTime->format('H:i')} tomorrow.");

            return;
        }

        // messages = [['phone' => '998900000000', 'message' => 'text'],['phone' => '998900000000', 'message' => 'text']]
        $success = $this->smsService->sendBatch($messages, $metadata);

        if (! $success) {
            $this->retryBatch($messages, $metadata, $priority, $attempts + 1);
        }
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
