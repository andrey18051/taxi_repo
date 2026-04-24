@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        {{ __('You are logged in!') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
        <h1 class="display-4">Database reports</h1>
        <p class="lead">Quick selection of requests and reporting period</p>

        <form action="{{ route('logs.download') }}" method="get" style="display:inline;">
            <button href="{{ route('logs.download-latest') }}" class="btn btn-primary btn-lg">
                📤 Скачать последний лог
            </button>
        </form>

        <form action="{{ route('logs.view') }}" method="get" style="display:inline;">
            <button type="submit" class="btn btn-primary btn-lg">
                📜 Посмотреть логи
            </button>
        </form>

        <form action="{{ route('logs.clear') }}" method="get" style="display:inline;">
            <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Вы уверены, что хотите очистить логи?')">
                🗑 Очистить логи
            </button>
        </form>
    </div>

    <div class="container">
        <h3>Отчет по IP</h3>
        <div class="row">
            <div class="col-md-6">
                <form action="{{route('reportIpUniqShort')}}" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Уникальные IP</b></p>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                                           value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateTo" name="dateTo"
                                           value="{{ date('Y-m-d') }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                            <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-6">
                <form action="{{route('reportIpRoute')}}" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Расчеты поездок</b></p>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                                           value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateTo" name="dateTo"
                                           value="{{ date('Y-m-d') }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                            <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <br>

        <div class="row">
            <div class="col-md-6">
                <form action="{{route('reportIpPage')}}" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Страницы сайта</b></p>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                                           value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateTo" name="dateTo"
                                           value="{{ date('Y-m-d') }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                            <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-6">
                <form action="{{route('reportIpUniq')}}" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Расшифровка IP</b></p>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                                           value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateTo" name="dateTo"
                                           value="{{ date('Y-m-d') }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                            <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <br>

        <div class="row">
            <div class="col-md-6">
                <form action="{{route('reportIpOrder')}}" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Заказы поездок</b></p>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                                           value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-5">
                                    <input class="form-control" type="date" id="dateTo" name="dateTo"
                                           value="{{ date('Y-m-d') }}"
                                           autocomplete="off" placeholder="Начало периода">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                            <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-6">
                <form action="{{route('bonusReport')}}" method="POST" id="form">
                    @csrf
                    <div class="row card">
                        <div class="card-body">
                            <div class="row">
                                <p><b>Бонусы</b></p>

                                <div class="row">
                                    <div class="col-md-11">
                                        <select class="form-select" id="email" name="email">
                                            @for ($i = 0; $i < count($emailArray); $i++)
                                                <option>{{ $emailArray[$i] }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                                <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- НОВЫЙ РАЗДЕЛ 1: Администрирование сервисов --}}
    <div class="container mt-5">
        <h3>🛠 Администрирование сервисов</h3>
        <div class="row">
            {{-- Centrifugo --}}
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Centrifugo</h5>
                        <p class="card-text">Панель управления сервером реального времени.</p>
                        <p class="text-muted small">Пароль: qB_xm2BvuAPRL5zCHI7CLg</p>
                        <a href="http://91.219.60.148:8008/" class="btn btn-outline-primary mt-auto" target="_blank">Перейти в Centrifugo</a>
                    </div>
                </div>
            </div>

            {{-- Тест Centrifugo --}}
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">🧪 Тест Centrifugo</h5>
                        <p class="card-text">Проверка WebSocket-соединения с сервером.</p>
                        <a href="https://t.easy-order-taxi.site/centrifugo-test" class="btn btn-outline-primary mt-auto" target="_blank">Открыть тест</a>
                    </div>
                </div>
            </div>

            {{-- Redis Commander --}}
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">🗄 Redis Commander</h5>
                        <p class="card-text">Веб-интерфейс для управления Redis.</p>
                        <a href="http://91.219.60.148:8085/" class="btn btn-outline-primary mt-auto" target="_blank">Открыть Redis</a>
                    </div>
                </div>
            </div>

            {{-- phpMyAdmin --}}
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">📊 phpMyAdmin</h5>
                        <p class="card-text">Управление базой данных MySQL.</p>
                        <p class="text-muted small">Логин: admin_office<br>Пароль: 18And051971</p>
                        <a href="http://91.219.60.148:8081/" class="btn btn-outline-primary mt-auto" target="_blank">Открыть phpMyAdmin</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- НОВЫЙ РАЗДЕЛ 2: Управление проектом --}}
    <div class="container mt-4">
        <h3>🌐 Управление проектом</h3>
        <div class="row">
            {{-- Хостинг --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">🚀 Хостинг NETX</h5>
                        <p class="card-text">Панель управления хостингом и доменами.</p>
                        <p class="text-muted small">Логин: andrey18051@gmail.com<br>Пароль: 18And051971</p>
                        <a href="https://netx.com.ua/" class="btn btn-outline-primary mt-auto" target="_blank">Перейти на хостинг</a>
                    </div>
                </div>
            </div>

            {{-- Здесь можно добавить другие внешние сервисы --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">📁 Репозиторий (GitHub)</h5>
                        <p class="card-text">Исходный код проекта (если есть).</p>
                        <a href="#" class="btn btn-outline-secondary mt-auto" target="_blank">Перейти (не указано)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Дополнительный раздел для заметок или документации (опционально) --}}
    <div class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header">
                📋 Полезная информация
            </div>
            <div class="card-body">
                <p><strong>Важные пароли и доступы:</strong></p>
                <ul>
                    <li><strong>Centrifugo:</strong> qB_xm2BvuAPRL5zCHI7CLg</li>
                    <li><strong>phpMyAdmin:</strong> admin_office / 18And051971</li>
                    <li><strong>Хостинг NETX:</strong> andrey18051@gmail.com / 18And051971</li>
                </ul>
                <p class="text-muted mb-0"><small>Все ссылки открываются в новой вкладке.</small></p>
            </div>
        </div>
    </div>

@endsection
