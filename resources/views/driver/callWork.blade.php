@extends('layouts.taxiNewCombo')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset

    {{--@isset($params)

            {{dd($params)}}

    @endisset--}}

    <!-- Section: Design Block -->


    <form action="{{ route('getInfo') }}">
            @csrf

       <section class="">
        <!-- Jumbotron -->
        <div class="text-center text-lg-start container" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="container">
                <div class="row justify-content-center">
                    <p class="lead text-center gradient">
                        Анкета водія
                    </p>

                    <a   style="width: 200px" href="https://play.google.com/store/apps/details?id=com.taxieasyua.job">
                        <img src="{{ asset('img/google-play-badge.png') }}" style="width: 200px; height: auto; margin-top: -15px"
                             title="Додаток Android" />
                    </a>

                    <div class="col-md-12">

                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Служби таксі
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card">
                                            <div class="card-body">
                                                 <div class="container">
                                                     <div class="row">
                                                         <div class="col-6">
                                                             <div class="form-check">
                                                                 <input class="form-check-input" type="checkbox"   id="Terminal" name="Terminal"
                                                                        @isset($params["Terminal"])
                                                                            @if($params["Terminal"] == "on")
                                                                            checked
                                                                            @endif
                                                                        @endisset
                                                                        checked>
                                                                 <label class="form-check-label" for="Terminal">
                                                                     Терминал
                                                                 </label>
                                                             </div>
                                                             <div class="form-check">
                                                                 <input class="form-check-input" type="checkbox"   id="TaxiEasyUa" name="TaxiEasyUa"
                                                                        @isset($params["TaxiEasyUa"])
                                                                            @if($params["TaxiEasyUa"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                 <label class="form-check-label" for="TaxiEasyUa">
                                                                     Таксі Лайт Юа
                                                                 </label>
                                                             </div>
                                                             <div class="form-check">
                                                                 <input class="form-check-input" type="checkbox"   id="UBER" name="UBER"
                                                                        @isset($params["UBER"])
                                                                            @if($params["UBER"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                 <label class="form-check-label" for="UBER">
                                                                     UBER
                                                                 </label>
                                                             </div>
                                                             <div class="form-check">
                                                                 <input class="form-check-input" type="checkbox"   id="UKLON" name="UKLON"
                                                                        @isset($params["UKLON"])
                                                                            @if($params["UKLON"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                 <label class="form-check-label" for="UKLON">
                                                                     UKLON
                                                                 </label>
                                                             </div>
                                                             <div class="form-check">
                                                                 <input class="form-check-input" type="checkbox"   id="BOLT" name="BOLT"
                                                                        @isset($params["BOLT"])
                                                                            @if($params["BOLT"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                 <label class="form-check-label" for="BOLT">
                                                                     BOLT
                                                                 </label>
                                                             </div>


                                                         </div>

                                                        <div class="col-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="OnTaxi" name="OnTaxi"
                                                                        @isset($params["OnTaxi"])
                                                                            @if($params["OnTaxi"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                <label class="form-check-label" for="OnTaxi">
                                                                    OnTaxi
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="taxi_838" name="taxi_838"
                                                                        @isset($params["taxi_838"])
                                                                            @if($params["taxi_838"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                <label class="form-check-label" for="taxi_838">
                                                                    838
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="Lubimoe_Taxi" name="Lubimoe_Taxi"
                                                                        @isset($params["Lubimoe_Taxi"])
                                                                            @if($params["Lubimoe_Taxi"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                <label class="form-check-label" for="Lubimoe_Taxi">
                                                                    Lubimoe Taxi
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="taxi_3040" name="taxi_3040"
                                                                        @isset($params["taxi_3040"])
                                                                            @if($params["taxi_3040"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                <label class="form-check-label" for="taxi_3040">
                                                                    3040
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="Maxim" name="Maxim"
                                                                        @isset($params["Maxim"])
                                                                            @if($params["Maxim"] == "on")
                                                                                checked
                                                                            @endif
                                                                        @endisset>
                                                                <label class="form-check-label" for="Maxim">
                                                                    Максім
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Інформація про автомобіль
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card-body">
                                            <div class="form-outline mb-2 col-12"  >
                                                <div class="row">
                                                    <div class="row mb-3">
                                                        <label for="brand" class="col-md-4 col-form-label text-md-end">{{ __("Марка") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="brand" name="brand" type="text" class="form-control" autofocus required
                                                                    @isset($params["brand"])
                                                                        value="{{$params["brand"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="model" class="col-md-4 col-form-label text-md-end">{{ __("Модель") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="model" name="model" type="text" class="form-control" required
                                                                    @isset($params["model"])
                                                                        value="{{$params["model"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="type" class="col-md-4 col-form-label text-md-end">{{ __("Тип кузова") }}</label>

                                                        <div class="col-md-6">
                                                            <select class="form-select" id="type" name="type" required>
                                                                @isset($params["type"])
                                                                    <option value="{{$params["brand"]}}">{{$params["type"]}}</option>
                                                                @endisset
                                                                <option value="седан">седан</option>
                                                                <option value="універсал">універсал</option>
                                                                <option value="хетчбек">хетчбек</option>
                                                                <option value="мікроавтобус">мікроавтобус</option>
                                                                <option value="мінівен">мінівен</option>
                                                                <option value="позашляховик">позашляховик</option>
                                                                <option value="вантажний (менше 4 посадочних місць)">вантажний (менше 4 посадочних місць)</option>
                                                                <option value="купе">купе</option>
                                                                <option value="кабріолет">кабріолет</option>
                                                                <option value="пікап">пікап</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="color" class="col-md-4 col-form-label text-md-end">{{ __("Колір") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="color" name="color" type="text" class="form-control" required
                                                                    @isset($params["color"])
                                                                        value="{{$params["color"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="year" class="col-md-4 col-form-label text-md-end">{{ __("Рік випуску") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="year" name="year" type="text" class="form-control" required
                                                                    @isset($params["year"])
                                                                        value="{{$params["year"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="number" class="col-md-4 col-form-label text-md-end">{{ __("Державний номер") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="number" name="number" type="text" class="form-control" required
                                                                    @isset($params["number"])
                                                                        value="{{$params["number"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Контактна інформація
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card-body">
                                            <div class="form-outline mb-2 col-12"  >
                                                <div class="row">
                                                    <div class="row mb-3">
                                                        <label for="city" class="col-md-4 col-form-label text-md-end">{{ __("Місто") }}</label>

                                                        <div class="col-md-6">
                                                            <select class="form-select" id="city" name="city" autofocus required>
                                                                @isset($params["city"])
                                                                    <option value="{{$params["city"]}}">{{$params["city"]}}</option>
                                                                @endisset
                                                                <option value="Київ">Київ</option>
                                                                <option value="Вінниця">Вінниця</option>
                                                                <option value="Дніпро">Дніпро</option>
                                                                <option value="Донецьк">Донецьк</option>
                                                                <option value="Житомир">Житомир</option>
                                                                <option value="Запоріжжя">Запоріжжя</option>
                                                                <option value="Івано-Франківськ">Івано-Франківськ</option>
                                                                <option value="Кропивницький">Кропивницький</option>
                                                                <option value="Луганськ">Луганськ</option>
                                                                <option value="Луцьк">Луцьк</option>
                                                                <option value="Львів">Львів</option>
                                                                <option value="Миколаїв">Миколаїв</option>
                                                                <option value="Одеса">Одеса</option>
                                                                <option value="Полтава">Полтава</option>
                                                                <option value="Рівне">Рівне</option>
                                                                <option value="Суми">Суми</option>
                                                                <option value="Тернопіль">Тернопіль</option>
                                                                <option value="Ужгород">Ужгород</option>
                                                                <option value="Харків">Харків</option>
                                                                <option value="Херсон">Херсон</option>
                                                                <option value="Хмельницький">Хмельницький</option>
                                                                <option value="Черкаси">Черкаси</option>
                                                                <option value="Чернігів">Чернігів</option>
                                                                <option value="Чернівці">Чернівці</option>
                                                            </select>

                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="first_name" class="col-md-4 col-form-label text-md-end">{{ __("Ім'я") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="first_name" name="first_name" type="text" class="form-control" required
                                                                    @isset($params["first_name"])
                                                                        value="{{$params["first_name"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="second_name" class="col-md-4 col-form-label text-md-end">{{ __("Прізвище") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="second_name" name="second_name" type="text" class="form-control"
                                                                    @isset($params["second_name"])
                                                                        value="{{$params["second_name"]}}"
                                                                    @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="email" class="col-md-4 col-form-label text-md-end">{{ __("Електронна пошта") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="email" type="email"
                                                                    class="form-control @error('email') is-invalid @enderror"
                                                                    name="email"
                                                                    @isset($params["email"])
                                                                        value="{{$params["email"]}}"
                                                                    @else
                                                                        value="{{ old('email') }}" autocomplete="email"
                                                                    @endisset

                                                                    placeholder="andrey@gmail.com"
                                                                    title="Формат вводу: andrey@gmail.com">

                                                            @error('email')
                                                            <span class="invalid-feedback" role="alert">
                                                                <strong>{{ $message }}</strong>
                                                            </span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                            <label for="phone" class="col-md-4 col-form-label text-md-end">{{ __("Телефон") }}</label>

                                                            <div class="col-md-6">
                                                                <input id="phone" type="text"
                                                                        class="form-control @error('phone') is-invalid @enderror"
                                                                        name="phone"

                                                                        @isset($params["phone"])
                                                                        value="{{$params["phone"]}}"
                                                                        @else
                                                                           value="{{ old('phone') }}" required autocomplete="user_phone"
                                                                        @endisset

                                                                        pattern="[\+]\d{12}"
                                                                        placeholder="+380936665544"
                                                                        title="Формат вводу: +380936665544"
                                                                        minlength="13"
                                                                        maxlength="13">

                                                                @error('phone')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            <br>
            <div class="container">
                <div class="row">
                    <script defer src="https://www.google.com/recaptcha/api.js"></script>
                    <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                </div>

                <!-- Submit button -->
                <div class="row">

                    <button type="reset" class="btn btn-danger col-12" style="margin-top: 5px">
                        Очистити
                    </button>

                    <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                        Надіслати
                    </button>
                </div>
                    </div>
        <!-- Jumbotron -->
                    <br>
        </div>
    </section>

        </form>
@endsection
