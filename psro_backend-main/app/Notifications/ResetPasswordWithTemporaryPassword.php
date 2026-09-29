<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordWithTemporaryPassword extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     * Sends a temporary password (so user can log in immediately) and the reset link.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $tempPassword = Str::password(12);
        $notifiable->forceFill([
            'password' => Hash::make($tempPassword),
        ])->save();

        $resetUrl = $this->resetUrl($notifiable);
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(__('Reset Password Notification'))
            ->line(__('You are receiving this email because we received a password reset request for your account.'))
            ->line(__('Your temporary password (use it to log in now):'))
            ->line('**' . $tempPassword . '**')
            ->line(__('We recommend you change this password after logging in using the link below.'))
            ->action(__('Set a new password'), $resetUrl)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expireMinutes]))
            ->line(__('If you did not request a password reset, please change your password immediately or contact support.'));
    }

    /**
     * Get the reset URL for the given notifiable (frontend URL).
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        $email = $notifiable->getEmailForPasswordReset();

        return rtrim(config('app.frontend_url'), '/')
            . '/reset-password/' . $this->token
            . '?email=' . urlencode($email);
    }
}
