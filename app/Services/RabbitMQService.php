<?php

namespace App\Services;

use AMQPConnection;
use App\Contracts\MessageConsumerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQService implements MessageConsumerInterface
{
    private $connection;
    private $channel;

    private function consume(string $routingKey, callable $callback): void {
        $this->channel->exchange_declare('sms_exchange', 'topic', false, true, false);
        [$queueName,,] = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->queue_bind($queueName, 'sms_exchange', $routingKey);

        $this->channel->basic_consume($queueName, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __construct()
    {
        $this->connect();
    }

    public static function getInstanse(): self
    {
        return new self();
    }

    public function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );
        $this->channel = $this->connection->channel();
    }


    public function consumeHighPriority(callable $callback): void
    {
        $this->consume('sms.priority.high', $callback);
    }

    public function consumeMediumPriority(callable $callback): void
    {
        $this->consume('sms.priority.medium', $callback);
    }

    public function consumeLowPriority(callable $callback): void
    {
        $this->consume('sms.priority.low', $callback);
    }

    public function close(): void
    {
        $this->connection->close();
        $this->channel->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}
