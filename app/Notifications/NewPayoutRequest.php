<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewPayoutRequest extends Notification
{
    public function __construct(
        private readonly string $freelancerName,
        private readonly string $amount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payout',
            'title'   => 'New payout request',
            'message' => "{$this->freelancerName} requested a payout of ₹{$this->amount}.",
        ];
    }
}
