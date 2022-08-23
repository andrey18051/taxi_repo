@extends('layouts.logout')

@section('content')

<section style="background-color: #eee;">
    <form action="{{ route('profile-edit') }}">
        @csrf
        <input type="hidden" class="form-control" name="authorization" value="{{$authorization}}">
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

                            <button type="submit" class="btn btn-primary">
                                Зберегти
                            </button>
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
                                <p class="mb-0">Ім'я</p>
                            </div>
                            <div class="col-sm-9">
                                <input type="text" class="form-control text-muted mb-0" name="user_first_name" id="user_first_name" value="{{$response["user_first_name"]}}">
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">По-батькові</p>
                            </div>
                            <div class="col-sm-9">
                                <input type="text" class="form-control text-muted mb-0" name="user_middle_name" id="user_middle_name" value="{{$response["user_middle_name"]}}">
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Прізвище</p>
                            </div>
                            <div class="col-sm-9">
                                <input type="text" class="form-control text-muted mb-0" name="user_last_name" id="user_last_name" value="{{$response["user_last_name"]}}">
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Вулиця:</p>
                            </div>
                            <div class="col-sm-9">
                                 <input type="text" class="form-control text-muted mb-0" name="route_address_from" id="route_address_from" value="{{$response["route_address_from"]}}">
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Будинок</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    <input type="text" class="form-control" name="route_address_number_from" id="route_address_number_from" value="{{$response["route_address_number_from"]}}">
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Квартира</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    <input type="text" class="form-control" name="route_address_entrance_from" id="route_address_entrance_from" value="{{$response["route_address_entrance_from"]}}">
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Під'їзд</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    <input type="text" class="form-control" name="route_address_apartment_from" id="route_address_apartment_from" value="{{$response["route_address_apartment_from"]}}">
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
</section>
@endsection
