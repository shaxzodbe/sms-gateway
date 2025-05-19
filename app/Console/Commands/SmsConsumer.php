<?php

namespace App\Console\Commands;

use App\Services\DispatcherService;
use Illuminate\Console\Command;

class SmsConsumer extends Command
{
    protected $signature = 'sms:consume';
    protected $description = 'Consume SMS queue';

    public function handle(): void
    {
        $dispatcher = app(DispatcherService::class);
        $dispatcher->consume();
    }
}
