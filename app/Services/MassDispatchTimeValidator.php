<?php

namespace App\Services;

use App\Contracts\MassDispatchTimeValidatorInterface;
use App\Models\MassDispatchConstraint;
use Carbon\Carbon;

class MassDispatchTimeValidator implements MassDispatchTimeValidatorInterface
{
    public function __construct(protected MassDispatchConstraint $constraint) {}

    public function isWithinAllowedTime(): bool
    {
        $now = Carbon::now();
        $start = Carbon::createFromTimeString($this->constraint->start_time);
        $end = Carbon::createFromTimeString($this->constraint->end_time);

        return $now->between($start, $end);
    }

    public function nextAllowedTimestamp(): int
    {
        $nextStart = Carbon::today()->setTimeFromTimeString($this->constraint->start_time);
        if (Carbon::now()->gt($nextStart)) {
            $nextStart->addDay();
        }

        return $nextStart->timestamp;
    }
}
