<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PInfoEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $template; // Добавляем свойство для хранения имени собственного шаблона

//    /**
//     * Create a new message instance.
//     *
//     * @param string $template Имя собственного шаблона
//     * @param array $params Параметры для передачи в шаблон
//     * @return void
//     */
//    public function __construct($template, $params)
//    {
//        $this->template = $template;
//        $this->params = $params;
//    }
    public $subject;
    public $message;
    public $url;
    public $text_button;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $message
     * @param string $url
     * @param string $text_button
     * @return void
     */
    public function __construct($template, string $subject, string $message, string $url, string $text_button)
    {
        $this->template = $template;
        $this->subject = $subject;
        $this->message = $message;
        $this->url = $url;
        $this->text_button = $text_button;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): PInfoEmail
    {
        // Используем метод view() для указания собственного шаблона
        return $this->view("emails.$this->template")
            ->with([
                'subject' => $this->subject,
                'mes' => $this->message,
                'url' => $this->url,
                'text_button' => $this->text_button
            ]);
    }
}
