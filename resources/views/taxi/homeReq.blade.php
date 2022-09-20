@extends('layouts.taxi2')

@section('content')
    {{-- print_r($params) --}}
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="container text-center">
            <p><b>Київ та область</b></p>
        </div>


    <!--     Пошук за адресою.-->

        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_street">
        <p class="text-center">Пошук за адресою. Заповнити поля для розрахунку вартості поїздки.</p>
        <form action="{{route('search-cost')}}" id="form">
                @csrf
            <div class="row">
                <div class="col-md-7 col-lg-8">
                    @if ($params['user_phone'] == '000')
                        <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" value="">
                    @else
                        <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" value="{{$params['user_phone']}}">
                    @endif
                    <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="{{$params['user_full_name']}}">
                    <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
                    <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />

                    <div class="container">
                            <div class="row">
                                <div class="col-8">
                                        <input type="text" class="form-control" id="search" name="search" autocomplete="off" placeholder="Пошук вулиці (Звідки)" value="{{ $params['routefrom']}}" required>
                                    </div>
                                <div class="col-4">
                                        <input type="text" id="from_number" name="from_number" placeholder="Будинок?" autocomplete="off" class="form-control" style="text-align: center" value="{{ $params['routefromnumber']}}" required/>
                                    </div>
                            </div>
                        </div>

                    <div class="container" style="text-align: left">
                            <label class="form-check-label" for="route_undefined">По місту</label>
                            <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" onclick="showHide('block_city')"
                            @if($params['route_undefined'] == 1)
                                checked
                            value="1"
                            @endif>
                        </div>

                    <div id="block_city" class="container"
                            @if($params['route_undefined'] == 1)
                                    style="display:none"
                            @else  style="display:block"
                            @endif>
                            <div class="row">
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="search1" name="search1" autocomplete="off" value="{{ $params['routeto']}}"  placeholder="Пошук вулиці (Куди)" >
                                    </div>
                                    <div class="col-4">
                                        <input type="text" id="to_number" name="to_number" placeholder="Будинок?" autocomplete="off" class="form-control" style="text-align: center"  value="{{ $params['routetonumber']}}" />
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
                    <div class="container">

                            <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                            onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                             <div id="block_id" style="display: none;">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <label class="form-label" for="required_time">Час подачі</label>
                                        <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="{{ $params['required_time']}}">
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="wagon" name="wagon"
                                                   @if( $params['wagon'] == 1)
                                                   checked
                                                   value="1"
                                                @endif>
                                            <label class="form-check-label" for="wagon">Универсал</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between ">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="minibus" name="minibus"
                                                   @if( $params['minibus'] == 1)
                                                   checked
                                                   value="1"
                                                @endif>
                                            <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between ">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="premium" name="premium"
                                                   @if( $params['premium'] == 1)
                                                   checked
                                                   value="1"
                                                @endif>
                                            <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between ">
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
                                    <li class="list-group-item d-flex justify-content-between ">
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
                    </div>
                </div>

            <div class="container text-center">
                <button class="w-100 btn btn-primary btn-lg" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
            </div>
            </form>
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
