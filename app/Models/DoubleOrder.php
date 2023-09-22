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
    private $responseBonusStr;
    /**
     * @var false|mixed|string
     */
    private $responseDoubleStr;
    /**
     * @var mixed|string|null
     */
    private $authorizationBonus;
    /**
     * @var mixed|string|null
     */
    private $authorizationDouble;
    /**
     * @var mixed|string
     */
    private $connectAPI;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private $identificationId;
    /**
     * @var mixed
     */
    private $apiVersion;
    /**
     * @var mixed
     */
    private $id;


}
