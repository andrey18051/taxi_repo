<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $uid_id;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var mixed
     */
    private $status;
    /**
     * @var mixed
     */
    private $user_id;
}
