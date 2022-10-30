<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="EvCZdtAnMAe93bc1OvK2SSBTfq0S3M1nI7qnWqHdyAQ" />
    <meta name="robots" content="all" />
    <link rel="canonical" href="https://m.easy-order-taxi.site/" />
    <title>Служба Таксі &#128662 від 40 грн по місту (Києв та Київська область)</title>
    <meta name="description" content="Швидкі та надійні поїздки &#129523.  Подання таксі &#128662 за 4-6 хвилин. Працюємо більше 10 років. Доступні низькі тарифи &#127974. Замовлення на сайті та у смартфоні &#128241 комфортного таксі &#128662.  Кур'єрська доставка документів &#128462 та посилок &#128230.">

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">

    <!-- Global site tag (gtag.js) - Google Ads: 999615800-->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XJ7XV5L4WS"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-XJ7XV5L4WS');
    </script>

<!-- Scripts-->
     <script src="{{ asset('js/app.js') }}" defer></script>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">

</head>
<body>
<div id="app">
    @include ('layouts.navigation')
    <main class="py-4">
        @include ('layouts.messages')
        @yield('content')
    </main>
</div>
@include ('layouts.footer')
</body>
</html>
