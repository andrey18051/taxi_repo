<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>
    <head>
        <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">
    </head>

    <!-- Scripts-->
    <script src="{{ asset('js/app.js') }}" defer></script>

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
    <footer class="my-5 pt-5 text-muted text-center text-small">
        <p class="mb-1">&copy; 2022 Легке замовлення таксі</p>
        <ul class="list-inline">
            <li class="list-inline-item"><a href="/">Конфіденційність</a></li>
            <li class="list-inline-item"><a href="/">Умови</a></li>
            <li class="list-inline-item"><a href="{{ route('feedback') }}">Підтримка</a></li>
        </ul>
    </footer>
    </body>
</html>
