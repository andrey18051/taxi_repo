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
        $this->expiryDate = now()->addDays($this->expiryDays)->format('d.m.Y');
    }

    public function build()
    {
        // Для HTML-версии:
        return $this->subject('📊 Отчёт по логам Laravel — ' . config('app.name'))
            ->view('emails.log-report-html')
            ->with([
                'logUrl' => $this->logUrl,
                'expiryDays' => $this->expiryDays,
                'expiryDate' => $this->expiryDate,
                'deletedCount' => $this->deletedCount,
            ]);

        // Или для Markdown-версии:
        // return $this->subject('📊 Отчёт по логам Laravel')
        //             ->markdown('emails.log-report');
    }
}
