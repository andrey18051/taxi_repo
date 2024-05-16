<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboTest extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    public $name;
    /**
     * @var int|mixed
     */
    public $street;
}
