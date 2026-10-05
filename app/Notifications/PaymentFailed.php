<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
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
            'type'    => 'payment',
            'title'   => 'Payment Failed',
            'message' => "Payment failed for milestone \"{$this->milestoneTitle}\" — please try again",
        ];
    }
}
