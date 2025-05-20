<?php

return [
    'rate_limiter' => [
        'limit' => env('SMS_LIMIT_RATE', 1),
        'window' => env('SMS_LIMIT_WINDOW_SECONDS', 60),
    ],
    'circuit_breaker' => [
        'failure_threshold' => env('CB_FAILURE_THRESHOLD', 5),
        'cooldown_seconds' => env('CB_COOLDOWN_SECONDS', 300),
    ],
    'providers' => [
        'Eskiz' => \App\Services\Sms\Providers\EskizProvider::class,
        'Notify' => \App\Services\Sms\Providers\NotifyProvider::class,
        'Getsms' => \App\Services\Sms\Providers\GetsmsProvider::class,
        'Playmobile' => \App\Services\Sms\Providers\PlaymobileProvider::class,
    ],
];
