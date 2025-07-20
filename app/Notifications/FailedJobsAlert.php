<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FailedJobsAlert extends Notification
{
    use Queueable;

    protected $failedJobsCount;

    public function __construct($failedJobsCount)
    {
        $this->failedJobsCount = $failedJobsCount;
    }

    public function via($notifiable)
    {
        return ['mail']; // Используем email вместо Slack
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Провалившиеся задачи в очереди')
            ->line("Обнаружено {$this->failedJobsCount} провалившихся задач в очереди!")
            ->action('Проверить Horizon', url('/horizon'));
    }
}
