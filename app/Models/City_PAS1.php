<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City_PAS1 extends Model
{
    use HasFactory;
    protected $table = 'city_pas_1_s';

    /**
     * @var mixed|string
     */
    private $name;
    /**
     * @var mixed|string
     */
    private $address;
    /**
     * @var mixed|string
     */
    private $login;
    /**
     * @var mixed|string
     */
    private $password;
    /**
     * @var mixed|string
     */
    private $online;
    /**
     * @var mixed|string
     */
    private $wfp_merchantAccount;
    /**
     * @var mixed|string
     */
    private $wfp_merchantSecretKey;
}
