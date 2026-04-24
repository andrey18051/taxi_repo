<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт по логам Laravel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #1a202c;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1a365d 0%, #2b6cb0 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2b6cb0 0%, #2c5282 100%);
            color: white !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .info-grid {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #2d3748;
        }
        .info-value {
            color: #4a5568;
            font-family: 'Courier New', monospace;
        }
        .badge {
            display: inline-block;
            background-color: #c6f6d5;
            color: #22543d;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .footer {
            background-color: #edf2f7;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
        .warning {
            background-color: #fef5e7;
            border-left: 4px solid #ecc94b;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        .url {
            background-color: #edf2f7;
            padding: 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1>📊 Отчёт по логам Laravel</h1>
            <p>{{ config('app.name', 'Служба Таксі Лайт Юа') }}</p>
        </div>

        <div class="content">
            <p style="margin-top: 0;">Здравствуйте!</p>
            <p>Файл логов вашего приложения <strong>Laravel</strong> готов к скачиванию.</p>

            <div style="text-align: center;">
                <a href="{{ $logUrl }}" class="button">📥 Скачать лог-файл</a>
            </div>

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">📁 Тип файла</span>
                    <span class="info-value">Лог-файл Laravel (.log)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">📅 Дата создания</span>
                    <span class="info-value">{{ now()->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">⏰ Время создания</span>
                    <span class="info-value">{{ now()->format('H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🗑️ Автоудаление</span>
                    <span class="info-value">через <strong>{{ $expiryDays }} дней</strong> (до {{ $expiryDate }})</span>
                </div>
            </div>

            @if($deletedCount > 0)
                <div style="margin: 20px 0;">
                    <span class="badge">🧹 Очистка сервера</span>
                    <p style="margin: 10px 0 0;">Удалено {{ $deletedCount }} старых архив{{ $deletedCount > 1 ? 'ов' : '' }} (старше {{ $expiryDays }} дней)</p>
                </div>
            @endif

            <div class="warning">
                <strong>💡 Подсказка:</strong> Если кнопка не работает, скопируйте ссылку и вставьте её в адресную строку браузера.
            </div>

            <div class="url">
                {{ $logUrl }}
            </div>

            <p style="font-size: 13px; color: #718096;">
                🔒 Ссылка действительна до автоматического удаления файла.
                <br>
                📎 Все данные хранятся в зашифрованном виде.
            </p>
        </div>

        <div class="footer">
            © {{ date('Y') }} {{ config('app.name', 'Служба Таксі Лайт Юа') }}.<br>
            Сообщение сгенерировано автоматически — {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</div>
</body>
</html>
