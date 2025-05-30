<?php

namespace App\Contracts;

interface MassDispatchTimeValidatorInterface
{
    public function isWithinAllowedTime(): bool;

    public function nextAllowedTimestamp(): int;
}
