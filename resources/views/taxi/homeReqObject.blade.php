@extends('layouts.taxi')

@section('content')
    {{-- print_r($params) --}}
    <div class="px-1 py-1 px-md-5 text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
    <div class="container">
        <main>
            <div class="text-center">
                <h1>Таксі Київ (Київська область)</h1>
                <p class="lead">Заповнити поля для розрахунку вартості поїздки.</p>
            </div>
            <form action="{{route('search-cost-object')}}" id="form">
                @csrf
                <div class="row g-5">

                    <div class="col-md-7 col-lg-8">

                            <div class="row g-3">
                                <div class="col-sm-8">
<!--                                    <label for="user_phone" class="form-label">Телефон</label>-->
                                    <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" placeholder="0936665544" value="">
                                </div>
                                <div class="col-sm-4">
<!--                                    <label for="user_full_name" class="form-label">Ім'я</label>-->
                                    <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="Новий замовник">

                                </div>
                                <div class="col-9">
                                    <label for="search" class="form-label">Звідки</label>
                                    <input type="text" class="form-control" id="search2" name="search2" autocomplete="off" placeholder="Пошук вулиці" value="{{ $params['routefrom']}}" required>
                                </div>

                                <div class="col-9" >
                                    <label class="form-label" for="required_time">Время подачі</label>
                                    <input type="datetime-local" step="any"  id="required_time" value="{{ $params['required_time']}}" name="required_time">
                                </div>
                                <div class="col-3" >
                                    <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" onclick="showHide('block_city')"
                                           @if($params['route_undefined'] == 1)
                                           checked
                                           value="1"
                                        @endif>
                                    <label class="form-check-label" for="route_undefined">По місту</label>
                                </div>

                                <div id="block_city" class="container"
                                     @if($params['route_undefined'] == 1)
                                     style="display:none"
                                     @else  style="display:block"
                                    @endif
                                >
                                    <div class="row">
                                        <div class="col-9">
                                    <label for="search1" class="form-label">Куди</label>
                                    <input type="text" class="form-control" id="search3" name="search3" autocomplete="off" value="{{ $params['routeto']}}" placeholder="Пошук вулиці" >
                                </div>

                                    </div>
                                </div>
                                <div class="col-sm-11">
<!--                                    <label for="comment" class="form-label">Коментар</label>-->
                                    <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
<!--                                    <textarea type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання"></textarea>-->

                                </div>

                                <div class="col-sm-1">
<!--                                    <label for="add_cost" class="form-label">Додати до вартості (грн)</label>-->
                                    <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                                </div>

                        <script src="https://www.google.com/recaptcha/api.js"></script>
                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                    </div>

                    </div>
                    <div class="col-md-5 col-lg-4 order-md-last">

                            <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                               onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                        <div id="block_id" style="display: none;">
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="wagon" name="wagon"
                                        @if( $params['wagon'] == 1)
                                            checked
                                            value="1"
                                        @endif>
                                        <label class="form-check-label" for="wagon">Универсал</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="minibus" name="minibus"
                                               @if( $params['minibus'] == 1)
                                               checked
                                               value="1"
                                            @endif>
                                        <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="premium" name="premium"
                                               @if( $params['premium'] == 1)
                                               checked
                                               value="1"
                                            @endif>
                                        <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тариф</label>
                                        <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" >
                                            <option>{{$params['flexible_tariff_name']}}</option>
                                            @for ($i = 0; $i < count($json_arr); $i++)
                                                @if( $json_arr[$i]['name'] != $params['flexible_tariff_name'])
                                                    <option>{{$json_arr[$i]['name']}}</option>
                                                @endif
                                            @endfor
                                        </select>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тип оплати замовлення</label>
                                        <select class="form-select" id="flexible_tariff_name" name="payment_type" required>
                                            @if( $params['payment_type'] == 1)
                                                {{--   <option>безготівка</option>--}}
                                                <option>готівка</option>
                                            @else
                                                <option>готівка</option>
                                                <!--                                                <option>безготівка</option>-->
                                            @endif
                                        </select>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div><button class="w-100 btn btn-primary btn-lg" type="submit" >Розрахувати вартість поїздки</button>
            </form>
        </main>


    </div>
    </div>

    <script type="text/javascript">
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
