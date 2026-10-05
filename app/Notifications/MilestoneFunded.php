<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MilestoneFunded extends Notification
{
    public function __construct(private readonly string $milestoneTitle) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payment',
            'title'   => 'Payment Secured',
            'message' => "The client has paid for \"{$this->milestoneTitle}\". You can start work now.",
        ];
    }
}
