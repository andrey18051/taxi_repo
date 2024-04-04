<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerInfoEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $template; // Добавляем свойство для хранения имени собственного шаблона

    /**
     * Create a new message instance.
     *
     * @param string $template Имя собственного шаблона
     * @param array $params Параметры для передачи в шаблон
     * @return void
     */
    public function __construct($template, $params)
    {
        $this->template = $template;
        $this->params = $params;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Используем метод view() для указания собственного шаблона
        return $this->view("emails.$this->template")->with($this->params)
            ->subject($this->params["subject"]);
    }
}
