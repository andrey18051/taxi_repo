@extends('layouts.admin')

@section('content')
    <div class="container mt-4"> <!-- Добавляем отступ сверху -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center"> <!-- Выровняем заголовок по центру -->
                        {{ __('Dashboard') }}
                    </div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <p class="text-center">{{ __('You are logged in!') }}</p> <!-- Выровняем текст по центру -->

                        <!-- Параметры -->
                        <div class="row">
                            <div class="col-6">
                                <strong>Имя:</strong> {{ $params['name'] }} <br>
                                <strong>Телефон:</strong> {{ $params['phoneNumber'] }} <br>
                                <strong>Email:</strong> {{ $params['email'] }} <br>
                                <strong>Позывной:</strong> {{ $params['driverNumber'] }} <br>
                            </div>
                            <div class="col-6">
                                <h1><strong>Текущий баланс:</strong> {{ $params['balance_current'] }} </h1><br>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Номер заявки:</strong> {{ $params['order_to_return'] }} <br>
                                <strong>Способ возврата:</strong> {{ $params['selectedTypeCode'] }} <br>
                                <strong>Дата заявки:</strong> {{ $params['order_to_return_date'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Форма -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <p class="text-center h5">Заявка на вывод средств с баланса выполнена</p>
            </div>
        </div>
    </div>
@endsection
