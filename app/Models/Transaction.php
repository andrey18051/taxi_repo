<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchantAccount',
        'order_reference',
        'transaction_type',
        'amount',
        'currency',
        'base_amount',
        'base_currency',
        'transaction_status',
        'created_date',
        'processing_date',
        'reason_code',
        'reason',
        'settlement_date',
        'email',
        'phone',
        'payment_system',
        'card_pan',
        'card_type',
        'issuer_bank_country',
        'issuer_bank_name',
        'fee',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'processing_date' => 'datetime',
        'settlement_date' => 'datetime',
    ];

}
