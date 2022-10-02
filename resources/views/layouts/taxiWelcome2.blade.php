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

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">

    <!-- Global site tag (gtag.js) - Google Ads: 999615800-->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>


<!-- Scripts-->
     <script src="{{ asset('js/app.js') }}" defer></script>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">

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
                    <!-- Links -->
                    <li class="nav-item dropdown">
                        <a id="navbar" class="nav-link" href="{{route('homeStreet', [$phone, $user_name])}}" role="button" target="_blank" data-bs-toggle="#" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Замовлення') }}
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a id="navbar" class="nav-link" href="{{ route('login-taxi') }}" role="button" target="_blank" data-bs-toggle="#" aria-haspopup="true" aria-expanded="false" v-pre>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                                <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                            </svg>
                            {{ __('Вхід') }}
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a id="navbar" class="nav-link" href="{{ route('registration-sms', '000') }}" role="button" target="_blank" data-bs-toggle="#" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Реєстрація') }}
                        </a>
                    </li>

                </ul>
            </div>
        </div>

    </nav>



    <div class="container ">
        <a class="btn btn-outline-success but_vart  btn-circle" href="{{route('homeStreet', [$phone, $user_name])}}" target="_blank" title="Розрахунок вартості">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
            </svg>
        </a>
    </div>


    <main class="py-4">
        @yield('content')
    </main>
</div>


<footer class="text-muted text-center text-small">
   <p class="mb-1">&copy; 2022</p> <!-- Легке замовлення таксі-->
    <ul class="list-inline">

        <li class="list-inline-item"><a href="{{route('homeStreet', [$phone, $user_name])}}" target="_blank">Розрахунок вартості</a></li>
        <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
        <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
        <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank">Ми на Фейсбук</a></li>
        <li class="list-inline-item"><a href="{{ route('callWorkForm') }}" target="_blank">Шукаю роботу водієм</a></li>
    </ul>
</footer>
</body>
</html>
