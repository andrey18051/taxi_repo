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

    <!-- Scripts -->
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
                    {{ config('app.name', 'Sferaved') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <input type="checkbox" id="nav-toggle" hidden>
                    <nav class="nav">
                        <label for="nav-toggle" class="nav-toggle" onclick></label>
                        <ul class="navbar-nav">
<!--                            <li><a href="https://next.privat24.ua/">Приват24</a>
                            <li><a href="https://online.pravex.ua/user?ReturnUrl=%2F">Правекс</a>
                            <li><a href="https://vchasno.ua/auth/login">Вчасно</a>
                            <li><a href="https://sota-buh.com.ua/account/login?ReturnUrl=%2Fedo">Сота</a>
                            <li><a href="https://pa.zbutenergo.kharkov.ua/frontend/web/index.php/uk/changed-acc/information">Харэнерго</a>
                            <li><a href="https://www.mdoffice.com.ua">MD Office</a>-->
                            <li><a href="{{ route('admin') }}">Admin</a>
                        </ul>
                    </nav>
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
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
