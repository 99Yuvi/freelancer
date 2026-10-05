<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ContractCreated extends Notification
{
    public function __construct(
        private readonly string $projectTitle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'contract',
            'title'   => "You've been hired!",
            'message' => "You've been hired for \"{$this->projectTitle}\"! Contract is now active.",
        ];
    }
}
