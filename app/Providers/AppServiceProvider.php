<?php

namespace App\Providers;

use App\Events\ContractCreated;
use App\Listeners\CreateConversationOnContractCreated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\ContractService::class, function ($app) {
            return new \App\Services\ContractService($app->make(\App\Services\ProposalService::class));
        });
    }

    public function boot(): void
    {
        Event::listen(ContractCreated::class, CreateConversationOnContractCreated::class);

        // Point password reset links to the React frontend instead of a Laravel web route
        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token) {
            $email = urlencode($notifiable->getEmailForPasswordReset());
            return config('app.frontend_url') . "/auth/reset-password?token={$token}&email={$email}";
        });

        // Point email verification links to the API (no auth required — fixed separately)
        VerifyEmail::createUrlUsing(function (mixed $notifiable) {
            $id   = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());
            $url  = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $id, 'hash' => $hash]
            );
            return $url;
        });
    }
}
