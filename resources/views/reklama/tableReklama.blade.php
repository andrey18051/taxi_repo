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
                                <b><h5 class="text-center">Зустріч з табличкою</h5></b>

                                <p style="text-align: center">
                                    Водій зустріне вас з табличкою біля виходу з паспортного контролю в аеропорту або
                                    біля виходу з вагона на вокзалі.
                                </p>
                                <div class="container-fluid" style="margin-top: -75px">
                                    <div class="row">
                                        <a class="col-3 text-center" href="{{route('transferFrom',
                                                     ["ЖД Южный", "taxi.transferFromUZ"])}}">
                                            <img src="{{ asset('img/UZ.png') }}" style="width: 80%; height: auto"
                                                 title="Зустріч на залізничному вокзалі">
                                        </a>
                                        <a class="col-3 text-center" href="{{route('transferFrom',
                                        ["Аэропорт Борисполь терминал Д", "taxi.transferFromBorispol"])}}">
                                            <img src="{{ asset('img/borispol.png') }}" style="width: 80%; height: auto"
                                                 title="Зустріч в аеропорту Бориспіль">
                                        </a>

                                        <a   class="col-3 text-center" href="{{route('transferFrom',
                                        ["Аэропорт Жуляны новый (ул.Медовая 2)", "taxi.transferFromJulyany"])}}">
                                            <img src="{{ asset('img/sikorskogo.png') }}" style="width: 80%; height: auto"
                                                 title="Зустріч в аеропорту Київ">
                                        </a>


                                        <a   class="col-3 text-center" href="{{route('transferFrom',
                                                     ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferFromAuto"])}}">
                                            <img src="{{ asset('img/auto.jpeg') }}" style="width: 80%; height: auto"
                                                 title="Зустріч на автовокзалі">
                                        </a>
                                    </div>
                                </div>
                                <p style="text-align: justify">
                                    Необхідно організувати зустріч в аеропорту Бориспіль/Жуляни
                                    або на вокзалі Києва та області? В цьом разі вам неодмінно варто скористатися послугою
                                    "Зустріч з табличкою" від служби «Таксі Лайт Юа». Професійний водій оперативно прибуде
                                    та зустріне вас з табличкою біля виходу з паспортного контролю в аеропорту або
                                    біля виходу з вагона на вокзалі.
                                    Це зручно та безпечно, а зі службою «Таксі Лайт Юа», ще надійно та економно.
                                </p>

                                <p style="text-align: justify">
                                    Послуга зустрічі з табличкою  в Києві та області дуже затребувані та популярні.
                                    В такому випадку краще перестрахуватися, завдяки цій послузі можна заздалегідь
                                    подбати про прибуття до Києва та організувати зустріч ще до виїзду.
                                </p>

                                <p style="text-align: justify">
                                    Послуги "Зустріч з табличкою" можна оформити за допомогою форми на нашому сайті, або
                                    залишити свій номер на сайті. Оператор розрахує точну вартість та
                                    надаст Вам допомогу.
                                </p>

                            </a>
                        </li>
                    </ul>
                    <div class="container-fluid" style="margin-top: 10px">
                        <p  class="gradient text-opacity-25">
                            <b>Вам також буде цікаво:</b>
                        </p>

                        <div class="header gradient" >
                            <a class="borderElement" href="{{route('homeCombo')}}" target="_blank">Шукати адресу</a>
                            <a class="borderElement" href="{{route('homeMapCombo')}}" target="_blank">Пошук по мапи</a>
                            <a  class="borderElement" @guest
                            href="{{ route('callBackForm') }}"
                                @else
                                href="{{ route('callBackForm-phone', Auth::user()->user_phone) }}"
                                @endguest>
                                Допомога у складний час</a>
                            <a class="borderElement" href="{{route('callWorkForm')}}" target="_blank">Робота у таксі</a>
                            <a class="borderElement" href="{{route('home-news')}}" target="_blank">Новини</a>
                        </div>
                    </div>
                </div>
            </div>
    </div>


@endsection
