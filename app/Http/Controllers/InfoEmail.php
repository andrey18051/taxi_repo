<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InfoEmail extends Mailable
{
    use Queueable, SerializesModels;

//    public $subject = 'Ваш заголовок по умолчанию';
//
//    public $introLines = [
//        'Ваше приветствие здесь',
//    ];
//
//    public $outroLines = [
//        'Ваша подпись здесь',
//    ];
//
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->markdown('emails.info')->with($this->params)
            ->subject($this->params["subject"]);
//            ->introLines($this->introLines)
//            ->outroLines($this->outroLines);;
    }
}
