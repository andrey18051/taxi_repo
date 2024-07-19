<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserSleepEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $message;
    public $app_url;
    public $url;
    public $text_button;
    public $uniqueNumber;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $message
     * @param string $url
     * @param string $text_button
     * @return void
     */
    public function __construct(string $subject, string $message, string $app_url, string $url, string $text_button, string $uniqueNumber)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->app_url = $app_url;
        $this->url = $url;
        $this->text_button = $text_button;
        $this->uniqueNumber = $uniqueNumber;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): UserSleepEmail
    {
        // Используем метод view() для указания собственного шаблона
        return $this->view("emails.sleep_users_email")
            ->with([
                'subject' => $this->subject,
                'mes' => $this->message,
                'app_url' => $this->app_url,
                'url' => $this->url,
                'text_button' => $this->text_button,
                'uniqueNumber' => $this->uniqueNumber
            ]);
    }
}
