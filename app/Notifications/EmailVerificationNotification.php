<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $verificationCode;
    protected string $verificationUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $verificationCode, string $verificationUrl)
    {
        $this->verificationCode = $verificationCode;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Thank you for registering with ' . config('app.name') . '. Please verify your email address to complete your registration.')
            ->line('**Option 1: Use Verification Code**')
            ->line('Enter this 6-digit code in the app:')
            ->line('**' . $this->verificationCode . '**')
            ->line('**Option 2: Click Verification Link**')
            ->action('Verify Email Address', $this->verificationUrl)
            ->line('This verification code and link will expire in 1 hour.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode,
            'verification_url' => $this->verificationUrl,
        ];
    }
}