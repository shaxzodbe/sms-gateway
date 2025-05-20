```
    RabbitMQService::getInstance()->publish(
        routingKey: 'sms.high.otp',
        payload: [
            'phone' => '998949289094',
            'message' => 'Test high priority message',
            'metadata' => [
                'uuid' => Str::uuid()->toString(),
                'type' => 'otp',
                'source' => 'broker',
                'expire_time' => now()->addMinutes(2)->timestamp,
            ],
        ],
        priority: 10
    );
```

