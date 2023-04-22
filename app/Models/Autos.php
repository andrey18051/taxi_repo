<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autos extends Model
{
    use HasFactory;

    /**
     * @var mixed|string
     */
    private $brand;
    private $model;
    private $type;
    private $color;
    private $year;
    private $number;
    private $driver_id;
}
