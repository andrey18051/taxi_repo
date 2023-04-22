<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    use HasFactory;
    private $name;
    private $email;
    /**
     * @var mixed
     */
    private $telegram_id;
    /**
     * @var mixed
     */
    private $viber_id;

}
