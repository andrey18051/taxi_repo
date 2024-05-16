<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoubleOrder extends Model
{
    use HasFactory;

    /**
     * @var false|mixed|string
     */
    public $responseBonusStr;
    /**
     * @var false|mixed|string
     */
    public $responseDoubleStr;
    /**
     * @var mixed|string|null
     */
    public $authorizationBonus;
    /**
     * @var mixed|string|null
     */
    public $authorizationDouble;
    /**
     * @var mixed|string
     */
    public $connectAPI;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public $identificationId;
    /**
     * @var mixed
     */
    public $apiVersion;
    /**
     * @var mixed
     */
    public $id;


}
