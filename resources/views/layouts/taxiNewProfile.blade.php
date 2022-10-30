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

<!--    &lt;!&ndash; Global site tag (gtag.js) - Google Ads: 999615800 &ndash;&gt;
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>-->
    <!-- Scripts-->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>


    <!-- Fonts -->
<!--    <link rel="preload" rel="dns-prefetch" href="//fonts.gstatic.com" as="font">
    <link href="https://fonts.googleapis.com/css?family=Nunito&display=swap" rel="stylesheet">-->

    <!-- Styles -->
    <link rel="stylesheet" href="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/base/jquery-ui.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">
    <script type="text/javascript">
        var route = "{{ url('autocomplete-search2') }}";
        $.ajax({
            url: route,         /* Куда пойдет запрос */
            method: 'get',             /* Метод передачи (post или get) */
            dataType: 'json',          /* Тип данных в ответе (xml, json, script, html). */
            data: {text: 'Текст'},     /* Параметры передаваемые в запросе. */
            success: function(data){   /* функция которая будет выполнена после успешного запроса.  */

                $(function() {
                    $('#route_address_from').autocomplete({
                        source: data
                    })
                });
            }
        });

    </script>
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
