@extends('layouts.taxiNewCombo')

@section('content')
    {{-- dd($params)  --}}
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <div class="container" style="text-align: center">
            <h2 class="gradient"><b>Трансфер до автовокзалу</b></h2>
            <p class="text-center gradient">Заповнити поля для розрахунку вартості поїздки.</p>
        </div>

        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_object">

            <form action="{{route('search-cost-transfer', "taxi.transferAuto")}}" id="form_object">
                @csrf
                <div class="row">
                    <div class="col-sm-8 col-lg-8">
                        @guest
                            <input type="hidden" id="user_phone" name="user_phone"  value="+380936665544">
                            <input type="hidden" id="user_full_name" name="user_full_name"  value="Гість">
                        @else
                            <input type="hidden"  id="user_phone" name="user_phone" value="{{Auth::user()->user_phone}}">
                            <input type="hidden" id="user_full_name" name="user_full_name"   value="{{Auth::user()->name}}">
                        @endguest
                        <input type="hidden" id="comment" name="comment" placeholder="Додати побажання" />
                        <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                        <input type="hidden" id="search1" name="search1" value="{{ $params['routeto']}}" required>

                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-8 col-12">
                                        <input type="text" class="form-control" id="search" name="search" autocomplete="off"
                                               placeholder="Звідки?" value="{{ $params['routefrom']}}"
                                               autocomplete="off" placeholder="Звідки?" value=""
                                               onblur="hidFrom(this.value)"
                                               required>
                                    </div>
                                    <div class="col-lg-4 col-12" id="div_from_number">
                                        <input type="text" id="from_number" name="from_number" placeholder="Будинок?"
                                               autocomplete="off" class="form-control" style="text-align: center" value="{{ $params['routefromnumber']}}" />
                                    </div>
                                </div>
                            </div>

                        <div style="display: none" class="container" style="text-align: left">
                             <label class="form-check-label" for="route_undefined">По місту</label>
                             <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" onclick="showHide('block_city')"
                                               @if($params['route_undefined'] == 1)
                                               checked
                                               value="1"
                                            @endif>

                        </div>
                        <div style="display: none" id="block_city" class="container"
                                 @if($params['route_undefined'] == 1)
                                    style="display:none"
                                 @else  style="display:block"
                                @endif>
                                <div class="row">
                                    <div class="col-12">
                                        <input type="text" class="form-control" id="search3" name="search3" autocomplete="off" placeholder="Пошук об'єкта (Куди)" value="{{ $params['routeto']}}">
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

                    <div class="col-md-5 col-lg-4" style="margin-top: 5px">
                        <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                           onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                        <div id="block_id" style="display: none">
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <label class="form-label" for="required_time">Час подачі</label>
                                    <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="{{ $params['required_time']}}">
                                </li>
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

                </div>
                <div class="container text-center">
                    <a class="w-100 btn btn-danger btn-lg" href="{{route('transfer',
                                                     ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferAuto"])}}" style="margin-top: 5px">Очистити форму</a>
                    <button class="w-100 btn btn-primary btn-lg" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
                </div>
            </form>
        </div>
        <br>
        <div class="container text-center">
            <a class="btn btn-outline-danger btn-circle"
               @if (!$params['user_phone'])
               href="{{ route('callBackForm') }}"
               @else
               href="{{ route('callBackForm-phone', $params['user_phone']) }}"
               @endif
               title="Екстренна допомога">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" style="margin-top: 4px" fill="currentColor" class="bi bi-telephone-inbound" viewBox="0 0 16 16">
                    <path d="M15.854.146a.5.5 0 0 1 0 .708L11.707 5H14.5a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 1 0v2.793L15.146.146a.5.5 0 0 1 .708 0zm-12.2 1.182a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                </svg>
            </a>
        </div>
    <br>
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
    </script>


@endsection
