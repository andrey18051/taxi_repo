<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёт логов Laravel</title>
</head>
<body>
<p>Здравствуйте!</p>

<p>Файл логов Laravel доступен по ссылке:</p>

<p><a href="{{ $logUrl }}" target="_blank">{{ $logUrl }}</a></p>

<p>После проверки файл можно удалить с сервера при необходимости.</p>

<hr>
<small>Сообщение сгенерировано автоматически — {{ date('d.m.Y H:i:s') }}</small>
</body>
</html>
