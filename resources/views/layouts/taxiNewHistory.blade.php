<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="EvCZdtAnMAe93bc1OvK2SSBTfq0S3M1nI7qnWqHdyAQ" />

    <title>{{ config('app.name') }}</title>
    <meta name="description" content="Швидкі та надійні поїздки &#129523.  Подання таксі &#128662 за 4-6 хвилин. Доступні низькі тарифи &#127974. Кур'єрська доставка документів &#128462 та посилок &#128230.">

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" >

    <!-- Scripts-->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <!-- Styles -->
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
