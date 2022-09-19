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


<!-- Scripts-->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
        <div class="container">

            <a class="navbar-brand" href="{{ url('/') }}" target="_blank">
                <img src="{{ asset('img/logo.jpg') }}" style="width: 50px; height: 50px">
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


    <main class="py-4">
        @include ('layouts.messages')
        @yield('content')
    </main>
</div>


<footer class="text-muted text-center text-small">
    <p class="mb-1">&copy; 2022 Легке замовлення таксі</p>
    <ul class="list-inline">
        <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}" target="_blank">Конфіденційність</a></li>
        <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
        <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
        <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank">Сторинка на Фейсбук</a></li>
    </ul>
</footer>
</body>
</html>
