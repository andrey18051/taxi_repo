<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $logUrl;
    public $deletedCount;
    public $expiryDays = 30;

    public function __construct($logUrl, $deletedCount = 0)
    {
        $this->logUrl = $logUrl;
        $this->deletedCount = $deletedCount;
    }

    public function build()
    {
        $expiryDate = now()->addDays($this->expiryDays)->format('d.m.Y');

        return $this->subject('📊 Отчёт по логам Laravel')
            ->markdown('emails.log-report')
            ->with([
                'logUrl' => $this->logUrl,
                'expiryDays' => $this->expiryDays,
                'expiryDate' => $expiryDate,
                'deletedCount' => $this->deletedCount,
            ]);
    }
}
