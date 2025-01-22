<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CheckVod extends Mailable
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
        $currentDate = Carbon::now()->format('d-m-Y'); // Форматирование текущей даты

        return $this->markdown('emails.check_vod')
            ->with($this->params)
            ->subject("{$currentDate} Модерация ВОД.");
    }
}
