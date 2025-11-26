<?php
// app/Models/CityTariff.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityTariff extends Model
{
    use HasFactory;

    protected $table = 'city_tariffs';

    protected $fillable = [
        'city',
        'base_price',
        'base_distance',
        'price_per_km',
        'is_test'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'is_test' => 'boolean'
    ];

    /**
     * Расчет стоимости поездки
     */
    public function calculatePrice(float $distance): float
    {
        if ($distance <= $this->base_distance) {
            return (float) $this->base_price;
        }

        $additionalDistance = $distance - $this->base_distance;
        return (float) $this->base_price + ($additionalDistance * $this->price_per_km);
    }

    /**
     * Scope для тестовых тарифов
     */
    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope для обычных тарифов
     */
    public function scopeRegular($query)
    {
        return $query->where('is_test', false);
    }
}
