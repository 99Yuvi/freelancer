<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewProposalReceived extends Notification
{
    public function __construct(
        private readonly string $freelancerName,
        private readonly string $projectTitle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'proposal',
            'title'   => 'New Proposal',
            'message' => "New proposal from {$this->freelancerName} on \"{$this->projectTitle}\"",
        ];
    }
}
