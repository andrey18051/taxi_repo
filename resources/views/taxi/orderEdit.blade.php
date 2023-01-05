@extends('layouts.taxiNewCombo')

@section('content')

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="text-center">
                <p class="gradient"><b>Увага!!!</b> Перевірте дані про майбутню подорож та підтвердіть замовлення.</p>
            </div>
            <form action="{{route('search-cost-edit', $orderId['0']['id']) }}">
                @csrf
                <div class="row" >

                        <div class="container" style="display: none;">
                             <div class="row">
                                <div class="col-8">
                                    <label for="search" class="form-label">Звідки</label>
                                    <input readonly type="text" class="form-control" id="search" autocomplete="off" name="search" value="{{ $orderId['0']['routefrom'] }}" required>
                                </div>
                                <div class="col-4">
                                    <label for="from_number" class="form-label">Будинок</label>
                                    <input readonly type="text" id="from_number" name="from_number" autocomplete="off" style="text-align: center" value="{{ $orderId['0']['routefromnumber'] }}" class="form-control" />
                                </div>
                             </div>
                        </div>

                        <div class="container" style="display: none;">
                                <div class="row">
                                    <div class="col-12">
                                    <label class="form-check-label" for="route_undefined">По місту</label>
                                    <input readonly type="checkbox" class="form-check-input" id="route_undefined" name="route_undefined" onclick="showHide('block_city')"
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
                                 style="display: none;"
                                >
                                <div class="row" style="display: none;">
                                    <div class="col-8">
                                        <label for="search1" class="form-label">Куди</label>
                                        <input readonly type="text" class="form-control" id="search1" autocomplete="off" name="search1" value="{{ $orderId['0']['routeto'] }}" required>
                                    </div>

                                    <div class="col-4">
                                        <label for="to_number" class="form-label" >Будинок</label>
                                        <input readonly type="text" id="to_number" name="to_number" autocomplete="off" style="text-align: center" value="{{ $orderId['0']['routetonumber'] }}" class="form-control" />
                                    </div>
                                </div>
                            </div>
                    <div class="col-lg-12 col-sm-12 ">
                        <div class="container text-center" style="margin-top: 5px">
                            <div class="row">
                                <div class="col-12">
                                    @guest
                                        <input type="text" id="user_full_name" name="user_full_name"
                                               value="Гість"  class="form-control"  />
                                    @endguest
                                    @auth
                                   <input type="text" id="user_full_name" name="user_full_name"
                                          value="{{Auth::user()->name}}"  class="form-control"  />
                                    @endauth
                                </div>
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
                            <div class="row">
                                <div class="col-12">
                                    @guest
                                        <input type="tel" class="form-control" id="user_phone" name="user_phone"
                                               pattern="[\+]\d{12}"
                                               placeholder="+380936665544"
                                               title="Формат вводу: +380936665544"
                                               minlength="13"
                                               maxlength="13"required/>
                                    @endguest
                                    @auth
                                        <input type="tel" class="form-control" id="user_phone" name="user_phone"
                                               value="{{ Auth::user()->user_phone}}"
                                               pattern="[\+]\d{12}"
                                               placeholder="+380936665544"
                                               title="Формат вводу: +380936665544"
                                               minlength="13"
                                               maxlength="13"
                                               required />
                                    @endauth

                                </div>
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">

                                <div class="col-12">
                                    <textarea class="form-control" id="comment" name="comment"  placeholder="Коментар"></textarea>
                                </div>
                                @auth
                                <div class="col-12" style="margin-top: 5px">
                                    <input type="text" id="promo" name="promo" placeholder="Промо код"
                                           value=""  class="form-control"
                                           onchange= "pCode(this.value)">

                                </div>
                                @endauth
                            <div class="col-12 slidecontainer">
                                <label for="add_cost" class="form-label" >
                                    Змінити вартість: <span id="rangeValue"> 0 </span>грн.
                                </label>
                                       <input type="range"
                                              @if(config('app.server') == 'Одесса')
                                              min="-30"
                                              @else
                                              min="0"
                                              @endif
                                           max="1000"
                                            step="1"
                                           value="0" id="add_cost" name="add_cost" style="text-align: center"
                                           onchange="document.getElementById('rangeValue').innerHTML = this.value;
                                           let  order_cost = Number(this.value) + Number({{session('order_cost')}});
                                                     document.getElementById('rangeValueСost').innerHTML = order_cost;"
                                           class="slider" />

                            </div>


                            <h4> Вартість поїздки: <span id="rangeValueСost">{{session('order_cost')}}</span>грн.</h4>
                            Рекомендована вартість на підставі цін інших служб таксі в діапазоні з
                            <a class="btn btn-success"
                            onclick="document.getElementById('add_cost').innerHTML = {{round (session('order_cost') * config('app.order_cost_min'), 0)}};
                                let  order_cost = {{round (session('order_cost') * config('app.order_cost_min'), 0)}};
                                let rangeValue = {{round (session('order_cost') * config('app.order_cost_min'), 0) - session('order_cost')}};
                                document.getElementById('rangeValue').innerHTML = rangeValue;
                                document.getElementById('add_cost').value = rangeValue;
                                document.getElementById('rangeValueСost').innerHTML = order_cost;"

                            >{{round (session('order_cost') * config('app.order_cost_min'), 0)}}</a>
                            по
                            <a class="btn btn-success"
                               onclick="document.getElementById('add_cost').innerHTML = {{round (session('order_cost') * config('app.order_cost_max'), 0)}};
                                   let  order_cost = {{round (session('order_cost') * config('app.order_cost_max'), 0)}};
                                   let rangeValue = {{round (session('order_cost') * config('app.order_cost_max'), 0) - session('order_cost')}};
                                   document.getElementById('rangeValue').innerHTML = rangeValue;
                                   document.getElementById('add_cost').value = rangeValue;
                                   document.getElementById('rangeValueСost').innerHTML = order_cost;"


                            >{{round (session('order_cost') * config('app.order_cost_max'), 0)}}</a> грн.
                        </div>
                    </div>
                    <div class="col-md-5 col-lg-4 order-md-last" style="display: none;">
                        <br/>
                        <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                           onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                        <div id="block_id" style="display: none" >
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

                <div class="container text-center">
                    <div class="row">
                        <a class="w-100 btn btn-danger" style="margin-top: 5px"
                           href="{{route('homeCombo')}}">
                            Очистити форму
                        </a>
                        <button class="w-100 btn btn-primary" style="margin-top: 5px" type="submit">
                            Розрахувати вартість поїздки
                        </button>
                    </div>
                </div>

            </form>
    </div>
    <script defer type="text/javascript">

        function pCode(value) {

            const route = "/promoSize/" + value;

            $.ajax({
                url: route,         /* Куда пойдет запрос */
                method: 'get',             /* Метод передачи (post или get) */
                dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                success: function(data){   /* функция которая будет выполнена после успешного запроса.  */

                    order_cost = {{session('order_cost')}} - Math.round({{session('order_cost')}}*data);
                    rangeValue = order_cost - {{ session('order_cost')}};
                    document.getElementById('rangeValue').innerHTML = rangeValue;

                    document.getElementById('add_cost').min =  rangeValue;
                    document.getElementById('add_cost').value =  rangeValue;
                    document.getElementById('rangeValueСost').innerHTML =  order_cost;
                }
            });
        }
    </script>
@endsection


