<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class VerificationUpdated extends Notification
{
    public function __construct(
        private readonly string $status
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'contract',
            'title'   => 'Verification Update',
            'message' => "Your ID verification was {$this->status}",
        ];
    }
}
