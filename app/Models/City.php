<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $name;
    /**
     * @var mixed
     */
    private $address;
    /**
     * @var mixed
     */
    private $login;
    /**
     * @var mixed
     */
    private $password;
    /**
     * @var bool|mixed
     */
    private $online;
}
