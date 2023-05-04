@extends('layouts.uklon')

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
                                     <h4 class="text-center"><b>Uklon (укр. Уклон) </b></h4>

                                     <p style="text-align: justify">
                                         Український райдхейлінг сервіс, працює у 27 містах. Uklon об'єднує десятки тисяч водіїв, які разом виконують більш ніж 2 млн поїздок на місяць.
                                     </p>
                                     <p style="text-align: justify">
                                         Uklon започаткувався як проєкт компанії Evos, яка займалася розробкою ПЗ для служб таксі. 2006 року Evos заснував Дмитро Дубровський. На той час в Evos було близько 20 працівників, з них Дмитро Дубровський був генеральним директором, Вікторія Дубровська займалася фінансами, Сергій Смусь відповідав за продажі. Розробкою продукту Uklon займався випускник Києво-Могилянської академії — 21-річний математик Віталій Дятленко. Уже в 2008 Сергій та Віталій стали співвласниками Evos.
                                     </p>
                                     <p style="text-align: justify">
                                         Початкові інвестиції в проєкт склали $10 тис., за пів року було створено сайт з замовленням авто. За пів року був розроблений багатофункціональний вебпортал, де для онлайн-замовлення авто виділялася одна із вкладок. На цій сторінці можна було викликати таксі без «розмови з диспетчером». Uklon розпочав роботу 25 березня 2010 року. Відтоді сервіс виклику авто трансформувався з порталу з вкладкою виклику таксі у великий хмарний продукт. Станом на 2013 рік сервіс співпрацював із більш ніж 100 службами таксі, тоді через Uklon приходило 500—1000 викликів на добу. Уже у 2014 році агрегатор став самоокупним. Evos та Uklon розпочали працювати як дві окремі компанії з 2015 року.
                                     </p>
                                     <p style="text-align: justify">
                                         Назва компанії є скороченням від «Ukraine online» — інтернет-порталу, який створений засновниками сервісу. У 2012-му був випущений перший додаток для Android, у 2013 — для iOS. У 2016 році компанія впровадила оплату проїзду банківською карткою.
                                     </p>
                                     <p style="text-align: justify">
                                         У 2017—2018 роках Uklon повністю переробив застосунок, систему рейтингу та боротьби з шахрайством, покращив карти, алгоритми подачі авто, ввів страхування пасажирів і водіїв, а також здійснив ребрендинг.
                                     </p>
                                     <p style="text-align: justify">
                                         У 2019 Uklon спільно зі страховою компанією ARX збільшили суму страхування пасажирів до 1 млн грн, попередньо послугу страхування запустили у 2017 році.
                                     </p>
                                     <p style="text-align: justify">
                                         Uklon найняв компанію Dragon Capital у 2019-му для пошуку інвестора, гроші спрямовувалися б для виходу в деякі країни Африки. Через пандемію COVID-19 перша експансія так і не відбулася.
                                     </p>
                                     <p style="text-align: justify">
                                         4 грудня 2018 почав роботу в Харкові. Базовий тариф станом на запуск становив: 30 грн. подача, 4 грн/км шляху, та 0,90 грн/хв за рух та очікування, мінімальна вартість поїздки — 50 грн. Також зі старту став доступний тариф Comfort: 30 грн. подача, 6,5 грн/км шляху, 1,2 грн/хв за рух та очікування, мінімальна вартість поїздки — 60 грн.
                                     </p>
                                     <p style="text-align: justify">
                                         Влітку 2019 оголосили про партнерство зі страховою компанією «УНІКА» і страхування кожної поїздки на суму до 1 мільйона гривень.
                                     </p>
                                     <p style="text-align: justify">
                                         Восени 2021-го Uklon анонсував нові опції, які дбають про безпеку пасажирів і водіїв. З'явилися «Чорні списки» для райдерів і драйверів та «Контроль швидкості» для драйверів[
                                     </p>
                                     <p style="text-align: justify">
                                         У травні 2022-го Uklon оголосив про запуск франшизи для глобальної експансії на нові ринки.
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
