<?php

namespace App\Contracts;

interface MessageConsumerInterface
{
    public function consume(callable $callback): void;
}
