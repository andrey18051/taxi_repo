<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionStatus extends Model
{
    use HasFactory;

    /**
     * @var mixed|string
     */
    private $bonus;
    /**
     * @var mixed|string
     */
    private $double;
}
