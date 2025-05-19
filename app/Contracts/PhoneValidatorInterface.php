<?php

namespace App\Contracts;

interface PhoneValidatorInterface
{
    public function isValidPhone(string $phone): bool;
}
