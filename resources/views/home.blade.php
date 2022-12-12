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
    <h1 class="display-4">Reporting databases</h1>
    <p class="lead">Quick selection of applications and reporting period</p>
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

<div class="container">
    <h3>Quite section</h3>
    <a type="button" href="{{ route('admin-quite') }}" class="btn">Quite</a>
</div>

<div class="container">
    <h3>News section</h3>
    <a type="button" href="{{ route('admin-news') }}" class="btn">News</a>
</div>


<script defer src="https://www.googleapis.com/geolocation/v1/geolocate?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc"></script>



<!--


    <div class="container">
        <h3>User section</h3>
        <a type="button" href="{{ route('taxi-account') }}" class="btn">Account</a>
        <a type="button" href="{{ route('search-home') }}" class="btn">Search home</a>
        <a type="button" href="{{ route('taxi-changePassword') }}" class="btn">ChangePassword</a>
        <a type="button" href="{{ route('taxi-restoreSendConfirmCode') }}" class="btn">RestoreSendConfirmCode</a>
        <a type="button" href="{{ route('taxi-restoreСheckConfirmCode') }}" class="btn">RestoreСheckConfirmCode</a>
        <a type="button" href="{{ route('taxi-restorePassword') }}" class="btn">RestorePassword</a>
        <a type="button" href="{{ route('taxi-sendConfirmCode') }}" class="btn">SendConfirmCode</a>
        <a type="button" href="{{ route('taxi-register') }}" class="btn">Register</a>
        <a type="button" href="{{ route('taxi-approvedPhonesSendConfirmCode') }}" class="btn">ApprovedPhonesSendConfirmCode</a>
        <a type="button" href="{{ route('taxi-approvedPhones') }}" class="btn">ApprovedPhones</a>
    </div>

    <div class="container">
        <h3>Webordes section</h3>
        <a type="button" href="{{ route('taxi-version') }}" class="btn">Version</a>
        <a type="button" href="{{ route('taxi-cost') }}" class="btn">Cost</a>
        <a type="button" href="{{ route('taxi-weborders') }}" class="btn">Weborders</a>
        <a type="button" href="{{ route('taxi-tariffs') }}" class="btn">Tariffs</a>
        <a type="button" href="{{ route('taxi-webordersUid') }}" class="btn">WebordersUid</a>
        <a type="button" href="{{ route('taxi-webordersUidDriver') }}" class="btn">WebordersUidDriver</a>
        <a type="button" href="{{ route('taxi-webordersUidCostAdditionalGet') }}" class="btn">WebordersUidCostAdditionalGet</a>
        <a type="button" href="{{ route('taxi-webordersUidCostAdditionalPost') }}" class="btn">WebordersUidCostAdditionalPost</a>
        <a type="button" href="{{ route('taxi-webordersUidCostAdditionalPut') }}" class="btn">WebordersUidCostAdditionalPut</a>
        <a type="button" href="{{ route('taxi-webordersUidCostAdditionalDelete') }}" class="btn">WebordersUidCostAdditionalDelete</a>
        <a type="button" href="{{ route('taxi-webordersDrivercarPosition') }}" class="btn">WebordersDrivercarPosition</a>
        <a type="button" href="{{ route('taxi-webordersCancel') }}" class="btn">WebordersCancel</a>
        <a type="button" href="{{ route('taxi-webordersRate') }}" class="btn">WebordersRate</a>
        <a type="button" href="{{ route('taxi-webordersHide') }}" class="btn">WebordersHide</a>


    </div>
    <div class="container">
        <h3>Client section</h3>
        <a type="button" href="{{ route('taxi-ordersReport') }}" class="btn">OrdersReport</a>
        <a type="button" href="{{ route('taxi-ordersHistory') }}" class="btn">OrdersHistory</a>
        <a type="button" href="{{ route('taxi-ordersBonusreport') }}" class="btn">OrdersBonusreport</a>
        <a type="button" href="{{ route('taxi-profile') }}" class="btn">Profile</a>
        <a type="button" href="{{ route('taxi-lastaddresses') }}" class="btn">Lastaddresses</a>
        <a type="button" href="{{ route('taxi-profile-put') }}" class="btn">Profile put</a>
        <a type="button" href="{{ route('taxi-credential') }}" class="btn">Credential</a>
        <a type="button" href="{{ route('taxi-changePhoneSendConfirmCode') }}" class="btn">ChangePhoneSendConfirmCode</a>
        <a type="button" href="{{ route('taxi-clientsChangePhone') }}" class="btn">ClientsChangePhone</a>
        <a type="button" href="{{ route('taxi-clientsBalanceTransactions') }}" class="btn">ClientsBalanceTransactions</a>
        <a type="button" href="{{ route('taxi-clientsBalanceTransactionsGet') }}" class="btn">ClientsBalanceTransactionsGet</a>
        <a type="button" href="{{ route('taxi-clientsBalanceTransactionsGetHistory') }}" class="btn">ClientsBalanceTransactionsGetHistory</a>
        <a type="button" href="{{ route('taxi-addresses') }}" class="btn">Addresses</a>
        <a type="button" href="{{ route('taxi-addressesPost') }}" class="btn">AddressesPost</a>
        <a type="button" href="{{ route('taxi-addressesPut') }}" class="btn">AddressesPut</a>
        <a type="button" href="{{ route('taxi-addressesDelete') }}" class="btn">AddressesDelete</a>




    </div>
    <div class="container">
    <h3>Geodata section</h3>
        <a type="button" href="{{ route('taxi-objects') }}" class="btn">Objects</a>
        <a type="button" href="{{ route('taxi-objectsSearch') }}" class="btn">ObjectsSearch</a>
        <a type="button" href="{{ route('taxi-streets') }}" class="btn">Streets</a>
        <a type="button" href="{{ route('taxi-streetsSearch') }}" class="btn">StreetsSearch</a>
        <a type="button" href="{{ route('taxi-geodataSearch') }}" class="btn">GeodataSearch</a>
        <a type="button" href="{{ route('taxi-geodataSearchLatLng') }}" class="btn">GeodataSearchLatLng</a>
        <a type="button" href="{{ route('taxi-geodataNearest') }}" class="btn">GeodataNearest</a>
        <a type="button" href="{{ route('taxi-driversPosition') }}" class="btn">DriversPosition</a>
    </div>

    <div class="container">
    <h3>Setting section</h3>
        <a type="button" href="{{ route('taxi-settings') }}" class="btn">Settings</a>
        <a type="button" href="{{ route('taxi-addCostIncrementValue') }}" class="btn">AddCostIncrementValue</a>
        <a type="button" href="{{ route('taxi-time') }}" class="btn">Time</a>
        <a type="button" href="{{ route('taxi-tnVersion') }}" class="btn">TnVersion</a>
    </div>-->
@endsection
