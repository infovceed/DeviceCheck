<?php

namespace App\Jobs;

use App\Services\WebSocketNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyWebSocketClients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int,string> */
    public array $paths;

    public function __construct(array $paths = ['/ws/stats'])
    {
        $this->paths = $paths;
    }

    public function handle(WebSocketNotifier $notifier): void
    {
        $notifier->notify($this->paths);
    }
}
