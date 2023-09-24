<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecStatusHistory extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $order;
    /**
     * @var mixed
     */
    private $orderType;
    /**
     * @var mixed|string
     */
    private $cancel;
    /**
     * @var mixed
     */
    private $execution_status;
}
