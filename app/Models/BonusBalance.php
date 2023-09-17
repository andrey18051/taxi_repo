<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusBalance extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $orderwebs_id;
    /**
     * @var mixed
     */
    private $users_id;
    /**
     * @var mixed
     */
    private $bonus_types_id;
    /**
     * @var mixed
     */
    private $bonusAdd;
    /**
     * @var mixed
     */
    private $bonusDel;
    /**
     * @var mixed
     */
    private $bonusBloke;
}
