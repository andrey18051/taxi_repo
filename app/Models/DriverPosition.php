<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverPosition extends Model
{
    use HasFactory;
    protected $fillable = [
        'driver_uid',
        'latitude',
        'longitude',
    ];
}
