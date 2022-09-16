@extends('layouts.logout')

@section('content')
    {{-- $phone --}}
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="container" style="text-align: center; margin-top: 5px">
        <h1>Таксі Київ (Київська область)</h1>
        </div>

        <div class="container text-center">
            <a  class="btn btn-outline-secondary  col-3" href="{{route('homeStreet', [$phone, $user_name])}}" target="_blank">Вулиці</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeObject', [$phone, $user_name])}}" target="_blank">Об'єкти</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeMap', [$phone, $user_name])}}" target="_blank">Мапа</a>
        </div>

        <!--     Пошук за адресою.-->
        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_street">
         <p class="text-center">Пошук за адресою. Заповнити поля для розрахунку вартості поїздки.</p>
         <form action="{{route('search-cost')}}" id="form">
                @csrf
                <div class="row">
                    <div class="col-md-7 col-lg-8">
                        @if ($phone == '000')
                            <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" value="">
                        @else
                            <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" value="{{$phone}}">
                        @endif
                        <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="{{$user_name}}">
                        <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                        <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />

                        <div class="container">
                                <div class="row">
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="search" name="search" autocomplete="off" placeholder="Пошук вулиці (Звідки)" value="" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" id="from_number" name="from_number" placeholder="Будинок?" autocomplete="off" class="form-control" style="text-align: center" value="" required/>
                                    </div>
                                </div>
                        </div>

                        <div class="container" style="text-align: left">
                            <label class="form-check-label" for="route_undefined">По місту</label>
                            <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1" onclick="showHide('block_city')">
                        </div>

                        <div id="block_city" class="container"  style="display:block">
                                <div class="row">
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="search1" name="search1" autocomplete="off" placeholder="Пошук вулиці (Куди)" >
                                    </div>
                                    <div class="col-4">
                                        <input type="text" id="to_number" name="to_number" placeholder="Будинок?" autocomplete="off" class="form-control" style="text-align: center" value="" />
                                    </div>
                                </div>
                        </div>

                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                        <div class="container" style="margin-top: 5px">
                             <div class="row">
                                    <div class="col-4 g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                             </div>
                       </div>
                    </div>

                    <div class="col-sm-5 col-lg-4" style="margin-top: 5px">

                        <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                               onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>
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
                 <button class="w-100 btn btn-primary btn-lg" type="submit">Розрахувати вартість поїздки</button>
             </div>
            </form>
    </div>
        <br>
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

    </script>


@endsection
