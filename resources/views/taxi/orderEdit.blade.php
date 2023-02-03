@extends('layouts.taxiNewCombo')

@section('content')
    @guest
        <div class="container  wrapper">
            Для замовлення введить номер телефону
        </div>
    @endguest

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="text-center">
                <p class="gradient"><b>Увага!!!</b> Перевірте дані про майбутню подорож та підтвердіть замовлення.</p>
            </div>
            <form action="{{route('search-cost-edit', $orderId['0']['id']) }}">
                @csrf
                <div class="row" >
                    <div>
                        <input type="hidden" id="search" name="search" value="{{ $orderId['0']['routefrom'] }}" />
                        <input type="hidden" id="from_number" name="from_number" value="{{ $orderId['0']['routefromnumber'] }}" />
                        <input type="hidden" class="form-check-input" id="route_undefined" name="route_undefined"
                                @if( $orderId['0']['route_undefined'] == 1)
                                    checked  value="1"
                                @endif />
                        <input type="hidden" id="search1"  name="search1" value="{{ $orderId['0']['routeto'] }}" />
                        <input type="hidden" id="to_number" name="to_number" value="{{ $orderId['0']['routetonumber'] }}"/>
                        <input type="hidden" class="form-check-input" id="wagon" name="wagon"
                               @if( $orderId['0']['wagon'] == 1)
                               checked
                               value="1"
                            @endif/>

                        <input type="hidden" class="form-check-input" id="minibus" name="minibus"
                               @if( $orderId['0']['minibus'] == 1)
                               checked
                               value="1"
                            @endif/>

                        <input type="hidden" class="form-check-input" id="premium" name="premium"
                               @if( $orderId['0']['premium'] == 1)
                               checked
                               value="1"
                            @endif/>

                        <input type="hidden" id="flexible_tariff_name" name="flexible_tariff_name" value="{{$orderId[0]['flexible_tariff_name']}}"/>

                        <input type="hidden" id="flexible_tariff_name" name="payment_type" value="{{$orderId['0']['payment_type']}}"/>
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
                                        <input type="tel"
                                               class="form-control is-invalid "
                                               id="user_phone" name="user_phone"
                                               pattern="[\+]\d{12}"
                                               placeholder="+380936665544"
                                               title="Формат вводу: +380936665544"
                                               minlength="13"
                                               maxlength="13" required
                                               value="{{ old('user_phone') }}" required autocomplete="user_phone"
                                               autofocus>

                                    @endguest
                                    @auth
                                        <input type="tel" class="form-control" id="user_phone" name="user_phone"
                                               value="{{ Auth::user()->user_phone}}"
                                               readonly/>
                                    @endauth

                                </div>
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            <p class="text-center gradient">Додати побажання до поїздки</p>
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <div class="col-12">
                                                <textarea class="form-control" id="comment" name="comment"  placeholder="Коментар"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @auth
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            <p class="text-center gradient">Ввести бонус-код</p>
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <div class="col-12" style="margin-top: 5px">
                                                <input type="text" id="promo" name="promo" placeholder="бонус-код"
                                                       value=""  class="form-control"
                                                       onchange= "pCode(this.value)"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endauth
                            </div>
                        </div>

                        <div class="container" style="margin-top: 5px">
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



                        <a class="w-100 btn btn-danger" style="margin-top: 5px"
                           href="{{route('homeCombo')}}" onclick="sessionStorage.clear();">
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


