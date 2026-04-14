<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервная копия БД</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f4; }
        .content { background: white; padding: 20px; border-radius: 5px; }
        .header { background: #2c3e50; color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 0.8em; color: #666; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 3px; }
        .info { margin: 15px 0; padding: 10px; background: #e8f5e8; border-left: 3px solid #27ae60; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>✅ Резервная копия базы данных</h2>
    </div>
    <div class="content">
        <p><strong>База данных:</strong> {{ $dbName }}</p>
        <p><strong>Размер файла:</strong> {{ $backupSize }} MB</p>
        <p><strong>Дата создания:</strong> {{ $date }}</p>
        <p><strong>Сервер:</strong> {{ $hostname }}</p>

        <div class="info">
            <strong>🔗 Ссылка для скачивания:</strong><br>
            <a href="{{ $backupUrl }}">{{ $backupUrl }}</a>
        </div>

        <p style="text-align: center;">
            <a href="{{ $backupUrl }}" class="btn">Скачать дамп</a>
        </p>

        <hr>
        <small>Автоматическое создание дампа | Система: {{ config('app.name') }}</small>
    </div>
    <div class="footer">
        <p>Это письмо сгенерировано автоматически. Пожалуйста, не отвечайте на него.</p>
    </div>
</div>
</body>
</html>

