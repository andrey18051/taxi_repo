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
    public $order;
    /**
     * @var mixed
     */
    private $orderType;
    /**
     * @var mixed|string
     */
    public $cancel;
    /**
     * @var mixed
     */
    public $execution_status;
}
