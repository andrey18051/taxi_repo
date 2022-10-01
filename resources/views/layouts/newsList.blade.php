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

    <style>

        div.btn {
            display: flex;
            justify-content: center;

        }
        .gradient-button {
            text-decoration: none;
            display: inline-block;
            color: white;
            padding: 20px 30px;
            margin: 10px 20px;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            letter-spacing: 2px;
            background-image: linear-gradient(to right, #9EEFE1 0%, #4830F0 51%, #9EEFE1 100%);
            background-size: 200% auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, .1);
            transition: .5s;
        }
        .gradient-button:hover {
            background-position: right center;
        }

        .gradient {
            color: rgba(0,0,0,0.6);
            text-shadow: 2px 8px 6px rgba(0,0,0,0.2),
            0px -5px 35px rgba(255,255,255,0.3);
        }

        .blockquote-3 {
            position: relative;
            text-align: center;
            margin: 16px 16px 34px 16px;
            border: 4px solid #337AB7;
            border-radius: 20px;
            padding: 16px 24px;
            font-size: 18px;
        }
        .blockquote-3:before,
        .blockquote-3:after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
        }
        .blockquote-3:before {
            left: 30px;
            bottom: -32px;
            border: 16px solid;
            border-color: #337AB7 transparent transparent #337AB7;
        }
        .blockquote-3:after {
            left: 35px;
            bottom: -21px;
            border: 12px solid;
            border-color: #fff transparent transparent #fff;
        }
        .blockquote-3 cite {
            position: absolute;
            bottom: -28px;
            left: 62px;
            font-size: 15px;
            font-weight: bold;
            color: #337AB7;
        }
        @-webkit-keyframes scroll {
            0% {
                -webkit-transform: translate(0, 0);
                transform: translate(0, 0);
            }
            100% {
                -webkit-transform: translate(-100%, 0);
                transform: translate(-100%, 0)
            }
        }

        @-moz-keyframes scroll {
            0% {
                -moz-transform: translate(0, 0);
                transform: translate(0, 0);
            }
            100% {
                -moz-transform: translate(-100%, 0);
                transform: translate(-100%, 0)
            }
        }

        @keyframes scroll {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(-100%, 0)
            }
        }

        .marquee {
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
        }

        .marquee span {
            display: inline-block;
            padding-left: 50%;
            -webkit-animation: scroll 50s infinite linear;
            -moz-animation: scroll 50s infinite linear;
            animation: scroll 50s infinite linear;
        }
        .btn-circle {
            width: 38px;
            height: 38px;
            border-radius: 19px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
            font-size: 16px;
        }
        .but_vart {
            position: absolute;
            margin-top: 70px;
            right: 2px;
            top: 2px;
        }

        .but_vart_vart {
            position: absolute;
            margin-top: 105px;
            right: 2px;
            top: 2px;
        }
    </style>
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
