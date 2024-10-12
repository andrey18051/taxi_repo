<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_number',
        'info',
        'image_path', // Добавьте это поле, чтобы разрешить массовое присвоение
    ];
}
