@extends('layouts.terminal')

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
                                     <h4 class="text-center"><b>Служба "Термінал"</b></h4>

                                     <p style="text-align: justify">
                                         Пропонує послуги таксі за найвигіднішими тарифами.
                                     </p>
                                     <p style="text-align: justify">
                                         Головними та важливими нашими перевагами є низькі ціни та високі стандарти обслуговування 👍☎. Наша служба є однією з найдешевших таксі в регіоні.
                                     </p>
                                     <p style="text-align: justify">
                                         Ми пропонуємо не лише найнижчі ціни, а й автомобілі високого рівня комфорту. Ви заощадите гроші і при цьому з комфортом доїдете в потрібне місце.
                                     </p>
                                     <p style="text-align: justify">
                                         У нас ви можете замовити таксі наступних видів:
                                     </p>
                                     <p style="text-align: justify">
                                         - Безкоштовно розрахунок вартості через сайт або диспетчера💕;
                                     </p>
                                     <p style="text-align: justify">
                                         - онлайн ✅ та попередньо;
                                     </p>
                                     <p style="text-align: justify">
                                         - пасажирське та вантажне;
                                     </p>
                                     <p style="text-align: justify">
                                         - легкове та мікроавтобуси;
                                     </p>
                                     <p style="text-align: justify">
                                         - зустріч в аеропорту;
                                     </p>
                                     <p style="text-align: justify">
                                         - трансфер та кур'єрську доставку;
                                     </p>
                                     <p style="text-align: justify">
                                        - готівкове та безготівкове обслуговування;
                                     </p>
                                     <p style="text-align: justify">
                                         - VIP-таксі (ювілеї, зустрічі, весілля).
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
