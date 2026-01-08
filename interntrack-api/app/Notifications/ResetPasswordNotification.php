<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $resetUrl = $baseUrl
            . '/reset-password/' . $this->token
            . '?email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Your InternTrack Password')
            ->greeting('Hello ' . $notifiable->fname . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('Your reset token is: **' . $this->token . '**')
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
