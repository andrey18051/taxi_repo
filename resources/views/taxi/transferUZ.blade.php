@extends('layouts.taxiNewCombo')

@section('content')
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
            var route = "/autocomplete-search-combo-hid/" + value.split('/')[0];

            $.ajax({
                url: route,         /* Куда пойдет запрос */
                method: 'get',             /* Метод передачи (post или get) */
                dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                success: function(data){   /* функция которая будет выполнена после успешного запроса.  */
                    if (data == 0) {
                        document.getElementById('from_number').style.display='none';
                        document.getElementById('from_number').value=null;
                    }
                    if (data == 1) {
                        document.getElementById('from_number').style.display='block';
                    }

                }
            });



        }
    </script>
    @empty($params['routefromnumberBlockNone'])
        @php
            $params['routefromnumberBlockNone'] = 'display: block;'
        @endphp
    @endempty

    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <div class="container" style="text-align: center">
            <h2 class="gradient"><b>Трансфер до залізничного вокзалу</b></h2>
            <p class="text-center gradient">Заповнити поля для розрахунку вартості поїздки.</p>
        </div>

        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_object">

            <form action="{{route('search-cost-transfer', "taxi.transferUZ")}}" id="form_object">
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
                                        <input type="text" class="form-control  @error('search') is-invalid @enderror"
                                               id="search" name="search" autocomplete="off"
                                               placeholder="Звідки?"

                                               @isset($params['routefrom'])
                                               value="{{ $params['routefrom']}}"
                                               readonly
                                               @endisset
                                               value="{{ old('search') }}"
                                               onblur="hidFrom(this.value);"
                                               required>
                                    </div>
                                    <div class="col-lg-4 col-12" id="div_from_number">
                                        <input type="text" id="from_number" name="from_number"
                                               class="form-control @error('from_number') is-invalid @enderror"
                                               @isset($params['routefromnumber'])
                                               value="{{ $params['routefromnumber']}}"
                                               readonly
                                               @endisset
                                               value="{{ old('from_number') }}"
                                               placeholder="Будинок?"
                                               autocomplete="off"
                                               @isset($params)
                                               style="text-align: center; display: {{$params['routefromnumberBlockNone']}}"
                                               @endisset
                                               style="text-align: center" >
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

                    <div class="col-sm-5 col-lg-4" style="margin-top: 5px">
                        <a href="javascript:void(0)" class="w-100 btn btn-outline-success"
                           onclick="showHide('block_id')">Додаткові параметри</a>

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
                    <div class="row">
                        <a class="w-100 btn btn-danger" href="{{route('transfer',
                                                     ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferAuto"])}}" style="margin-top: 5px">Очистити форму</a>
                        <button class="w-100 btn btn-primary" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
                    </div>
                </div>
            </form>
        </div>
    </div>




@endsection

