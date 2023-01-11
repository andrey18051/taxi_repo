@extends('layouts.taxiNewCombo')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Реєстрація') }}</div>

                <div class="card-body">

                    <div class="col-md-8 offset-md-4 flex items-center justify-end mt-4">

                       <span class="gradient">Скористатися</span>
                        <a type="button"   href="{{ url('auth/telegram') }}" title="Авторизуватися через Telegram">

                            <img src="{{ asset('img/icons8-telegram-app-48.png') }}">
                        </a>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
