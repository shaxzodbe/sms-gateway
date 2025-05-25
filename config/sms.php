<?php

use App\Services\Sms\Providers\EskizProvider;
use App\Services\Sms\Providers\GetsmsProvider;
use App\Services\Sms\Providers\NotifyProvider;
use App\Services\Sms\Providers\PlaymobileProvider;

return [
    'rate_limiter' => [
        'Eskiz' => [
            'limit' => 5,
            'window' => 1,
        ],
        'Notify' => [
            'limit' => 5,
            'window' => 1,
        ],
        'Getsms' => [
            'limit' => 5,
            'window' => 1,
        ],
        'Playmobile' => [
            'limit' => 5,
            'window' => 1,
        ],
    ],

    'circuit_breaker' => [
        'failure_threshold' => env('CB_FAILURE_THRESHOLD', 20),
        'cooldown_seconds' => env('CB_COOLDOWN_SECONDS', 1),
    ],

    'providers' => [
        'Eskiz' => EskizProvider::class,
        'Notify' => NotifyProvider::class,
        'Getsms' => GetsmsProvider::class,
        'Playmobile' => PlaymobileProvider::class,
    ],
];
