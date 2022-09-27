<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="EvCZdtAnMAe93bc1OvK2SSBTfq0S3M1nI7qnWqHdyAQ" />

    <title>{{ config('app.name') }}</title>
    <meta name="description" content="Швидкі та надійні поїздки &#129523 по місту Київ та Київській області.
        Подання таксі &#128662 за 4-6 хвилин. Працюємо більше 10 років. Доступні низькі тарифи &#127974.
        Замовлення на новому та швідкому сайті та у смартфоні &#128241 надійного та перевіреного роками Таксі Лайт Юа.
        Підібрати комфортний автомобіль &#128662 за найкращою ціною &#127974.
        Кур'єрська доставка. Доставка документів &#128462 та посилок &#128230.">
        <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">
<!--
        &lt;!&ndash; Global site tag (gtag.js) - Google Ads: 999615800 &ndash;&gt;
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>
-->


    <!-- Fonts -->
<!--    <link rel="preload" rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito&display=swap" rel="stylesheet">-->

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        /* CSS */
        .btn-circle {
            width: 38px;
            height: 38px;
            border-radius: 19px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
            font-size: 16px;
        }
    </style>
<!--    <link rel="stylesheet" href="{{ asset('css/left-nav-style.css') }}">-->
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">

                <a class="navbar-brand" href="{{ url('/') }}" target="_blank">
                    <img src="{{ asset('img/logo.jpg') }}" style="width: 40px; height: 40px">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" target="_blank" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        <li class="nav-item dropdown">
                            <a id="navbar" class="nav-link" href="/" role="button" data-bs-toggle="#" target="_blank" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ "Вихід" }}
                            </a>
                        </li>


                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @include ('layouts.messages')
            @yield('content')
        </main>
    </div>
    <script defer src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.1/bootstrap3-typeahead.min.js">
    </script>
    <script type="text/javascript">
        var route = "{{ url('autocomplete-search') }}";
        $('#search').typeahead({
            source: function (query, process) {
                return $.get(route, {
                    query: query
                }, function (data) {
                    return process(data);
                });
            }
        });
        $('#search1').typeahead({
            source: function (query1, process1) {
                return $.get(route, {
                    query: query1
                }, function (data1) {
                    return process1(data1);
                });
            }
        });
        var route2 = "{{ url('autocomplete-search-object') }}";
        $('#search2').typeahead({
            source: function (query2, process2) {
                return $.get(route2, {
                    query: query2
                }, function (data2) {
                    return process2(data2);
                });
            }
        });
        $('#search3').typeahead({
            source: function (query3, process3) {
                return $.get(route2, {
                    query: query3
                }, function (data3) {
                    return process3(data3);
                });
            }
        });

    </script>
    <!-- Scripts
    <script src="{{ asset('js/app.js') }}" defer></script>-->
    <footer class="text-muted text-center text-small">
        <p class="mb-1">&copy; 2022</p> <!-- Легке замовлення таксі-->
        <ul class="list-inline">
        <!--        <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}" target="_blank">Конфіденційність</a></li>-->
            <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
            <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
            <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank">Ми на Фейсбук</a></li>
        </ul>
    </footer>
</body>
</html>
