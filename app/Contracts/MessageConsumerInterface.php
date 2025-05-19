<?php

namespace App\Contracts;

interface MessageConsumerInterface
{
    public function consumeHighPriority(callable $callback): void;

    public function consumeMediumPriority(callable $callback): void;

    public function consumeLowPriority(callable $callback): void;
}
