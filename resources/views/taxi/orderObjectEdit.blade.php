@extends('layouts.logoutObject2')

@section('content')
    {{-- print_r($orderId) --}}

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <div class="text-center">
            <h2 class="gradient">Увага!!! Перевірте дані про майбутню подорож та підтвердіть замовлення.</h2>
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

                                <div class="col-12">
                                    <label for="comment" class="form-label">Коментар</label>
                                    <textarea class="form-control" id="comment" name="comment"  >{{ $orderId['0']['comment'] }}</textarea>
                                </div>

                                <div class="col-12 slidecontainer">
                                    <label for="add_cost" class="form-label"  >Додати до вартости: <span id="rangeValue"> 0 </span>грн</label>
                                    <input type="range"

                                           min="0"
                                           max="100"

                                           value="0" id="add_cost" name="add_cost" style="text-align: center"
                                           onchange="document.getElementById('rangeValue').innerHTML = this.value;"
                                           class="slider" />

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
        <br>
        <div class="container text-center">
            <a class="btn btn-outline-danger btn-circle"
               @if (!$orderId['0']['user_phone'])
               href="{{ route('callBackForm') }}"
               @else
               href="{{ route('callBackForm-phone', $orderId['0']['user_phone']) }}"
               @endif
               title="Екстренна допомога">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"  fill="currentColor" class="bi bi-telephone-inbound" viewBox="0 0 16 16">
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

    </script>
@endsection
