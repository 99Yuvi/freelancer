<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewVerificationRequest extends Notification
{
    public function __construct(
        private readonly string $freelancerName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'verification',
            'title'   => 'New verification request',
            'message' => "{$this->freelancerName} submitted verification documents for review.",
        ];
    }
}
