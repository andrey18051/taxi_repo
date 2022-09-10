<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="EvCZdtAnMAe93bc1OvK2SSBTfq0S3M1nI7qnWqHdyAQ" />

    <title>{{ config('app.name') }}</title>

        <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">
        <!-- Global site tag (gtag.js) - Google Ads: 999615800 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'AW-999615800');
        </script>
    <!-- Event snippet for Website traffic conversion page -->
    <script>
        gtag('event', 'conversion', {'send_to': 'AW-999615800/bAbGCJLlkNsDELja09wD'});
    </script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/left-nav-style.css') }}">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">

                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('img/logo.jpg') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->

                        <li class="nav-item dropdown">
                            <a id="navbar" class="nav-link" href="/" role="button" data-bs-toggle="#" aria-haspopup="true" aria-expanded="false" v-pre>
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
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.1/bootstrap3-typeahead.min.js">
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
            source: function (query, process) {
                return $.get(route, {
                    query: query
                }, function (data) {
                    return process(data);
                });
            }
        });
    </script>
    <!-- Scripts
    <script src="{{ asset('js/app.js') }}" defer></script>-->
    <footer class="text-muted text-center text-small">
        <p class="mb-1">&copy; 2022 Легке замовлення таксі</p>
        <ul class="list-inline">
            <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}">Конфіденційність</a></li>
            <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}">Умови</a></li>
            <li class="list-inline-item"><a href="{{ route('feedback') }}">Підтримка</a></li>
            <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/">Сторинка на Фейсбук</a></li>
        </ul>
    </footer>
</body>
</html>
