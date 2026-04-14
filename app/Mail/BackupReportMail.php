<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $backupUrl;
    public $backupSize;
    public $dbName;

    public function __construct($backupUrl, $backupSize, $dbName)
    {
        $this->backupUrl = $backupUrl;
        $this->backupSize = $backupSize;
        $this->dbName = $dbName;
    }

    public function build()
    {
        return $this->subject('Резервная копия БД - ' . $this->dbName . ' - ' . now()->format('Y-m-d H:i:s'))
            ->view('emails.backup_report')
            ->with([
                'backupUrl' => $this->backupUrl,
                'backupSize' => $this->backupSize,
                'dbName' => $this->dbName,
                'date' => now()->format('Y-m-d H:i:s'),
                'hostname' => gethostname(),
            ]);
    }
}
