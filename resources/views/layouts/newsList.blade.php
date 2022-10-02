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

    <!-- Global site tag (gtag.js) - Google Ads: 999615800 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>


<!-- Scripts-->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>
    <!-- Styles -->
    <link rel="stylesheet" href="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/base/jquery-ui.css">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-r-circle" viewBox="0 0 16 16">
                                <path d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8Zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0ZM5.5 4.002h3.11c1.71 0 2.741.973 2.741 2.46 0 1.138-.667 1.94-1.495 2.24L11.5 12H9.98L8.52 8.924H6.836V12H5.5V4.002Zm1.335 1.09v2.777h1.549c.995 0 1.573-.463 1.573-1.36 0-.913-.596-1.417-1.537-1.417H6.835Z"/>
                            </svg>
                            {{ __('Реєстрація') }}
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


<footer class="text-muted text-center text-small">
    <p class="mb-1">&copy; 2022</p> <!-- Легке замовлення таксі-->
    <ul class="list-inline">
    <!--        <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}" target="_blank">Конфіденційність</a></li>-->
        <li class="list-inline-item"><a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank">Розрахунок вартості</a></li>
        <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
        <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
        <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank">Ми на Фейсбук</a></li>
    </ul>
</footer>
</body>
</html>
