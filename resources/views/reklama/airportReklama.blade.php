@extends('layouts.taxiNewCombo')

@section('content')
    <?php

    use App\Http\Controllers\WebOrderController;

    $connection = new  WebOrderController();
    $quites = $connection->quites_all();
    $i = -1;
    foreach ($quites as $item) {
        $i++;
        $quitesArr[$i] =  $item['name'];

    }
    $rand =  rand(0, $i);
    /**
    * Бегущая строка
    */

    $quites_order = $connection->query_all();

    $i_order = -1;
    foreach ($quites_order as $item) {
        $i_order++;
        $quitesArr_order[$i_order] =  $item['routefrom'] . " - " . $item['routeto'] . "-" . $item['web_cost'] . "грн " ;
    }

    ?>
    @if($i_order !== -1)
        <div class="container-fluid" style="text-align: center">
            <p class="marquee gradient"><span>
                <?php
                $i_order_view = 0;
                do {
                    $rand_order =  rand(0, $i_order);
                    echo "&#128662 " . $quitesArr_order[$rand_order];
                    $i_order_view++;
                } while ($i_order_view <= 3);
                ?></span>
            </p>
        </div>
    @endif

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-sm-6">
                <a href="{{route('homeCombo')}}" target="_blank" style="text-decoration: none;">
                    <h2 class="gradient text-primary">
                        <b>Найкраща пропозиція Таксі Києва та області </b>
                    </h2>
                    <p  class="gradient text-opacity-25">
                        <b>Послуги нашої служби.</b>
                    </p>
                </a>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('orderReklama')}}" target="_blank"
                           style="text-decoration: none;">Попереднє замовлення</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('driverReklama')}}" target="_blank"
                           style="text-decoration: none;">Послуга "тверезий водій"</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('stationReklama')}}" target="_blank"
                           style="text-decoration: none;">Таксі на вокзал</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('airportReklama')}}" target="_blank"
                           style="text-decoration: none;">Таксі в аеропорт Бориспіль та Жуляни</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('regionReklama')}}" target="_blank"
                           style="text-decoration: none;">Дешеве обласне міжміське таксі</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('tableReklama')}}" target="_blank"
                           style="text-decoration: none;">Зустріч з табличкою</a>
                    </li>
                </ul>
                <div class="text-center">
                    <a href="{{route('homeCombo')}}" class="gradient-button" target="_blank">Замовити таксі</a>
                </div>
            </div>

            <div class="col-lg-9 col-sm-6">
                <div class="row">
                    <ul class="olderOne">
                        <li>
                         <a href="{{route('homeCombo')}}">

                                <b><h5 class="text-center">Таксі в аеропорт Бориспіль та Жуляни</h5></b>

                                <p class="text-center">
                                    Оцініть  найвигідніші ціни та якісний сервіс на поїздку в аеропорти міста Києва.
                                    Розрахуйте вартість онлайн.
                                </p>
                               <div class="container-fluid" style="margin-top: -75px">
                                    <div class="row">
                                        <a class="col-6 text-center" href="{{route('transfer',
                                                     ["Аэропорт Борисполь терминал Д", "taxi.transferBorispol"])}}">
                                            <img src="{{ asset('img/borispol.png') }}" style="width: 80%; height: auto"
                                                 title="Трансфер до аеропорту Бориспіль">
                                        </a>

                                        <a   class="col-6 text-center" href="{{route('transfer',
                                                     ["Аэропорт Жуляны новый (ул.Медовая 2)", "taxi.transferJulyany"])}}">
                                            <img src="{{ asset('img/sikorskogo.png') }}" style="width: 80%; height: auto"
                                                 title="Трансфер до аеропорту Київ">
                                        </a>
                                    </div>
                                </div>
                                <p style="text-align: justify">
                                    Кожна поїздка в аеропорт є дуже важливою . Кожен з нас заздалегідь прагне
                                    все спланувати, щоб не виникло неприємних сюрпризів, адже ніяк не можна запізнитися
                                    на літак. Зі службою «Таксі Лайт Юа» ви можете не хвилюватися, ми пропонуємо якісний сервіс,
                                    швидку подачу та ціни таксі економ, стандарт та преміум класу.
                                </p>

                                <p style="text-align: justify">
                                    Найправильнішим рішенням буде  попереднє замовлення таксі в аеропорт в службі «Таксі Лайт Юа».
                                    Це дасть можливість заздалегідь розрахувати вартість та подбати про майбутню поїздку.
                                    Водій візьме ваше замовлення заздалегідь та складе свій маршрут так,  щоб прибути без запізнень.
                                    Попереднє замовлення таксі в аеропорт позбавить вас від пошуку найближчого вільного
                                    автомобіля.
                                </p>

                                <p style="text-align: justify">
                                    Попереднє замовлення можна оформити за допомогою форми на
                                    нашому сайті, або  залишити свій номер у формі обратного зв'язку на сайті.
                                    Оператор розрахує точну вартість та підбере найбільш підходящий автомобіль.
                                </p>

                            </a>
                        </li>
                    </ul>
                    <div class="container-fluid" style="margin-top: 10px">
                        <p  class="gradient text-opacity-25">
                            <b>Вам також буде цікаво:</b>
                        </p>

                        <div class="container-fluid" style="margin-top: 10px">
                            <p  class="gradient text-opacity-25">
                                <b>Вам також буде цікаво:</b>
                            </p>

                            <div class="header gradient" >
                                <a href="{{route('homeCombo')}}" target="_blank">Шукати адресу</a>
                                <a href="{{route('homeMapCombo')}}" target="_blank">Пошук по мапи</a>
                                <a  @guest
                                    href="{{ route('callBackForm') }}"
                                    @else
                                    href="{{ route('callBackForm-phone', Auth::user()->user_phone) }}"
                                    @endguest>
                                    Допомога у складний час</a>
                                <a href="{{route('callWorkForm')}}" target="_blank">Робота у таксі</a>
                                <a href="{{route('home-news')}}" target="_blank">Новини</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection
