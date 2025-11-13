<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\DashboardNotification;

class NotifyUserOfCompletedImport implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private User $user,
        private string $title='Import completed',
        private string $message='The import has been completed successfully.'
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new DashboardNotification(__($this->title), __($this->message)));
    }
}
