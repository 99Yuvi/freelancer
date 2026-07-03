<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PayoutProcessed extends Notification
{
    public function __construct(
        private readonly string $amount,
        private readonly string $status
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payment',
            'title'   => 'Payout Update',
            'message' => "Your payout of ₹{$this->amount} was {$this->status}",
        ];
    }
}
