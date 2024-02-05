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

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/left-nav-style.css') }}">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <input type="checkbox" id="nav-toggle" hidden>
                    <nav class="nav">
                        <label for="nav-toggle" class="nav-toggle" @click></label>
                        <ul class="navbar-nav">
                            <li><a href="{{ asset('/laratrust') }}">Roles</a>
                            <li><router-link to="/admin/users">Users</router-link></li>
                            <li><router-link to="/admin/user_messages">User's messages</router-link></li>
                            <li><router-link to="/admin/user_messages_email">User's emails</router-link></li>
                            <li><router-link to="/admin/services">Services</router-link></li>
                            <li><a href="{{ asset('/services/serviceNew') }}">Services Add</a>
                            <li><a href="{{ asset('/blacklist') }}">Android settings</a>
                            <li><router-link to="/admin/closeReason">Close Reason</router-link></li>
                            <li><router-link to="/admin/fondy">Fondy</router-link></li>
                            <li><router-link to="/admin/bonus">Bonuses types</router-link></li>
                            <li><router-link to="/admin/city">Cities</router-link></li>
                            <li><a href="{{ asset('/city/cityNew') }}">City Add</a>
                            <li><a href="{{ asset('/') }}">Taxi</a>
                            <li><a href="{{ asset('/home') }}">Report</a>
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
    <script>
        // Add this script to handle automatic collapse
        document.addEventListener("DOMContentLoaded", function () {
            var navToggle = document.getElementById('nav-toggle');
            var navLinks = document.querySelectorAll('.nav a');

            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    // Check if the checkbox is checked (visible)
                    if (navToggle.checked) {
                        // Uncheck the checkbox to hide the left panel
                        navToggle.checked = false;
                    }
                });
            });
        });
    </script>
</body>
</html>
