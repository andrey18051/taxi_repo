<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drivers extends Model
{
    use HasFactory;

    /**
     * @var mixed|string
     */
    private $city;
    /**
     * @var mixed|string
     */
    private $first_name;
    /**
     * @var mixed|string
     */
    private $second_name;
    /**
     * @var mixed|string
     */
    private $email;
    /**
     * @var mixed|string
     */
    private $phone;
}
