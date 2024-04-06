<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerGroup extends Model
{
    use HasFactory;

    /**
     * @var mixed|string
     */
    private $name;
    /**
     * @var mixed|string
     */
    private $description;
}
