<?php

namespace App\Services;

use App\Contracts\MassDispatchTimeValidatorInterface;
use App\Models\MassDispatchConstraint;
use Carbon\Carbon;

class MassDispatchTimeValidator implements MassDispatchTimeValidatorInterface
{
    public function __construct(
        protected MassDispatchConstraint $constraint
    ) {}

    public function getStartTime(): Carbon
    {
        return Carbon::today()->copy()->setTimeFromTimeString($this->constraint->start_time);
    }

    public function getEndTime(): Carbon
    {
        return Carbon::today()->copy()->setTimeFromTimeString($this->constraint->end_time);
    }

    public function nextStartTime(): Carbon
    {
        $now = Carbon::now();
        $start = $this->getStartTime();

        return $now->gt($this->getEndTime())
            ? $start->addDay()
            : $start;
    }
}
