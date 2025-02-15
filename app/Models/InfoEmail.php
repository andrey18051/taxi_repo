<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InfoEmail extends Mailable
{
    use Queueable, SerializesModels;

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
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';

        $codeNew = substr(str_shuffle($permitted_chars), 0, 4);
        $currentTimestamp = time();
        $subject = "Повідомлення TEUA-$codeNew-$currentTimestamp";
        return $this->markdown('emails.info')->with($this->params)
            ->subject($subject);
    }
}
