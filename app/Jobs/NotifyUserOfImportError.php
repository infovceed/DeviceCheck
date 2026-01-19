<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Notifications\DashboardNotification;

class NotifyUserOfImportError implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private User $user,
        private string $title = 'Error en importación',
        private string $message = ''
    ) {}

    public function handle(): void
    {
        $this->user->notify(new DashboardNotification(__($this->title), __($this->message)));
    }
}
