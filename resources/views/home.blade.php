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

        <div class="card-deck mb-3 text-center">
            <div class="row  align-items-center">
                <div class="col-md-3 card mb-4   ">
                    <div class="card-header">
                        <h4 class="my-0 font-weight-normal">Office 1.00</h4>
                    </div>
                    <div class="card-body">
                        <h1 class="card-title pricing-card-title"> <small class="text-muted"></small></h1>
                        <ol>01.07.2020 - 31.03.2021</ol>
                        <a type="button" href="http://office-1-00"  class="btn btn-lg btn-block btn-primary">Get started</a>
                    </div>
                </div>
                <div class="offset-md-1 col-md-3 card mb-4  ">
                    <div class="card-header">
                        <h4 class="my-0 font-weight-normal">Office 2.00-2021</h4>
                    </div>
                    <div class="card-body">
                        <h1 class="card-title pricing-card-title"> <small class="text-muted"></small></h1>
                        <ol>01.04.2021 - 31.12.2021</ol>
                        <a type="button" href="http://office-2-00-2021" class="btn btn-lg btn-block btn-primary">Get started</a>
                    </div>
                </div>
                <div class="offset-md-1 col-md-3 card mb-4  ">
                    <div class="card-header">
                        <h4 class="my-0 font-weight-normal">Office 2.00-2022</h4>
                    </div>
                    <div class="card-body">
                        <h1 class="card-title pricing-card-title"> <small class="text-muted"></small></h1>
                        <ol>01.12.2022 - 31.12.2022</ol>
                        <a type="button" href="http://www.korzhov-office.ru" class="btn btn-lg btn-block btn-outline-primary">Get started</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="container">
        <a type="button" href="{{ route('taxi-version') }}" class="btn">Version</a>
        <a type="button" href="{{ route('taxi-profile') }}" class="btn">Profile</a>
        <a type="button" href="{{ route('taxi-addresses') }}" class="btn">Addresses</a>
        <a type="button" href="{{ route('taxi-lastaddresses') }}" class="btn">Lastaddresses</a>
        <a type="button" href="{{ route('taxi-tariffs') }}" class="btn">Tariffs</a>
        <a type="button" href="{{ route('taxi-ordershistory') }}" class="btn">Ordershistory</a>
        <a type="button" href="{{ route('taxi-ordersreport') }}" class="btn">Ordersreport</a>
        <a type="button" href="{{ route('taxi-bonusreport') }}" class="btn">Bonusreport</a>
        <a type="button" href="{{ route('taxi-profile-put') }}" class="btn">Profile put</a>
    </div>
@endsection
