<?php

namespace App\Enums;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETRY = 'retry';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
