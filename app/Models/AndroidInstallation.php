<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AndroidInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'app',
        'fcm_token',
        'locale',
        'timezone',
        'first_open_at',
        'reminder_due_at',
        'reminder_sent_at',
        'reminder_cancelled_at',
        'reminder_opt_out',
    ];

    protected $casts = [
        'first_open_at' => 'datetime',
        'reminder_due_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_cancelled_at' => 'datetime',
        'reminder_opt_out' => 'boolean',
    ];
}

