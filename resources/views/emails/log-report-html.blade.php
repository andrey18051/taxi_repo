<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Отчёт по логам Laravel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 40px 20px;
        }
        .email-container {
            max-width: 520px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-header {
            background: #1a365d;
            padding: 35px 25px;
            text-align: center;
        }
        .email-header h1 {
            color: white;
            margin: 0;
            font-size: 26px;
        }
        .email-body {
            padding: 35px;
        }
        .btn {
            display: block;
            background: #2b6cb0;
            color: white;
            text-align: center;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin: 25px 0;
        }
        .btn:hover {
            background: #2c5282;
        }
        .info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .url {
            background: #edf2f7;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            margin: 15px 0;
        }
        .footer {
            background: #edf2f7;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
        }
        @media (max-width: 600px) {
            .email-body { padding: 25px; }
            .info-item { flex-direction: column; gap: 4px; }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        <h1>📊 Отчёт по логам</h1>
    </div>
    <div class="email-body">
        <p>Здравствуйте!</p>
        <p>Подготовлен новый файл логов Laravel.</p>

        <a href="{{ $logUrl }}" class="btn">📥 Скачать лог-файл</a>

        <div class="info">
            <div class="info-item"><strong>📁 Тип файла</strong> <span>Лог Laravel (.log)</span></div>
            <div class="info-item"><strong>📅 Дата</strong> <span>{{ now()->format('d.m.Y H:i:s') }}</span></div>
            <div class="info-item"><strong>🗑️ Удаление</strong> <span>через {{ $expiryDays }} дней</span></div>
            @if($deletedCount > 0)
                <div class="info-item"><strong>🧹 Очистка</strong> <span>удалено {{ $deletedCount }} архивов</span></div>
            @endif
        </div>

        <div class="url">{{ $logUrl }}</div>

        <p style="font-size: 13px; color: #718096; margin-top: 20px;">
            🔒 Ссылка действительна {{ $expiryDays }} дней.<br>
            📎 Файл будет автоматически удалён с сервера.
        </p>
    </div>
    <div class="footer">
        © {{ date('Y') }} {{ config('app.name') }}<br>
        {{ now()->format('d.m.Y H:i:s') }}
    </div>
</div>
</body>
</html>
