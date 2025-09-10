<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverKarma extends Model
{
    use HasFactory;

    protected $fillable = [
        'uidDriver',
        'order_id',
        'action',
    ];
}
