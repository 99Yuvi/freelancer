<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(
        private readonly string $netAmount,
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
            'title'   => 'Payment Received',
            'message' => "Payment of ₹{$this->netAmount} received for \"{$this->milestoneTitle}\"",
        ];
    }
}
