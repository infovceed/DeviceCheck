<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Orchid\Platform\Notifications\DashboardChannel;
use Orchid\Platform\Notifications\DashboardMessage;

class DashboardNotification extends Notification
{
    use Queueable;
    private $title;
    private $message;
    private $route;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $route = '')
    {
        $this->title = $title;
        $this->message = $message;
        $this->route = $route;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [DashboardChannel::class];
    }
    /**
     * Get the dashboard representation of the notification.
     */
    public function toDashboard($notifiable): DashboardMessage
    {
        $notification = (new DashboardMessage())
            ->title($this->title)
            ->message($this->message);

        if (!empty($this->route)) {
            $notification->action($this->route);
        }

        return $notification;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
