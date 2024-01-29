<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVisit extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $user_id;
    /**
     * @var mixed
     */
    private $app_name;
}
