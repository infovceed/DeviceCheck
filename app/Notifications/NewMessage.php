<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\Device;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Orchid\Platform\Notifications\DashboardChannel;
use Orchid\Platform\Notifications\DashboardMessage;

class NewMessage extends Notification
{
    use Queueable;
    private $device;

    /**
     * Create a new notification instance.
     */
    public function __construct( Device $device)
    {

        $this->device = $device;
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
     * Get the mail representation of the notification.
     */
    public function toDashboard(object $notifiable): DashboardMessage
    {
           return (new DashboardMessage)
                    ->title(__('New message in device').': '.$this->device->divipole->code)
                    ->message(__('You have a new message in the device. Click to view the message.'))
                    ->action(route('platform.systems.incidents', ['device' => $this->device->id]));
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
