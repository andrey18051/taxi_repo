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
                                <strong>Гугл аккаунт:</strong> {{ $params['driver_uid'] }} <br>
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
                <p class="text-center h5">Заявка на вывод средств с баланса</p>
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('return-amount-save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="name" value="{{ $params['name'] }}">
                            <input type="hidden" name="phoneNumber" value="{{ $params['phoneNumber'] }}">
                            <input type="hidden" name="email" value="{{ $params['email'] }}">
                            <input type="hidden" name="driver_uid" value="{{ $params['driver_uid'] }}">
                            <input type="hidden" name="driverNumber" value="{{ $params['driverNumber'] }}">
                            <input type="hidden" name="balance_current" value="{{ $params['balance_current'] }}">
                            <input type="hidden" name="order_to_return" value="{{ $params['order_to_return'] }}">
                            <input type="hidden" name="selectedTypeCode" value="{{ $params['selectedTypeCode'] }}">
                            <input type="hidden" name="order_to_return_date" value="{{ $params['order_to_return_date'] }}">

                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-md-6"> <!-- Уменьшаем ширину формы -->
                                        <div class="mb-3">
                                            <label for="amount_to_return_admin" class="form-label">Сумма к возврату</label>
                                            <input type="text" id="amount_to_return_admin" name="amount_to_return_admin" autocomplete="off" class="form-control text-end" value="{{ $params['amount_to_return'] }}" />
                                        </div>

                                        <div class="mb-3">
                                            <label for="code_verify" class="form-label">Код подтверждения</label>
                                            <input type="text" id="code_verify" name="code_verify" autocomplete="off" class="form-control text-end" />
                                        </div>

                                        <!-- Submit button -->
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-primary">
                                                Вернуть
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
