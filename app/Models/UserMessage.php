<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMessage extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $user_id;
    /**
     * @var mixed
     */
    private $text_message;
    /**
     * @var int|mixed
     */
    private $sent_message_info;
    /**
     * @var mixed
     */
    private $app;
    /**
     * @var mixed
     */
    private $city;
}
