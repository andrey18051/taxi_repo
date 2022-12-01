@extends('layouts.taxiNewCombo')

@section('content')

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <!--     Пошук за адресою.-->
        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_street">
            <div class="container" style="text-align: center">
                <h2 class="gradient"><b>Київ та область</b></h2>
                <p class="text-center gradient">Заповнити поля для розрахунку вартості поїздки.</p>
            </div>
            <div class="container text-center">
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Аэропорт Борисполь терминал Д", "taxi.transferBorispol"])}}">
                            <img src="{{ asset('img/borispol.png') }}" style="width: 150px; height: auto"
                                 title="Трансфер до аеропорту Бориспіль">
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Аэропорт Жуляны новый (ул.Медовая 2)", "taxi.transferJulyany"])}}">
                            <img src="{{ asset('img/sikorskogo.png') }}" style="width: 150px; height: auto"
                                 title="Трансфер до аеропорту Київ">
                        </a>
                    </div>
                    <br>
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["ЖД Южный", "taxi.transferUZ"])}}">
                            <img src="{{ asset('img/UZ.png') }}" style="width: 150px; height: auto"
                                 title="Трансфер до залізничного вокзалу">
                        </a>
                    </div>

                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferAuto"])}}">
                            <img src="{{ asset('img/auto.jpeg') }}" style="width: 150px; height: auto"
                                 title="Трансфер до автовокзалу">
                        </a>
                    </div>
                </div>
            </div>
            <br>
            <form action="{{route('search-cost')}}" id="form">
                @csrf
                <div class="row">
                    <div class="col-md-7 col-lg-8">
                        @guest
                            <input type="hidden" id="user_phone" name="user_phone"  value="+380936665544">
                            <input type="hidden" id="user_full_name" name="user_full_name"  value="Гість">
                        @else
                            <input type="hidden"  id="user_phone" name="user_phone" value="{{Auth::user()->user_phone}}">
                            <input type="hidden" id="user_full_name" name="user_full_name"   value="{{Auth::user()->name}}">
                        @endguest

                        <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                        <input type="hidden" id="comment" name="comment" placeholder="Додати побажання" />

                        <div class="container">
                                <div class="row">
                                    <div class="col-lg-8 col-12">
                                        <input type="text"
                                               id="search"
                                               class="form-control @error('search') is-invalid @enderror"
                                               name="search" value="{{ old('search') }}"
                                               placeholder="Звідки?"
                                               onblur="hidFrom(this.value)"
                                               autocomplete="off"
                                               required>

                                        @error('search')
                                        <span class="invalid-feedback" role="alert">
                                             <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="col-lg-4 col-12" id="div_from_number">
                                        <input type="text" id="from_number" name="from_number"
                                               class="form-control @error('from_number') is-invalid @enderror"
                                               value="{{ old('from_number') }}"
                                               placeholder="Будинок?"
                                               autocomplete="off"
                                               style="text-align: center" >
                                        @error('from_number')
                                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ 'Це поле обов`язкове.' }}</strong>
                                                </span>
                                        @enderror
                                    </div>
                                </div>
                        </div>


                        <div class="container" style="text-align: left">
                            <label class="form-check-label" for="route_undefined">По місту</label>
                            <input type="checkbox" class="form-check-input" id="route_undefined"
                                   name="route_undefined" value="1"
                                   onclick="showHide('block_city')">
                        </div>

                        <div id="block_city" class="container"  style="display:block">
                                <div class="row">
                                    <div class="col-lg-8 col-12">
                                        <input type="text" id="search1" name="search1"
                                               class="form-control @error('search1') is-invalid @enderror"
                                               value="{{ old('search1') }}"
                                               placeholder="Куди?"
                                               autocomplete="off"
                                               onblur="hidTo(this.value)">

                                        @error('search1')
                                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                                </span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 col-12" id="div_to_number">
                                        <input type="text" id="to_number" name="to_number"
                                               class="form-control @error('to_number') is-invalid @enderror"
                                               value="{{ old('to_number') }}"
                                               placeholder="Будинок?"
                                               autocomplete="off"
                                               style="text-align: center" >
                                        @error('to_number')
                                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ 'Це поле обов`язкове.' }}</strong>
                                                </span>
                                        @enderror
                                    </div>
                                </div>
                        </div>

                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                        <div class="container" style="margin-top: 5px">
                             <div class="row">
                                    <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                             </div>
                       </div>
                       <br>
                    </div>

                    <div class="col-sm-5 col-lg-4" style="margin-top: 5px">

                        <a href="javascript:void(0)" class="w-100 btn btn-outline-success"
                               onclick="showHide('block_id')">Додаткові параметри</a><br/>
                        <div class="container">
                            <div id="block_id" style="display: none;">
                                <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <label class="form-label" for="required_time">Час подачі</label>
                                    <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="null">
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="wagon" name="wagon">
                                        <label class="form-check-label" for="wagon">Универсал</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="minibus" name="minibus">
                                        <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="premium" name="premium">
                                        <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тариф</label>
                                        <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" >
                                            <option></option>
                                            @for ($i = 0; $i < count($json_arr); $i++)
                                                <option>{{$json_arr[$i]['name']}}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тип оплати замовлення</label>
                                        <select class="form-select" id="flexible_tariff_name" name="payment_type" required>
                                            <option>готівка</option>
<!--                                            <option>безготівка</option>-->
                                        </select>
                                    </div>
                                </li>
                            </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container text-center">
                    <div class="row">
                        <div class="col-lg-6">
                            <button class="w-100 btn btn-danger btn-lg" href="{{route('homeCombo')}}" style="margin-top: 5px">Очистити форму</button>
                        </div>
                        <div class="col-lg-6">
                            <button class="w-100 btn btn-primary btn-lg" style="margin-top: 5px" type="submit">
                                Розрахувати вартість поїздки
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        <div class="container-fluid" style="margin-top: 10px">
            <div class="header gradient" >
                <b>Зустріч -> </b>
                <a class="borderElement" href="{{route('transferFrom',  ["Аэропорт Борисполь терминал Д", "taxi.transferFromBorispol"])}}" style="text-decoration: none" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-airplane" viewBox="0 0 16 16">
                        <path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849Zm.894.448C7.111 2.02 7 2.569 7 3v4a.5.5 0 0 1-.276.447l-5.448 2.724a.5.5 0 0 0-.276.447v.792l5.418-.903a.5.5 0 0 1 .575.41l.5 3a.5.5 0 0 1-.14.437L6.708 15h2.586l-.647-.646a.5.5 0 0 1-.14-.436l.5-3a.5.5 0 0 1 .576-.411L15 11.41v-.792a.5.5 0 0 0-.276-.447L9.276 7.447A.5.5 0 0 1 9 7V3c0-.432-.11-.979-.322-1.401C8.458 1.159 8.213 1 8 1c-.213 0-.458.158-.678.599Z"/>
                    </svg>
                    {{ __('Бориспіль') }}
                </a>

                <a class="borderElement" href="{{route('transferFrom', ["Аэропорт Жуляны новый (ул.Медовая 2)", "taxi.transferFromJulyany"])}}" style="text-decoration: none" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-airplane" viewBox="0 0 16 16">
                        <path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849Zm.894.448C7.111 2.02 7 2.569 7 3v4a.5.5 0 0 1-.276.447l-5.448 2.724a.5.5 0 0 0-.276.447v.792l5.418-.903a.5.5 0 0 1 .575.41l.5 3a.5.5 0 0 1-.14.437L6.708 15h2.586l-.647-.646a.5.5 0 0 1-.14-.436l.5-3a.5.5 0 0 1 .576-.411L15 11.41v-.792a.5.5 0 0 0-.276-.447L9.276 7.447A.5.5 0 0 1 9 7V3c0-.432-.11-.979-.322-1.401C8.458 1.159 8.213 1 8 1c-.213 0-.458.158-.678.599Z"/>
                    </svg>
                    {{ __('Жуляни') }}
                </a>

                <a class="borderElement" href="{{route('transferFrom', ["ЖД Южный", "taxi.transferFromUZ"])}}" style="text-decoration: none" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-train-lightrail-front" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6.5 0a.5.5 0 0 0 0 1h1v1.011c-1.525.064-3.346.394-4.588.655C1.775 2.904 1 3.915 1 5.055V13.5A2.5 2.5 0 0 0 3.5 16h9a2.5 2.5 0 0 0 2.5-2.5V5.055c0-1.14-.775-2.15-1.912-2.39-1.242-.26-3.063-.59-4.588-.654V1h1a.5.5 0 0 0 0-1h-3ZM8 3c-1.497 0-3.505.356-4.883.644C2.464 3.781 2 4.366 2 5.055V13.5a1.5 1.5 0 0 0 1.072 1.438c.028-.212.062-.483.1-.792.092-.761.2-1.752.266-2.682.038-.531.062-1.036.062-1.464 0-1.051-.143-2.404-.278-3.435a52.052 52.052 0 0 0-.07-.522c-.112-.798.42-1.571 1.244-1.697C5.356 4.199 6.864 4 8 4c1.136 0 2.645.2 3.604.346.825.126 1.356.9 1.244 1.697-.022.16-.046.335-.07.522C12.643 7.596 12.5 8.949 12.5 10c0 .428.024.933.062 1.464.066.93.174 1.92.266 2.682.038.31.072.58.1.792A1.5 1.5 0 0 0 14 13.5V5.055c0-.69-.464-1.274-1.117-1.41C11.505 3.354 9.497 3 8 3Zm3.835 11.266c.034.28.066.53.093.734H4.072a62.692 62.692 0 0 0 .328-3h2.246c.36 0 .704-.143.958-.396a.353.353 0 0 1 .25-.104h.292a.35.35 0 0 1 .25.104c.254.253.599.396.958.396H11.6c.068.808.158 1.621.236 2.266ZM6 13.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Zm0 0a.5.5 0 1 1 1 0 .5.5 0 0 1-1 0Zm3.5.5a.5.5 0 0 0 .5-.5.5.5 0 1 0 1 0 .5.5 0 0 0-1 0 .5.5 0 1 0-.5.5Zm-5.03-3h2.176a.353.353 0 0 0 .25-.104c.254-.253.599-.396.958-.396h.292c.36 0 .704.143.958.396a.353.353 0 0 0 .25.104h2.177c-.02-.353-.031-.692-.031-1 0-.927.104-2.051.216-3H4.284c.112.949.216 2.073.216 3 0 .308-.011.647-.03 1Zm-.315-5h7.69l.013-.096a.497.497 0 0 0-.405-.57C10.495 5.188 9.053 5 8 5s-2.495.188-3.453.334a.497.497 0 0 0-.405.57L4.155 6Z"/>
                    </svg>
                    {{ __('Південний вокзал') }}
                </a>

                <a  class="borderElement" href="{{route('transferFrom', ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferFromAuto"])}}" style="text-decoration: none" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-truck-front" viewBox="0 0 16 16">
                        <path d="M5 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-6-1a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H7Z"/>
                        <path fill-rule="evenodd" d="M4 2a1 1 0 0 0-1 1v3.9c0 .625.562 1.092 1.17.994C5.075 7.747 6.792 7.5 8 7.5c1.208 0 2.925.247 3.83.394A1.008 1.008 0 0 0 13 6.9V3a1 1 0 0 0-1-1H4Zm0 1h8v3.9c0 .002 0 .001 0 0l-.002.004a.013.013 0 0 1-.005.002h-.004C11.088 6.761 9.299 6.5 8 6.5s-3.088.26-3.99.406h-.003a.013.013 0 0 1-.005-.002L4 6.9c0 .001 0 .002 0 0V3Z"/>
                        <path fill-rule="evenodd" d="M1 2.5A2.5 2.5 0 0 1 3.5 0h9A2.5 2.5 0 0 1 15 2.5v9c0 .818-.393 1.544-1 2v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V14H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2a2.496 2.496 0 0 1-1-2v-9ZM3.5 1A1.5 1.5 0 0 0 2 2.5v9A1.5 1.5 0 0 0 3.5 13h9a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 12.5 1h-9Z"/>
                    </svg>
                    {{ __('Автовокзал') }}
                </a>

            </div>
        </div>

    </div>
    </div>

    <script defer type="text/javascript">
        /**
         * Функция Скрывает/Показывает блок
         * @author ox2.ru дизайн студия
         **/
        function showHide(element_id) {
            //Если элемент с id-шником element_id существует
            if (document.getElementById(element_id)) {
                //Записываем ссылку на элемент в переменную obj
                var obj = document.getElementById(element_id);
                //Если css-свойство display не block, то:
                if (element_id == "block_city") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
                if (element_id == "block_id") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
            }
            //Если элемент с id-шником element_id не найден, то выводим сообщение
            else alert("Элемент с id: " + element_id + " не найден!");
        }

        function hidFrom(value) {
            var route = "/autocomplete-search-combo-hid/" + value;

            $.ajax({
                url: route,         /* Куда пойдет запрос */
                method: 'get',             /* Метод передачи (post или get) */
                dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                success: function(data){   /* функция которая будет выполнена после успешного запроса.  */
                    if (data == 0) {
                          document.getElementById('div_from_number').style.display='none';
                    }
                    if (data == 1) {
                        document.getElementById('div_from_number').style.display='block';
                    }

                }
            });



        }

        function hidTo(value) {
            var route = "/autocomplete-search-combo-hid/" + value;

            $.ajax({
                url: route,         /* Куда пойдет запрос */
                method: 'get',             /* Метод передачи (post или get) */
                dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                success: function(data){   /* функция которая будет выполнена после успешного запроса.  */
                    if (data == 0) {
                        document.getElementById('div_to_number').style.display='none';
                    }
                    if (data == 1) {
                        document.getElementById('div_to_number').style.display='block';
                    }

                }
            });

        }

    </script>


@endsection
