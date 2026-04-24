<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт по логам Laravel</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 580px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 8s infinite;
        }

        @keyframes shimmer {
            0%, 100% {
                transform: translate(0, 0);
            }
            50% {
                transform: translate(10px, 10px);
            }
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .header p {
            margin: 12px 0 0;
            opacity: 0.95;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        .content {
            padding: 40px 35px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .message {
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .info-grid {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 20px;
            margin: 30px 0;
            border: 1px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #334155;
        }

        .info-value {
            color: #0f172a;
            font-weight: 500;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            margin: 25px 0;
            border-radius: 12px;
            font-size: 13px;
            color: #92400e;
        }

        .url-box {
            background: #0f172a;
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            position: relative;
        }

        .url {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            color: #a5f3fc;
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .security-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0 15px;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .security-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #64748b;
        }

        .footer {
            background: #f8fafc;
            padding: 25px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        hr {
            margin: 20px 0;
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 15px;
            }
            .content {
                padding: 25px 20px;
            }
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .button {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div class="logo-icon">📊</div>
            <h1>Отчёт по логам Laravel</h1>
            <p>{{ config('app.name', 'Служба Таксі Лайт Юа') }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                👋 Здравствуйте!
            </div>
            <div class="message">
                Файл логов вашего приложения <strong>Laravel</strong> готов к скачиванию.
                Лог содержит информацию о работе системы за указанный период.
            </div>

            <div class="button-container">
                <a href="{{ $logUrl }}" class="button">
                    📥 Скачать лог-файл
                </a>
            </div>

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">
                        <span>📁</span> Тип файла
                    </span>
                    <span class="info-value">Лог-файл Laravel (.log)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <span>📅</span> Дата создания
                    </span>
                    <span class="info-value">{{ now()->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <span>⏰</span> Время создания
                    </span>
                    <span class="info-value">{{ now()->format('H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <span>🗑️</span> Автоудаление
                    </span>
                    <span class="info-value">
                        через <strong>{{ $expiryDays }} дней</strong> (до {{ $expiryDate }})
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <span>📏</span> Размер файла
                    </span>
                    <span class="info-value" id="fileSize">—</span>
                </div>
            </div>

            @if($deletedCount > 0)
                <div style="margin: 20px 0; text-align: center;">
                    <span class="badge">
                        🧹 Очистка сервера
                    </span>
                    <p style="margin: 12px 0 0; font-size: 13px; color: #475569;">
                        Удалено {{ $deletedCount }} старых архив{{ $deletedCount > 1 ? 'ов' : '' }}
                        (старше {{ $expiryDays }} дней)
                    </p>
                </div>
            @endif

            <div class="warning">
                <strong>💡 Подсказка:</strong> Если кнопка не работает, скопируйте ссылку ниже
                и вставьте её в адресную строку браузера.
            </div>

            <div class="url-box">
                <button class="copy-btn" onclick="copyToClipboard()">📋 Копировать</button>
                <div class="url" id="logUrl">{{ $logUrl }}</div>
            </div>

            <div class="security-icons">
                <div class="security-item">
                    <span>🔒</span>
                    <span>SSL защита</span>
                </div>
                <div class="security-item">
                    <span>🛡️</span>
                    <span>Шифрование</span>
                </div>
                <div class="security-item">
                    <span>⏱️</span>
                    <span>Ограничен по времени</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name', 'Служба Таксі Лайт Юа') }}. Все права защищены.</p>
            <p style="margin-top: 8px;">
                Сообщение сгенерировано автоматически — {{ now()->format('d.m.Y H:i:s') }}
            </p>
            <hr>
            <p style="font-size: 10px;">
                Это автоматическое сообщение, пожалуйста, не отвечайте на него.
            </p>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        const url = document.getElementById('logUrl').innerText;
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.innerText;
            btn.innerText = '✅ Скопировано!';
            setTimeout(() => {
                btn.innerText = originalText;
            }, 2000);
        });
    }

    // Эмуляция получения размера файла (опционально)
    fetch('{{ $logUrl }}', { method: 'HEAD' })
        .then(response => {
            const size = response.headers.get('Content-Length');
            if (size) {
                const kb = (size / 1024).toFixed(1);
                const mb = (size / (1024 * 1024)).toFixed(2);
                const displaySize = mb > 1 ? `${mb} MB` : `${kb} KB`;
                document.getElementById('fileSize').innerText = displaySize;
            } else {
                document.getElementById('fileSize').innerHTML = '—';
            }
        })
        .catch(() => {
            document.getElementById('fileSize').innerHTML = '—';
        });
</script>
</body>
</html>
