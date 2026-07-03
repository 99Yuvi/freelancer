<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PaymentFailedAdmin extends Notification
{
    public function __construct(
        private readonly string $clientName,
        private readonly string $milestoneTitle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payment_failed',
            'title'   => 'Payment failed',
            'message' => "A payment by {$this->clientName} failed for milestone \"{$this->milestoneTitle}\".",
        ];
    }
}
