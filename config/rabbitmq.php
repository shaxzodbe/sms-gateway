<?php

return [
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USERNAME', 'sms'),
    'password' => env('RABBITMQ_PASSWORD', 'sms123'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'queues' => [
        'main' => 'sms.priority.all',
    ],

    'max_priority' => 10,
    'delay' => 300, // ms
];
