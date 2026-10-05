<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ProposalShortlisted extends Notification
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
            'type'    => 'proposal',
            'title'   => 'Proposal Shortlisted',
            'message' => "Your proposal for \"{$this->projectTitle}\" was shortlisted",
        ];
    }
}
