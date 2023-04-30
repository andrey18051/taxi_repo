@extends('layouts.widgets')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset


    <form action="{{ route('widgets-getInfo') }}">
            @csrf

       <section class="">
        <!-- Jumbotron -->
        <div class="text-center text-lg-start container">
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
                                        Інформація про послугу
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card">
                                            <div class="card-body">
                                                 <div class="container">
                                                     <div class="row">
                                                         <p class="gradient">
                                                         За допомогою програми або анкети вище можна зробити легкий крок для роботи на авто і відправити запит до служб як кандидата на співпрацю.
                                                         </p>
                                                         <p class="gradient">
                                                         Авто дозволяє працювати в будь-який зручний для Вас час без спеціальних умов, обов'язкових замовлень.
                                                         </p>
                                                         <p class="gradient">
                                                         Сервіс для тих, хто хоче заробити автомобілем, з попутниками, окупити витрати на його утримання або розпочати власний бізнес у сфері перевезень. Робота з кількома фірмами без прив'язки до офісу та за вільним графіком.
                                                         </p>
                                                         <p class="gradient">
                                                         З нами вже працюють автомобілі наступних таксі:
                                                                @foreach($services as $value)
                                                                    {{$value['name']}}
                                                                @endforeach
                                                         </p>

                                                         <p class="gradient">
                                                         Сервіси працюють у всіх містах України. Ось кілька із них: Київ, Харків, Одеса, Дніпро, Запоріжжя, Вінниця, Полтава, Суми, Черкаси, Кропивницький. Херсон, Житомир, Чернігів, Ужгород, Львів, Тернопіль, Івано-Франківськ, Рівне, Вінниця, Чернівці, Луцьк, Хмельницький
                                                         </p>
                                                         <p class="gradient">
                                                         Переваги роботи водієм на авто:
                                                         </p>
                                                         <p class="gradient">
                                                          выбор из списка видов маршрутов разрешенные для Вас к выполнению после регистрации;
                                                          фільтрація замовлень в ефірі за радіусом, довжиною маршруту та ціною за кілометр;
                                                          доступ до інформації про замовлення: всі точки маршруту, вартість поїздки, тип оплати, відстань до точки та за маршрутом;
                                                          Доступ до інформації про клієнта: ім'я, телефон;
                                                          відображення маршруту до клієнта та самого замовлення;
                                                          перехід у будь-який Ваш навігатор за будь-якими маршрутами клієнтаж;
                                                          щоденне виведення коштів на картку за запитом;
                                                          історія транзакцій особистого балансу;
                                                          актуальні оповіщення про зміни та нововведення;
                                                          статистика замовлень по секторам онлайн;
                                                          можливість вибору графічного оформлення та звукового оповіщення;
                                                          можливість відображення нових замовлень поверх усіх додатків;
                                                          служба підтримки 24/7;
                                                          у надзвичайних ситуаціях можна покликати на допомогу інших водіїв, використовуючи тривожну кнопку.
                                                         </p>
                                                         <p class="gradient">
                                                         Влаштуватись у сервіс Драйвер дуже просто! Для цього необхідно встановити програму та заповнити дані в ньому або на цій формі. А після того, як заявку буде оброблено, прийде оповіщення, і можна приступати до роботи!
                                                         </p>
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
                                        Служби таксі (Має бути зазначена хоча б одна служба таксі.)
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="row">

                                                    <div class="col-6">

                                                        @for($i = 0; $i< count($services)/2; $i++)
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="{{$services[$i]['name']}}" name="{{$services[$i]['name']}}"
                                                                       @isset($params[$services[$i]['name']])
                                                                       @if($params[$services[$i]['name']] == "on")
                                                                       checked
                                                                       @endif
                                                                       @endisset

                                                                       @if($services[$i]['name'] == "Термінал")
                                                                       checked
                                                                       @endif>
                                                                <label class="form-check-label" for="{{$services[$i]['name']}}">
                                                                    {{$services[$i]['name']}}
                                                                </label>
                                                            </div>
                                                        @endfor


                                                    </div>
                                                    <div class="col-6">

                                                        @for($i = count($services)/2 ; $i< count($services); $i++)
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"   id="{{$services[$i]['name']}}" name="{{$services[$i]['name']}}"
                                                                       @isset($params[$services[$i]['name']])
                                                                       @if($params[$services[$i]['name']] == "on")
                                                                       checked
                                                                       @endif
                                                                       @endisset
                                                                       @if($services[$i]['name'] == "Термінал")
                                                                       checked
                                                                       @endif>
                                                                <label class="form-check-label" for="{{$services[$i]['name']}}">
                                                                    {{$services[$i]['name']}}
                                                                </label>
                                                            </div>
                                                        @endfor


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
                                        Інформація про автомобіль (Поля позначені * – обов'язкові до заповнення)
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card-body">
                                            <div class="form-outline mb-2 col-12"  >
                                                <div class="row">
                                                    <div class="row mb-3">
                                                        <label for="brand" class="col-md-4 col-form-label text-md-end">{{ __("Марка*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="brand" name="brand" type="text" class="form-control" autofocus
                                                                   @isset($params["brand"])
                                                                   value="{{$params["brand"]}}"
                                                                @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="model" class="col-md-4 col-form-label text-md-end">{{ __("Модель*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="model" name="model" type="text" class="form-control"
                                                                   @isset($params["model"])
                                                                   value="{{$params["model"]}}"
                                                                @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="type" class="col-md-4 col-form-label text-md-end">{{ __("Тип кузова*") }}</label>

                                                        <div class="col-md-6">
                                                            <select class="form-select" id="type" name="type"  >
                                                                @isset($params["type"])
                                                                    <option value="{{$params["type"]}}">{{$params["type"]}}</option>
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
                                                        <label for="color" class="col-md-4 col-form-label text-md-end">{{ __("Колір*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="color" name="color" type="text" class="form-control"
                                                                   @isset($params["color"])
                                                                   value="{{$params["color"]}}"
                                                                @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="year" class="col-md-4 col-form-label text-md-end">{{ __("Рік випуску*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="year" name="year" type="text" class="form-control"
                                                                   @isset($params["year"])
                                                                   value="{{$params["year"]}}"
                                                                @endisset>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="number" class="col-md-4 col-form-label text-md-end">{{ __("Державний номер*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="number" name="number" type="text" class="form-control"
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
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Контактна інформація  (Поля позначені * – обов'язкові до заповнення)
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="card-body">
                                            <div class="form-outline mb-2 col-12"  >
                                                <div class="row">
                                                    <div class="row mb-3">
                                                        <label for="city" class="col-md-4 col-form-label text-md-end">{{ __("Місто*") }}</label>

                                                        <div class="col-md-6">
                                                            <select class="form-select" id="city" name="city" autofocus  >
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
                                                        <label for="first_name" class="col-md-4 col-form-label text-md-end">{{ __("Ім'я*") }}</label>

                                                        <div class="col-md-6">
                                                            <input id="first_name" name="first_name" type="text" class="form-control"
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
                                                        <label for="email" class="col-md-4 col-form-label text-md-end">{{ __("Електронна пошта (Бажано)") }}</label>

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
                                                            <label for="phone" class="col-md-4 col-form-label text-md-end">{{ __("Телефон*") }}</label>

                                                            <div class="col-md-6">
                                                                <input id="phone" type="text"
                                                                        class="form-control @error('phone') is-invalid @enderror"
                                                                        name="phone"

                                                                        @isset($params["phone"])
                                                                        value="{{$params["phone"]}}"
                                                                        @else
                                                                           value="{{ old('phone') }}"   autocomplete="user_phone"
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

{{--                    <a href="{{route("getInfo")}}" class="btn btn-danger col-12" style="margin-top: 5px">--}}
{{--                        Очистити--}}
{{--                    </a>--}}

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
