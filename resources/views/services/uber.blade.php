@extends('layouts.uber')

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
                                     <h4 class="text-center"><b>Uber Technologies Inc. ; Uber (Убер)</b></h4>

                                     <p style="text-align: justify">
                                         Американська компанія, що створила однойменний мобільний застосунок для пошуку, виклику та оплати таксі або приватних водіїв. Застосунок доступний у більш ніж 200 великих містах в 67 країнах (крім Канади) світу. З роками, декілька інших компаній успадкували подібну модель бізнесу, яка в подальшому отримала назву «уберифікація» (англ. Uberification) або «уберизація».З погляду ширшого економіко-суспільного контексту компанія працює в рамках концепції економіки спільної участі.
                                     </p>
                                     <p style="text-align: justify">
                                     Убер був заснований у 2009 році під назвою UberCab — Гаретом Грампом (співзасновником компанії StumbleUpon), та Тревісом Каланіком, який на той час продав свій стартап Red Swoosh за 19 мільйонів доларів 2007 року. Назва «Убер» — походить від сленгового слова «uber», яке означає «найвищий» або «супер», і має походження від німецького слова über, що означає «над», «понад».
                                     </p>
                                     <p style="text-align: justify">
                                     Каланік приєднався до Кемпа та дав йому «повний кредит ідеї» за Uber. Кемп та його друзі витратили 800$ найнявши приватного водія та з тих пір роздумували над шляхами зниження вартості послуг чорного автомобіля. Він зрозумів, що розділювати витрати з людьми могло б стати доступним і його ідея перетворилася на Убер.
                                     </p>
                                     <p style="text-align: justify">
                                     На одному з перших заходів у Сан Франциско присвячених Уберові, Каланік сказав: «Гарет — це людина, яка придумала цю маячню́». Перший прототип був створений Кемпом та його друзями Оскаром Салазаром і Конрадом Віланом, разом з Каланіком, котрий в компанії був «мега консультантом».
                                     </p>
                                     <p style="text-align: justify">
                                     Першим містом, де в травні 2010 була запущена бета версія Уберу, був Сан-Франциско. Мобільний застосунок офіційно запустили в 2011. Спочатку застосунок дозволяв користувачам використовувати тільки чорні авто підвищеного комфорту і ціна була в 1.5 разів вища, ніж на таксі.
                                     </p>
                                     <p style="text-align: justify">
                                     Ідея створення «Уберу» прийшла до Тревіса Каланіка в Парижі, коли він намагався знайти таксі, щоб дістатися до місця проведення конференції 2008 LeWeb. У березні 2009 року, Тревіс разом з Гаретом Кемпом засновував компанію «UberCab», а вже через рік у червні 2010 компанія запустила власний сервіс у Сан-Франциско. З моменту заснування компанії крісло CEO займає Раян Грейвс (англ. Ryan Graves), котрий в подальшому поступився посадою Тревісу Каланіку.
                                     </p>
                                     <p style="text-align: justify">
                                     З моменту запуску сервісу, водії які беруть участь в системі Uber, могли користуватися лише автомобілями представницького класу Lincoln Town Car, Cadillac Escalade, BMW 7, Mercedes-Benz S550, з 2012 року список доступних автомобілів було розширено (новий сервіс отримав назву «UberX»).
                                     </p>
                                     <p style="text-align: justify">
                                     19 березня 2018 року безпілотний автомобіль компанії, що проходив тестування технології безпілотного водіння в Аризоні, США, скоїв наїзд на пішохода, останній загинув.
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
