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
    <script>
        var months = new Array(13);
        months[1]="січня"; months[2]="лютого"; months[3]="березня"; months[4]="квітня"; months[5]="травня";
        months[6]="червня"; months[7]="липня"; months[8]="серпня"; months[9]="вересня"; months[10]="жовтня";
        months[11]="листопада"; months[12]="грудня";

        var time = new Date();
        var thismonth = months[time.getMonth() + 1];
        var date = time.getDate();
        var thisyear = time.getYear();
        var day = time.getDay() + 1;

        if (thisyear < 2000)
            thisyear = thisyear + 1900;
        if (day == 1) DayofWeek = "Неділя";
        if (day == 2) DayofWeek = "Понеділок";
        if (day == 3) DayofWeek = "Вівторок";
        if (day == 4) DayofWeek = "Середа";
        if (day == 5) DayofWeek = "Четвер";
        if (day == 6) DayofWeek = "П'ятниця";
        if (day == 7) DayofWeek = "Субота";

    </script>
    <script>
        setInterval(function() {
            var cd = new Date();
            var clockdat = document.getElementById("clockdat");
            clockdat.innerHTML = cd.toLocaleTimeString();
        }, 1000);
    </script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>
    <!-- Styles -->
    <link rel="stylesheet" href="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/base/jquery-ui.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">

    <script type="text/javascript">
        var route = "{{ url('autocomplete-search-combo') }}";
        $.ajax({
            url: route,         /* Куда пойдет запрос */
            method: 'get',             /* Метод передачи (post или get) */
            dataType: 'json',          /* Тип данных в ответе (xml, json, script, html). */
            data: {text: 'Текст'},     /* Параметры передаваемые в запросе. */
            success: function(data){   /* функция которая будет выполнена после успешного запроса.  */

                $(function() {
                    $('#search').autocomplete({
                        source: data
                    })
                });
                $(function() {
                    $('#search1').autocomplete({
                        source: data
                    })
                });
            }
        });
    </script>
</head>
<body>
<div id="app">
    @include ('layouts.navigationCombo')
    <main class="py-4">
        @include ('layouts.messages')
        @yield('content')
    </main>
</div>
@include ('layouts.footerCombo')
</body>
</html>
