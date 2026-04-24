@component('mail::message')
    # 📋 Отчёт по логам Laravel

    Файл логов Laravel доступен для скачивания по ссылке ниже.

    @component('mail::button', ['url' => $logUrl, 'color' => 'primary'])
        📥 Скачать лог-файл
    @endcomponent

    ## ⏰ Срок хранения
    Файл будет автоматически удалён с сервера через **{{ $expiryDays }} дней** (до {{ $expiryDate }}).

    @if($deletedCount > 0)
        🧹 **Очистка:** Удалено {{ $deletedCount }} старых архивов (старше {{ $expiryDays }} дней).
    @endif

    Если ссылка не открывается, возможно файл уже удалён за давностью срока хранения.

    ---

    Сообщение сгенерировано автоматически — {{ now()->format('d.m.Y H:i:s') }}
@endcomponent
