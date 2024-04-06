<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    /**
     * @var mixed|string
     */
    private $name;
    /**
     * @var mixed|string
     */
    private $email;
    /**
     * @var mixed|string
     */
    private $phone;
    /**
     * @var mixed|string
     */
    private $service;
    /**
     * @var mixed|string
     */
    private $city;
    /**
     * @var mixed|string
     */
    private $group_id;
}
