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
</div>

<div class="container">
    <h3>Отчет по IP</h3>
    <form action="{{route('reportIpUniqShort')}}" id="form">
        @csrf
        <div class="row card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                               value="{{ date('Y-m-d', strtotime("-1 month")) }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateTo" name="dateTo"
                               value="{{  date('Y-m-d') }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">
                            Уникальные IP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{route('reportIpRoute')}}" id="form">
        @csrf
        <div class="row card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                               value="{{ date('Y-m-d', strtotime("-1 month")) }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateTo" name="dateTo"
                               value="{{  date('Y-m-d') }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">
                            Расчеты поездок
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{route('reportIpPage')}}" id="form">
        @csrf
        <div class="row card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                               value="{{ date('Y-m-d', strtotime("-1 month")) }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateTo" name="dateTo"
                               value="{{  date('Y-m-d') }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">
                            Страницы сайта
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{route('reportIpUniq')}}" id="form">
        @csrf
        <div class="row card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                               value="{{ date('Y-m-d', strtotime("-1 month")) }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateTo" name="dateTo"
                               value="{{  date('Y-m-d') }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">
                            Расшифровка IP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{route('reportIpOrder')}}" id="form">
        @csrf
        <div class="row card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateFrom" name="dateFrom"
                               value="{{ date('Y-m-d', strtotime("-1 month")) }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-5">
                        <input class="form-control" type="date" id="dateTo" name="dateTo"
                               value="{{  date('Y-m-d') }}"
                               autocomplete="off" placeholder="Начало периода">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">
                            Заказы поездок
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script defer src="https://www.googleapis.com/geolocation/v1/geolocate?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc"></script>

@endsection
