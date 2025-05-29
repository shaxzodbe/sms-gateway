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

    private string $queue;

    private int $maxPriority;

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

            $this->queue = config('rabbitmq.queues.main');
            $this->maxPriority = config('rabbitmq.max_priority');

            $this->channel->queue_declare(
                $this->queue,
                false,
                true,
                false,
                false,
                false,
                new AMQPTable(['x-max-priority' => $this->maxPriority])
            );

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
                    'headers' => new AMQPTable([
                        'x-delay' => 0,
                    ]),
                ]
            );

            $this->channel->basic_publish(
                $message,
                '',
                $this->queue,
            );

            Log::info("Published message to [$routingKey] with priority [$priority]");

        } catch (Exception $e) {
            Log::error("Failed to publish message to [$routingKey]: ".$e->getMessage());
        }
    }

    public function consume(callable $callback): void
    {
        if (! $this->channel) {
            Log::error('RabbitMQ channel is not available');

            return;
        }

        try {
            $this->channel->basic_qos(0, 1, false);
            $this->channel->basic_consume(
                $this->queue,
                '',
                false,
                false,
                false,
                false,
                function ($message) use ($callback) {
                    try {
                        $payload = json_decode($message->body, true);
                        $callback($payload, $this->queue);
                        $message->ack();
                    } catch (Exception $e) {
                        Log::error("Failed to process message from queue [$this->queue]: ".$e->getMessage());
                        $message->nack();
                    }
                }
            );

            while ($this->channel->is_consuming()) {
                $this->channel->wait();
            }

        } catch (Exception $e) {
            Log::error("Failed to consume messages from queue [$this->queue]: ".$e->getMessage());
        }
    }

    public function __construct()
    {
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function publishWithDelay(array $payload, int $priority = 5, int $delay = 0): void
    {
        if (! $this->channel) {
            Log::error('Cannot publish with delay: RabbitMQ channel is not available.');

            return;
        }

        try {
            $message = new AMQPMessage(
                json_encode($payload),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'priority' => $priority,
                    'headers' => new AMQPTable([
                        'x-delay' => $delay,
                    ]),
                ]
            );

            $this->channel->basic_publish(
                $message,
                '',
                $this->queue,
            );

            Log::info("Published delayed message to queue [$this->queue] with delay [$delay] ms");

        } catch (Exception $e) {
            Log::error("Failed to publish delayed message to [$this->queue]: ".$e->getMessage());
        }
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
