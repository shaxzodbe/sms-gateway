<?php

namespace App\Contracts;

use Carbon\Carbon;

interface MassDispatchTimeValidatorInterface
{
    public function getStartTime(): Carbon;

    public function getEndTime(): Carbon;
}
