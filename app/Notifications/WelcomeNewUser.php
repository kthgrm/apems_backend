<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class WelcomeNewUser extends Notification
{
    use Queueable;

    private string $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $temporaryPassword)
    {
        $this->temporaryPassword = $temporaryPassword;
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
        // Generate password reset token
        $token = Password::createToken($notifiable);
        $resetUrl = config('app.frontend_url') . "/reset-password?token={$token}&email=" . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Welcome to APEMS - Account Created')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your account has been created in the Awards Projects and Engagements Management System (APEMS).')
            ->line('Your temporary login credentials are:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Temporary Password:** {$this->temporaryPassword}")
            ->line('For security reasons, you must change your password upon first login.')
            ->action('Set Your Password', $resetUrl)
            ->line('If you have any questions, please contact your system administrator.')
            ->line('Thank you for joining APEMS!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Welcome email sent to new user',
            'user_email' => $notifiable->email,
        ];
    }
}
