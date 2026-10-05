<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MilestoneDelivered extends Notification
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
            'title'   => 'Work Submitted',
            'message' => "Work submitted for milestone \"{$this->milestoneTitle}\" — please review",
        ];
    }
}
