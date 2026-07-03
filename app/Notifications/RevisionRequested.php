<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RevisionRequested extends Notification
{
    public function __construct(
        private readonly string $milestoneTitle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'contract',
            'title'   => 'Revision Requested',
            'message' => "Revision requested for milestone \"{$this->milestoneTitle}\"",
        ];
    }
}
