<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerEmail extends Model
{
    use HasFactory;

    /**
     * @var mixed
     */
    private $partner_id;
    /**
     * @var mixed|string
     */
    private $subject;
    /**
     * @var mixed
     */
    private $text_message;
    /**
     * @var int|mixed
     */
    private $sent_message_info;
}
