<?php

namespace App\Services;

use App\Contracts\PhoneValidatorInterface;
use Illuminate\Support\Facades\RateLimiter;

class PhoneValidatorService implements PhoneValidatorInterface
{
    public function isValidPhone(string $phone): bool
    {
        return preg_match('/^998\d{9}$/', $phone);
    }
}
