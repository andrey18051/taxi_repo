@extends('layouts.logoutObject2')

@section('content')
    {{-- print_r($orderId) --}}

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="text-center">
            <h2>Увага!!! Перевірте дані про майбутню подорож та підтвердіть замовлення.</h2>
        </div>
        <form action="{{route('search-cost-edit-object', $orderId['0']['id']) }}">
                @csrf
                <div class="row">
                    <div class="col-md-7 col-lg-8">
                        <div class="container">
                            <div class="row">
                                <div class="col-12">
                                    <label for="search" class="form-label">Звідки</label>
                                    <input type="text" class="form-control" id="search2" autocomplete="off" name="search2" value="{{ $orderId['0']['routefrom'] }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                                <div class="row">
                                    <div class="col-12">
                                        <label class="form-check-label" for="route_undefined">По місту</label>
                                        <input type="checkbox" class="form-check-input" id="route_undefined" name="route_undefined" onclick="showHide('block_city')"
                                            @if( $orderId['0']['route_undefined'] == 1)
                                                checked
                                               value="1"
                                        @endif
                                        >
                                    </div>
                                </div>
                            </div>

                        <div id="block_city" class="container"
                             @if( $orderId['0']['route_undefined'] == 1)
                             style="display:none"
                             @else  style="display:block"
                            @endif
                            >
                                <div class="row">
                                    <div class="col-12">
                                        <label for="search1" class="form-label">Куди</label>
                                        <input type="text" class="form-control" id="search3" autocomplete="off" name="search3" value="{{ $orderId['0']['routeto'] }}" required>
                                    </div>
                                </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
                            <div class="row">
                                <div class="col-12">
                                    <input type="name" id="user_full_name" name="user_full_name" value="{{ $orderId['0']['user_full_name'] }}"  class="form-control"  required/>
                                </div>
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
                            <div class="row">
                                <div class="col-12">
                                    <input type="tel" class="form-control" id="user_phone" name="user_phone" placeholder="Телефон? Приклад: 0936665544" value="{{ $orderId['0']['user_phone'] }}"  autofocus required>
                                </div>
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
                            <div class="row">
                                <div class="col-8">
                                    <label for="comment" class="form-label">Коментар</label>
                                    <textarea class="form-control" id="comment" name="comment"  >{{ $orderId['0']['comment'] }}</textarea>
                                </div>

                                <div class="col-4">
                                    <label for="add_cost" class="form-label">Додати (грн)</label>
                                    <input type="text" id="add_cost" name="add_cost" style="text-align: center" class="form-control" value="{{ $orderId['0']['add_cost'] }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 col-lg-4 order-md-last">
                        <br/>
                        <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                           onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                        <div id="block_id" style="display: none">
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <label class="form-label" for="required_time">Час подачі</label>
                                    <input type="datetime-local" step="any"  id="required_time" value="{{ $orderId['0']['required_time']}}" name="required_time">
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="wagon" name="wagon"
                                            @if( $orderId['0']['wagon'] == 1)
                                               checked
                                               value="1"
                                            @endif>
                                        <label class="form-check-label" for="wagon">Универсал</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="minibus" name="minibus"
                                            @if( $orderId['0']['minibus'] == 1)
                                               checked
                                               value="1"
                                            @endif>
                                        <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="premium" name="premium"
                                            @if( $orderId['0']['premium'] == 1)
                                               checked
                                               value="1"
                                            @endif>
                                        <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тариф</label>

                                        <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name">
                                            <option>{{$orderId[0]['flexible_tariff_name']}}</option>
                                            @for ($i = 0; $i < count($json_arr); $i++)
                                                @if( $json_arr[$i]['name'] != $orderId[0]['flexible_tariff_name'])
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
                                            @if( $orderId['0']['payment_type'] == 1)
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
                <button class="w-100 btn btn-danger btn-lg" type="submit" style="margin-top: 30px">Підтвердіть замовлення</button>

            </form>
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
