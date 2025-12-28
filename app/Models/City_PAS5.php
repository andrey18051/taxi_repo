<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City_PAS5 extends Model
{
    use HasFactory;
    protected $table = 'city_pas_5_s';
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
    /**
     * @var mixed|string
     */
    private $black_list;
}
