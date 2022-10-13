@extends('layouts.taxiNewProfile')

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
                        <li class="breadcrumb-item"><a href="{{route('home-phone-user_name',
                            [substr($response["user_phone"], 3), $response["user_first_name"] . " " . $response["user_last_name"]])
                            }}"
                                class="btn btn-outline-success" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                    <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                    <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                </svg>

                                Нове замовлення</a>
                        </li>
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
