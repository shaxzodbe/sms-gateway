<?php

namespace App\Console\Commands;

use App\Services\DispatcherService;
use Illuminate\Console\Command;

class MediumPriorityConsumer extends Command
{
    protected $signature = 'sms:consume-medium';
    protected $description = 'Consume medium-priority SMS queue';

    public function handle(): void
    {
        $dispatcher = app(DispatcherService::class);
        $dispatcher->consumeMediumPriority();
    }
}
