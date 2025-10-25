<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogReportMail extends Mailable
{
    use Queueable, SerializesModels;
    public $logUrl;

    public function __construct($logUrl)
    {
        $this->logUrl = $logUrl;
    }

    public function build()
    {
        return $this->subject('Отчёт логов Laravel')
            ->view('emails.log_report')
            ->with(['logUrl' => $this->logUrl]);
    }
}
