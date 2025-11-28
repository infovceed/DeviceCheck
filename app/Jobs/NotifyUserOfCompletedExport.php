<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\DashboardNotification;

class NotifyUserOfCompletedExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private User $user,
        private string $downloadUrl,
        private string $fileName
    ){}

    public function handle(): void
    {
        $title = __('Your export is ready');
        $message = __("Download report: :url", ['url' => "<a href='{$this->downloadUrl}' target='_blank'>{$this->fileName}</a>"]);
        $this->user->notify(new DashboardNotification($title, $message, $this->downloadUrl));
    }
}
