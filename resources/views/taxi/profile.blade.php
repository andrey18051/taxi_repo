@extends('layouts.logout')

@section('content')
<section style="background-color: #eee;">
    <div class="container py-5">
        <div class="row">
            <div class="col">
                <nav aria-label="breadcrumb" class="bg-light rounded-3 p-3 mb-4">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/">Нове замовлення</a></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="{{ asset('img/logo-profile.jpg')}}" alt="avatar"
                             class="rounded-circle img-fluid" style="width: 150px;">
                        <h5 class="my-3">
                            {{$response["user_first_name"]}}
                            {{$response["user_last_name"]}}
                        </h5>
                    <div class="d-flex justify-content-center mb-2">
                            <a type="button" class="btn btn-primary"
                               href="{{ route('profile-edit-form', $authorization) }}">Оновити</a>
                            <a type="button" class="btn btn-outline-primary ms-1"
                               href="{{ route('costhistory', $authorization) }}">Мои маршруты</a>
                        </div>
                    </div>
                </div>
                <div class="card mb-4 mb-lg-0">
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush rounded-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <i class="fas fa-globe fa-lg text-warning"></i>
                                <p class="mb-0">Ідентіфікатор <b>{{$response["id"]}}</b></p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Повне ім'я</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    {{$response["user_last_name"]}}
                                    {{$response["user_first_name"]}}
                                    {{$response["user_middle_name"]}}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Телефон</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{$response["user_phone"]}}</p>
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Адреса</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    {{$response["route_address_from"]}},
                                    будинок  {{$response["route_address_number_from"]}},
                                    квартира {{$response["route_address_entrance_from"]}},
                                    під'їзд {{$response["route_address_apartment_from"]}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4 mb-md-0">
                            <div class="card-body">
                                <p class="mb-4"><span class="text-primary font-italic me-1">Фінансова інформація</span></p>
                                <p class="mb-1">Текущий баланс: <b>{{$response["user_balance"]}} грн</b></p>
                                @if($response["payment_type"] == "0")
                                    <p class="mb-1" style="font-size: .77rem;">Категорія оплати користувача: готівка</p>
                                @else
                                    <p class="mb-1" style="font-size: .77rem;">Категорія оплати користувача: безготівка</p>
                                @endif
                                <p class="mb-1" style="font-size: .77rem;">Корпоративний рахунок: {{$response["corporate_account"]}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 mb-md-0">
                            <div class="card-body">
                                <p class="mb-4"><span class="text-primary font-italic me-1">Активність користування сервісом</span></p>
                                <p class="mb-1" style="font-size: .77rem;">Усього заказів: {{$response["orders_count"]}}</p>
                                <p class="mb-1" style="font-size: .77rem;">Усього бонусів: {{$response["client_bonuses"]}} грн</p>
                                <p class="mb-1" style="font-size: .77rem;">Знижка: {{$response["discount"]["value"]}} {{$response["discount"]["unit"]}}</p>
                                <div class="progress rounded" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{$response["discount"]["value"]}}%" aria-valuenow="{{$response["discount"]["value"]}}"
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</section>

@endsection
