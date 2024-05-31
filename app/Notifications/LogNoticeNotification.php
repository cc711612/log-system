<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class LogNoticeNotification extends Notification
{
    use Queueable;

    public string $message;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $result = [];
        if ($notifiable->email) {
            array_push($result, 'mail');
        }
        if ($notifiable->slack_webhook_url) {
            array_push($result, 'slack');
        }

        return $result;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toSlack($notifiable)
    {
        $lines = [
            'Test System',
            $this->message,
        ];

        return (new SlackMessage)
            ->content(implode("\r\n", $lines))
            ->to($notifiable->slack_webhook_url);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('系統通知信')
            ->greeting('Hello!')
            ->line('This is the notification message: ' . $this->message)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
