@extends('layouts.taxi')

@section('content')
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="container" style="text-align: center; margin-top: 5px">
        <h1>Таксі Київ (Київська область)</h1>
        </div>

        <div class="container text-center">
            <a  class="btn btn-outline-secondary  col-3" href="{{route('home')}}">Вулиці</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeObject')}}">Об'єкти</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeMap')}}">Мапа</a>
        </div>

        <!--     Пошук по об'єктах.-->
        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_object">
            <p class="text-center">Пошук по об'єктах. Заповнити поля для розрахунку вартості поїздки.</p>
            <form action="{{route('search-cost-object')}}" id="form_object">
                    @csrf
                    <div class="row">
                        <div class="col-sm-8 col-lg-8">
                            <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" placeholder="0936665544" value="">
                            <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
                            <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="Новий замовник">
                            <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />


                            <div class="container">
                                    <div class="row">
                                        <div class="col-12">
                                            <input type="text" class="form-control" id="search2" name="search2" autocomplete="off" placeholder="Пошук об'єкта (Звідки)" value="" required>
                                        </div>
                                    </div>
                                </div>

                            <div class="container" style="text-align: left">
                                    <label class="form-check-label" for="route_undefined">По місту</label>
                                    <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1" onclick="showHide('block_city_object')">
                                </div>

                            <div id="block_city_object" class="container"  style="display:block">
                                    <div class="row">
                                        <div class="col-12">
                                            <input type="text" class="form-control" id="search3" name="search3" autocomplete="off" placeholder="Пошук об'єкта (Куди)" >
                                        </div>
                                    </div>
                                </div>

                            <script defer src="https://www.google.com/recaptcha/api.js"></script>
                            <div class="container" style="margin-top: 5px">
                                 <div class="row">
                                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                                    </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-lg-4" style="margin-top: 5px">

                            <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                               onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                            <div id="block_id" style="display: none">
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

                    <div class="container text-center">
                        <button class="w-100 btn btn-primary btn-lg" type="submit">Розрахувати вартість поїздки</button>
                    </div>
                </form>
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
