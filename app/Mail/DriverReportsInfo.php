<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DriverReportsInfo extends Mailable
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
        $filePath = storage_path('app/public/reports/drivers_balance_report.xlsx');

        if (file_exists($filePath)) {
            return $this->markdown('emails.driver_report_info')
                ->with($this->params)
                ->subject($this->params["subject"])
                ->attach($filePath); // Прикрепите файл, если он существует
        } else {
            // Обработка случая, когда файл не существует
            // Например, можно отправить сообщение без вложения
            return $this->markdown('emails.driver_report_info')
                ->with($this->params)
                ->subject($this->params["subject"]);
        }
    }


}
