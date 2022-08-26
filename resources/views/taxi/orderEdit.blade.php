@extends('layouts.cost')

@section('content')
    {{-- print_r($orderId) --}}

    <div class="px-4 py-5 px-md-5 text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
    <div class="container">
        <main>
            <div class="py-1 text-center">
                <h2>Увага!!! Перевірте дані про майбутню подорож та підтвердіть замовлення.</h2>
            </div>
            <form action="{{route('search-cost-edit', $orderId['0']['id']) }}">
                @csrf
                <div class="row g-5">
                    <div class="col-md-7 col-lg-8">

                        <div class="row g-3">

                            <div class="col-9">
                                <label for="search" class="form-label">Звідки</label>
                                <input type="text" class="form-control" id="search" autocomplete="off" name="search" value="{{ $orderId['0']['routefrom'] }}" required>
                            </div>

                            <div class="col-3">
                                <label for="from_number" class="form-label">Будинок</label>
                                <input type="text" id="from_number" name="from_number" autocomplete="off" style="text-align: center" value="{{ $orderId['0']['routefromnumber'] }}" class="form-control" />

                            </div>
                            <div class="col-12" >
                                <input type="checkbox" class="form-check-input" autocomplete="off" id="route_undefined" name="route_undefined"
                                    @if( $orderId['0']['route_undefined'] == 1)
                                        checked
                                       value="1"
                                    @endif
                                   >
                                <label class="form-check-label" for="route_undefined">По місту</label>
                            </div>
                            <div class="col-9">
                                <label for="search1" class="form-label">Куди</label>
                                <input type="text" class="form-control" id="search1" autocomplete="off" name="search1" value="{{ $orderId['0']['routeto'] }}" required>
                            </div>

                            <div class="col-3">
                                <label for="to_number" class="form-label" >Будинок</label>
                                <input type="text" id="to_number" name="to_number" autocomplete="off" style="text-align: center" value="{{ $orderId['0']['routetonumber'] }}" class="form-control" />
                            </div>
                            <div class="col-sm-8">
                                <label for="user_phone" class="form-label">Телефон</label>
                                <input type="tel" class="form-control" id="user_phone" name="user_phone"  value="{{ $orderId['0']['user_phone'] }}"  autofocus required>
                            </div>
                            <div class="col-sm-4">
                                <label for="user_full_name" class="form-label">Ім'я</label>
                                <input type="name" id="user_full_name" name="user_full_name" value="{{ $orderId['0']['user_full_name'] }}"  class="form-control"  required/>

                            </div>
                            <div class="col-8">
                                <label for="comment" class="form-label">Коментар</label>
                                <textarea class="form-control" id="comment" name="comment"  >{{ $orderId['0']['comment'] }}</textarea>

                            </div>

                            <div class="col-4">
                                <label for="add_cost" class="form-label"  >Додати до вартості (грн)</label>
                                <input type="text" id="add_cost" name="add_cost" style="text-align: center" class="form-control" value="{{ $orderId['0']['add_cost'] }}" />
                            </div>

                            <!--                            <div class="col-12">
                                                            <label for="username" class="form-label">Имя пользователя</label>
                                                            <div class="input-group has-validation">
                                                                <span class="input-group-text">@</span>
                                                                <input type="text" class="form-control" id="username" placeholder="Имя пользователя" required>
                                                                <div class="invalid-feedback">
                                                                    Имя пользователя обязательно.
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <label for="email" class="form-label">Эл. адрес <span class="text-muted">(Необязательно)</span></label>
                                                            <input type="email" class="form-control" id="email" placeholder="you@example.com">
                                                            <div class="invalid-feedback">
                                                                Пожалуйста, введите действующий адрес электронной почты для получения информации о доставке.
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <label for="address" class="form-label">Адрес</label>
                                                            <input type="text" class="form-control" id="address" placeholder="ул. Фокина" required>
                                                            <div class="invalid-feedback">
                                                                Пожалуйста, введите свой адрес доставки.
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <label for="address2" class="form-label">Адрес 2 <span class="text-muted">(Необязательно)</span></label>
                                                            <input type="text" class="form-control" id="address2" placeholder="квартира или дом">
                                                        </div>

                                                        <div class="col-md-5">
                                                            <label for="country" class="form-label">Страна</label>
                                                            <select class="form-select" id="country" required>
                                                                <option value="">Выберите...</option>
                                                                <option>Россия</option>
                                                            </select>
                                                            <div class="invalid-feedback">
                                                                Пожалуйста, выберите действующую страну.
                                                            </div>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label for="state" class="form-label">Область</label>
                                                            <select class="form-select" id="state" required>
                                                                <option value="">Выберите...</option>
                                                                <option>Брянская</option>
                                                            </select>
                                                            <div class="invalid-feedback">
                                                                Пожалуйста, выберите действующую область.
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label for="zip" class="form-label">Индекс</label>
                                                            <input type="text" class="form-control" id="zip" placeholder="" required>
                                                            <div class="invalid-feedback">
                                                                Почтовый индекс обязателен.
                                                            </div>
                                                        </div>-->
                        </div>
                        <!--
                                                <hr class="my-4">

                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="same-address">
                                                    <label class="form-check-label" for="same-address">Адрес доставки такой же, как мой платежный адрес</label>
                                                </div>

                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="save-info">
                                                    <label class="form-check-label" for="save-info">Сохраните эту информацию для следующего раза</label>
                                                </div>

                                                <hr class="my-4">

                                                <h4 class="mb-3">Оплата</h4>

                                                <div class="my-3">
                                                    <div class="form-check">
                                                        <input id="credit" name="paymentMethod" type="radio" class="form-check-input" checked required>
                                                        <label class="form-check-label" for="credit">Кредитная карта</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input id="debit" name="paymentMethod" type="radio" class="form-check-input" required>
                                                        <label class="form-check-label" for="debit">Дебетовая карта</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input id="paypal" name="paymentMethod" type="radio" class="form-check-input" required>
                                                        <label class="form-check-label" for="paypal">PayPal</label>
                                                    </div>
                                                </div>

                                                <div class="row gy-3">
                                                    <div class="col-md-6">
                                                        <label for="cc-name" class="form-label">Имя на карте</label>
                                                        <input type="text" class="form-control" id="cc-name" placeholder="" required>
                                                        <small class="text-muted">Полное имя, как отображено на карточке</small>
                                                        <div class="invalid-feedback">
                                                            Имя на карте обязательно.
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="cc-number" class="form-label">Номер кредитной карты</label>
                                                        <input type="text" class="form-control" id="cc-number" placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Номер кредитной карты обязателен.
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="cc-expiration" class="form-label">Срок действия</label>
                                                        <input type="text" class="form-control" id="cc-expiration" placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Дата истечения карты обязательна.
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="cc-cvv" class="form-label">CVV</label>
                                                        <input type="text" class="form-control" id="cc-cvv" placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Защитный код обязателен.
                                                        </div>
                                                    </div>
                                                </div>

                        <hr class="my-4">
                        -->


                    </div>
                    <div class="col-md-5 col-lg-4 order-md-last">

                        <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last" onclick="showHide('block_id')">Бажаний тип авто</a><br/><br/>

                        <div id="block_id" style="display: none;">
                            <ul class="list-group mb-3">
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

                                        <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" required>
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
                if (obj.style.display != "block") {
                    obj.style.display = "block"; //Показываем элемент
                }
                else obj.style.display = "none"; //Скрываем элемент
            }
            //Если элемент с id-шником element_id не найден, то выводим сообщение
            else alert("Элемент с id: " + element_id + " не найден!");
        }
    </script>
@endsection
