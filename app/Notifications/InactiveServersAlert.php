<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InactiveServersAlert extends Notification
{
    use Queueable;

    /** @var array */
    protected $servers;

    /**
     * @param array $servers
     */
    public function __construct($servers)
    {
        // Можно оставить type-hint в сигнатуре: __construct(array $servers)
        // В PHP 7.3 это допустимо. Если хотите максимально мягко — как здесь, без type-hint.
        $this->servers = (array) $servers;
    }

    /**
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('🚨 Неактивные сервера обнаружены')
            ->greeting('Здравствуйте!')
            ->line('Обнаружены следующие неработающие сервера:');

        foreach ($this->servers as $s) {
            $mail->line('• ' . $s);
        }

        return $mail->line('Проверьте их доступность и перезапустите при необходимости.');
    }
}
