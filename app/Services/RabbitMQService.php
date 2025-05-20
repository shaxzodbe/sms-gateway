<?php

namespace App\Services;

use App\Contracts\MessageConsumerInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQService implements MessageConsumerInterface
{
    private static ?self $instance = null;

    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    private string $exchangeName = 'sms.exchange';

    private array $queues = [
        'high' => 'sms.priority.high',
        'medium' => 'sms.priority.medium',
        'low' => 'sms.priority.low',
    ];

    private array $bindings = [
        'high' => 'sms.high.*',
        'medium' => 'sms.medium.*',
        'low' => 'sms.low.*',
    ];

    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost')
            );

            $this->channel = $this->connection->channel();

            $this->channel->exchange_declare(
                $this->exchangeName,
                'topic',
                false,
                true,
                false
            );

            foreach ($this->queues as $priority => $queue) {
                $this->channel->queue_declare(
                    $queue,
                    false,
                    true,
                    false,
                    false,
                    false,
                    new AMQPTable(['x-max-priority' => 10])
                );

                $this->channel->queue_bind(
                    $queue,
                    $this->exchangeName,
                    $this->bindings[$priority]
                );
            }

        } catch (Exception $e) {
            Log::error('RabbitMQ connection failed: '.$e->getMessage());
        }
    }

    public function publish(string $routingKey, array $payload, int $priority = 5): void
    {
        if (! $this->channel) {
            Log::error('Cannot publish: RabbitMQ channel is not available.');

            return;
        }

        try {
            $message = new AMQPMessage(
                json_encode($payload),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'priority' => $priority,
                ]
            );

            $this->channel->basic_publish(
                $message,
                $this->exchangeName,
                $routingKey
            );

            Log::info("Published message to [$routingKey] with priority [$priority]");

        } catch (Exception $e) {
            Log::error("Failed to publish message to [$routingKey]: ".$e->getMessage());
        }
    }

    public function consume(string $queue, callable $callback): void
    {
        if (! $this->channel) {
            Log::error('RabbitMQ channel is not available');

            return;
        }

        try {
            $this->channel->basic_qos(0, 1, false);
            $this->channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                function ($message) use ($callback, $queue) {
                    try {
                        $payload = json_decode($message->body, true);
                        $callback($payload, $queue);
                        $message->ack();
                    } catch (Exception $e) {
                        Log::error("Failed to process message from queue [$queue]: ".$e->getMessage());
                        $message->nack();
                    }
                }
            );

            while ($this->channel->is_consuming()) {
                $this->channel->wait();
            }

        } catch (Exception $e) {
            Log::error("Failed to consume messages from queue [$queue]: ".$e->getMessage());
        }
    }

    public function __construct()
    {
        $this->connect();
    }

    public function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function consumeHighPriority(callable $callback): void
    {
        $this->consume($this->queues['high'], $callback);
    }

    public function consumeMediumPriority(callable $callback): void
    {
        $this->consume($this->queues['medium'], $callback);
    }

    public function consumeLowPriority(callable $callback): void
    {
        $this->consume($this->queues['low'], $callback);
    }

    public function close(): void
    {
        $this->channel?->close();
        $this->connection?->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}
