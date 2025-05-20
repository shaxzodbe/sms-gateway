<?php

namespace App\Console\Commands;

use App\Services\DispatcherService;
use Illuminate\Console\Command;

class LowPriorityConsumer extends Command
{
    protected $signature = 'sms:consume-low';
    protected $description = 'Consume low-priority SMS queue';

    public function handle(): void
    {
        $dispatcher = app(DispatcherService::class);
        $dispatcher->consumeLowPriority();
    }
}
