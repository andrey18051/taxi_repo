<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class City_PAS2 extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'city_pas_2_s';

    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'login',
        'password',
        'online',
        'wfp_merchantAccount',
        'wfp_merchantSecretKey',
        'merchant_fondy',
        'fondy_key_storage',
        'versionApi',
        'cost_correction',
        'card_max_pay',
        'bonus_max_pay',
        'black_list'
    ];

    /**
     * Настройки аудита
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'address',
        'login',
        'password',
        'online',
        'wfp_merchantAccount',
        'wfp_merchantSecretKey',
        'merchant_fondy',
        'fondy_key_storage',
        'versionApi',
        'cost_correction',
        'card_max_pay',
        'bonus_max_pay',
        'black_list'
    ];

    /**
     * Преобразования типов
     *
     * @var array
     */
    protected $casts = [
        'cost_correction' => 'float',
        'card_max_pay' => 'integer',
        'bonus_max_pay' => 'integer',
        'online' => 'boolean',
    ];

    /**
     * Настройка событий аудита
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored'
    ];

    /**
     * Дополнительные данные для аудита
     *
     * @return array
     */
    public function transformAudit(array $data): array
    {
        // Добавляем IP адрес и User Agent
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();
        $data['url'] = request()->fullUrl();

        return $data;
    }

    /**
     * Мутатор для поля online
     */
    public function setOnlineAttribute($value)
    {
        $this->attributes['online'] = $value === 'true' || $value === true || $value === '1' ? 'true' : 'false';
    }

    /**
     * Аксессор для поля online
     */
    public function getOnlineAttribute($value)
    {
        return $value === 'true' || $value === true || $value === '1' ? 'true' : 'false';
    }
}
