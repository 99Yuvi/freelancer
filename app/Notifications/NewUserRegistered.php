<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    public function __construct(
        private readonly string $userName,
        private readonly string $role
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'user',
            'title'   => 'New user registered',
            'message' => "{$this->userName} joined as a {$this->role}.",
        ];
    }
}
