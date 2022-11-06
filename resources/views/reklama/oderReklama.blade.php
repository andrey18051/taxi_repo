@extends('layouts.newsList')

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
                <a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank" style="text-decoration: none;">
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
                    <a href="{{route('home')}}" class="gradient-button" target="_blank">Замовити таксі</a>
                </div>
            </div>

            <div class="col-lg-9 col-sm-6">
                <div class="row">
                    <ul class="olderOne">
                        <li>
                            <a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}">
                                <b><h5 class="text-center">Попереднє замовлення</h5></b>
                                <p style="text-align: justify">
                                    Можливість оформити замовлення таксі на бажаний час та день.
                                    Зручно при поїздках до аеропортів та вокзалів Києва. Швидко, надійно та недорого.
                                    Розрахуйте точну вартість на сайті.
                                </p>

                                <p style="text-align: justify">
                                    Нерідкі випадки, коли поїздка планується заздалегідь.
                                    Найчастіше автомобіль таксі потрібно безпосередньо перед виходом. Вам неодмінно
                                    підійде послуга попереднє замовлення таксі, яка допоможе спланувати і викликати
                                    службу «Таксі Лайт Юа» на необхідний час.
                                </p>

                                <p style="text-align: justify">
                                    Якщо ви вирішили спланувати свою поїздку заздалегідь, то на сторінках нашого сайту
                                    Ви можете розрахувати точну вартість поїздки, а також оформити попереднє замовлення
                                    таксі. Для цього буде потрібно вказати маршрут майбутньої поїздки, а також дату та
                                    час. Служба «Таксі Лайт Юа» пропонує найбільш доступні ціни таксі, а також ви можете
                                    заздалегідь замовити будь-який з класів авто (економ, стандарт та  преміум  класу).
                                </p>

                                <p style="text-align: justify">
                                    Попереднє замовлення можна оформити за допомогою форми на
                                    нашому сайті, або  залишити свій номер у формі обратного зв'язку на сайті.
                                    Оператор розрахує точну вартість та підбере найбільш підходящий автомобіль.
                                </p>
                            </a>
                        </li>
                    </ul>
            </div>
        </div>
    </div>

@endsection
