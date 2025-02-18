<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogReportMail extends Mailable
{
    use Queueable, SerializesModels;
    public $filePath;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Laravel Log Report')
            ->markdown('emails.log_report')
            ->attach($this->filePath, [
                'as' => 'laravel.log',
                'mime' => 'text/plain',
            ]);
    }
}
