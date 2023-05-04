@extends('layouts.bolt')

@section('content')

    <?php

    use App\Http\Controllers\WebOrderController;use App\Models\NewsList;

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
    $news = NewsList::all();

    $randomNewsArr[] = null;
    $i = 0;
    foreach ($news as $value) {
        $newsArr[$i]["id"] = $value["id"];
        $newsArr[$i++]["short"] = $value["short"];
    }

    for ($i = 0; $i <= 4; $i++) {
        $randomNewsArr[$i] = $newsArr[random_int(0, count($newsArr) - 1)];
    }
    ?>

    <div class="container-fluid">
        <div class="row">
            @include ('layouts.servicesShort')

             <div class="col-lg-9 col-sm-9 col-md-9" >
                 <div class="container">
                     <div class="row">
                         <ul class="olderOne">
                             <li>
                                 <a href="{{route('homeCombo')}}">
                                     <h4 class="text-center"><b>Bolt (раніше відома як Taxify)</b></h4>

                                     <p style="text-align: justify">
                                         Естонська компанія, яка надає послуги пошуку, замовлення й оплати поїздок на автомобілі, мотоциклі або оренду електросамокатів через однойменний Мобільний застосунок. Існують 2 застосунки: для пасажирів і водіїв.
                                     </p>
                                     <p style="text-align: justify">
                                         Компанію заснував у 2013 Маркус Вілліг (на той час — 19-річний студент) з метою об'єднати усі служби таксі Талліна і Риги в одну платформу. Сервіс було запущено в серпні 2013, а 2014 року почалося охоплення закордонних ринків
                                     </p>
                                     <p style="text-align: justify">
                                         2014 року Bolt зібрав більше €2 млн первинних інвестицій від бізнес-ангелів з Естонії та Фінляндії.
                                     </p>
                                     <p style="text-align: justify">
                                         У 2017 стартап придбав компану Лондоні, шляхом придбання місцевої таксі-компанії з ліцензією, але був закритий публічно-правовою корпорацією Transport for London[6]. Після цього Bolt (тоді відомий як Taxify) заповнив нову аплікацію аби поновити свою роботу. Невдовзі сервіс запрацював у Парижі та Лісабон
                                     </p>
                                     <p style="text-align: justify">
                                         У травні 2018 концерн Daimler та китайський конгломерат Didi Chuxing (конкурент Uber), інвестували більше $175 мільйонів, після чого компанія була оцінена в мільярд доларів й отримала статус «єдинорога».
                                     </p>
                                     <p style="text-align: justify">
                                         Перший раз сервіс зайшов на український ринок у вересні 2016 в Києві, але не витримав конкуренції з Uber і Uklon. Після отримання 175 мільйонів доларів інвестицій Bolt повернувся до України в червні 2018 з агресивною маркетинговою стратегією, 50 % знижками, промокодами та акціями для водіїв. Повідомлялося про наміри поширити діяльність на інші міста України.
                                     </p>
                                     <p style="text-align: justify">
                                         Тарифи на серпень 2018 становили: 25 грн подача, 4,5 грн за км, 1,15 грн/хв шляху. Мінімальний тариф 40 грн.
                                     </p>
                                     <p style="text-align: justify">
                                         4 грудня 2018 почав роботу в Харкові. Базовий тариф станом на запуск становив: 30 грн. подача, 4 грн/км шляху, та 0,90 грн/хв за рух та очікування, мінімальна вартість поїздки — 50 грн. Також зі старту став доступний тариф Comfort: 30 грн. подача, 6,5 грн/км шляху, 1,2 грн/хв за рух та очікування, мінімальна вартість поїздки — 60 грн.
                                     </p>
                                     <p style="text-align: justify">
                                         Влітку 2019 оголосили про партнерство зі страховою компанією «УНІКА» і страхування кожної поїздки на суму до 1 мільйона гривень.
                                     </p>
                                     <p style="text-align: justify">
                                         Для замовлення авто доступні такі категорії: Bolt; Comfort; Isolated (із перегородкою між водієм та пасажиром); Pets (перевезення тварин).
                                     </p>
                                 </a>
                             </li>
                         </ul>
                         <div class="fb-comments" data-href="https://m.easy-order-taxi.site" data-width="auto" data-numposts="5"></div>
                         <div class="container">
                             <p  class="gradient text-opacity-25">
                                 <b>Читати ще:</b>
                             </p>
                             <ul class="border">
                                 @foreach($randomNewsArr as $value)
                                     <li>
                                         <a href="/breakingNews/{{$value['id']}}"
                                            target="_blank"
                                            style="text-decoration: none;
                                                    color: black";>{{$value["short"]}}...</a>
                                     </li>
                                 @endforeach
                             </ul>
                         </div>


                         <div class="container-fluid" style="margin-top: 10px">
                             <p  class="gradient text-opacity-25">
                                 <b>Вам також буде цікаво:</b>
                             </p>

                             <div class="header gradient" >
                                 <a class="borderElement" href="{{route('homeCombo')}}" target="_blank">Шукати адресу</a>
                                 <a class="borderElement" href="{{route('homeMapCombo')}}" target="_blank">Пошук по мапи</a>
                                 <a  class="borderElement"
                                     href="{{ route('callBackForm') }}">
                                     Допомога у складний час</a>
                                 <a class="borderElement" href="{{route('callWorkForm')}}" target="_blank">Робота у таксі</a>
                                 <a class="borderElement" href="{{route('home-news')}}" target="_blank">Новини</a>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="container-fluid" style="margin: 10px">
                     <a href="{{route('homeCombo')}}"
                        target="_blank" style="text-decoration: none; color: black"
                        onclick="sessionStorage.clear();">
                         <h5 style="text-align: center; " class="gradient">
                             <b>Служба Таксі Лайт Юа – це завжди надійно, комфортно та вигідно. <br>
                                 Замовьте таксі прям зараз.</b>
                         </h5>
                     </a>
                 </div>

            </div>
        </div>

    </div>


@endsection
