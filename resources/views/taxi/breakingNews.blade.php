@extends('layouts.Canonical')

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
            @include ('layouts.servicesShort')

             <div class="col-lg-9 col-sm-9 col-md-9" >
                 <div class="container">
                     <div class="row">
                         <ul class="olderOne">
                             <li>
                                 <a href="{{route('homeCombo')}}">
                                     <h4 class="text-center"><b>{{$news->short}}</b></h4>

                                     <p style="text-align: justify">
                                         {{$news->full}}
                                     </p>
                                     <p>
                                     Джерело: <i>{{$news->author}}</i>
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


                 <div class="fb-comments" data-href="https://m.easy-order-taxi.site" data-width="auto" data-numposts="5"></div>

            </div>
        </div>

    </div>


@endsection
