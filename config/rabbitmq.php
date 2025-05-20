<?php

return [
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USERNAME', 'admin'),
    'password' => env('RABBITMQ_PASSWORD', 'admin'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
];
