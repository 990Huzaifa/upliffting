<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Announcement extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;
    public $priority;
    public $image;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $url = null, $priority, $image = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->priority = $priority;
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title'      => $this->title,
            'message'   => $this->message,
            'attachment'  => $this->url,
            'priority'   => $this->priority,
            'image'      => $this->image
        ]);
    }
}
