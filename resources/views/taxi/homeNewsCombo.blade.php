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
            <div class="col-lg-3 col-sm-6 col-md-3">
                <a href="{{route('homeCombo')}}" target="_blank" style="text-decoration: none;">

                    <p  class="gradient text-opacity-25" id="poslugy">
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
                    <a href="{{route('homeCombo')}}" class="gradient-button animate-fading" target="_blank">Замовити таксі</a>
                </div>

                    <p  class="gradient text-opacity-25">
                        <b>Замовити трансфер</b>
                    </p>
                    <div class="row">
                        <div class="slideshow-container">

                            <div class="mySlides fade">
                                <a href="{{route('home')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/kiyv2.jpg') }}" style="width:100%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/UZ.png') }}" style="width:100%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/borispol.png') }}" style="width:100%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/sikorskogo.png') }}" style="width:100%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/auto.jpeg') }}" style="width:100%">

                                </a>
                            </div>

                        </div>
                        <br>

                        <div style="text-align:left">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <a href="{{route('airportReklama')}}" target="_blank"
                               style="text-decoration: none;">До аеропорту "Бориспіль"</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <a href="{{route('airportReklama')}}" target="_blank"
                               style="text-decoration: none;">До аеропорту "Киів" (Жуляни)</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <a href="{{route('stationReklama')}}" target="_blank"
                               style="text-decoration: none;">До залізничного вокзалу</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <a href="{{route('stationReklama')}}" target="_blank"
                               style="text-decoration: none;">До автовокзалу</a>
                        </li>
                    </ul>

                <p  class="gradient text-opacity-25">
                    <b>Додатково</b>
                </p>

                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('callWorkForm')}}" target="_blank"
                           style="text-decoration: none;">Робота в таксі</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('taxi-gdbr')}}" target="_blank"
                           style="text-decoration: none;">Конфіденційність</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('taxi-umovy')}}" target="_blank"
                           style="text-decoration: none;">Умови</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="{{route('feedback')}}" target="_blank"
                           style="text-decoration: none;">Підтримка</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank"
                           style="text-decoration: none;">Ми на Фейсбук</a>
                    </li>
                </ul>
<!--                <p  class="gradient text-opacity-25">
                    <b>Теги</b>
                </p>
                <div class="container-fluid">
                    <div class="row">
                            <div class="borderElement"> такси </div>
                            <div class="borderElement"> uklon </div>
                            <div class="borderElement"> болт такси </div>
                            <div class="borderElement"> уклон такси </div>
                            <div class="borderElement"> такси болт </div>
                            <div class="borderElement"> убер </div>
                            <div class="borderElement"> такси макси </div>
                            <div class="borderElement"> такси уклон </div>
                            <div class="borderElement"> 838 такси </div>
                            <div class="borderElement"> такси 838 </div>
                            <div class="borderElement"> убер такси </div>
                            <div class="borderElement"> такси белая церковь </div>
                            <div class="borderElement"> он такси </div>
                            <div class="borderElement"> уклон телефон </div>
                            <div class="borderElement"> номер такси </div>
                            <div class="borderElement"> bolt такси </div>
                            <div class="borderElement"> uklon номер телефона </div>
                            <div class="borderElement"> заказать такси </div>
                            <div class="borderElement"> такси десятка </div>
                            <div class="borderElement"> такси болт номер телефона </div>
                            <div class="borderElement"> такси уклон телефон </div>
                            <div class="borderElement"> uklon драйвер </div>
                            <div class="borderElement"> вызвать такси </div>
                            <div class="borderElement"> такси онлайн </div>
                            <div class="borderElement"> эконом таксиg </div>
                            <div class="borderElement"> uklon такси </div>
                            <div class="borderElement"> такси болт номер </div>
                            <div class="borderElement"> 292 такси </div>
                            <div class="borderElement"> такси максим номер </div>
                            <div class="borderElement"> такси 292 </div>
                            <div class="borderElement"> служба поддержки болт </div>
                            <div class="borderElement"> uber такси </div>
                            <div class="borderElement"> такси убер </div>
                            <div class="borderElement"> дешевое такси </div>
                            <div class="borderElement"> болт такси телефон </div>
                            <div class="borderElement"> 579 такси </div>
                            <div class="borderElement"> уклон для водителей </div>
                            <div class="borderElement"> любимое такси  </div>
                            <div class="borderElement"> таксометр </div>
                            <div class="borderElement"> служба такси </div>
                            <div class="borderElement"> желтое такси </div>
                            <div class="borderElement"> мое такси </div>
                            <div class="borderElement"> оптима такси </div>
                            <div class="borderElement"> uklon личный кабинет </div>
                            <div class="borderElement"> uklon онлайн </div>
                            <div class="borderElement"> такси эконом </div>
                            <div class="borderElement"> такси межгород </div>
                            <div class="borderElement"> экспресс такси </div>
                            <div class="borderElement"> shark taxi </div>
                            <div class="borderElement"> номер такси болт </div>
                            <div class="borderElement"> вызов такси </div>
                            <div class="borderElement"> самое дешевое такси </div>
                            <div class="borderElement"> такси оптимальное </div>
                            <div class="borderElement"> такси uber </div>
                            <div class="borderElement"> такси заказать </div>
                            <div class="borderElement"> онлайн такси </div>
                    </div>
               </div>-->
                <div class="container-fluid" style="margin-top: 10px">
                    <p  class="gradient">
                        <b>Вам також буде цікаво:</b>
                    </p>

                    <div class="header gradient" >
                        <a href="{{route('homeCombo')}}" target="_blank">Онлайн замовлення за адресою</a>
                        <a href="{{route('homeMapCombo')}}" target="_blank">Замовлення таксі по мапи</a>
                        <a  @guest
                            href="{{ route('callBackForm') }}"
                            @else
                            href="{{ route('callBackForm-phone', Auth::user()->user_phone) }}"
                            @endguest>
                            Допомога у складний час</a>
                    </div>
                </div>
            </div>

             <div class="col-lg-9 col-sm-6 col-md-9" >

                    <div class="container-fluid">
                        <a href="{{route('homeCombo')}}"
                                style="text-decoration: none">
                            <h4 class="text-center text-primary gradient"> <b>Пропонуємо Вам найсучасніший
                            сервіс організації поїздок на комфортабельних авто за доступними цінами.</b></h4>
                        </a>

                    </div>


                 <div class="container-fluid">
                     <div class="row">
                         <ul class="olderOne">
                             <div class="container-fluid">
                                 <div class="row">
                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('orderReklama')}}">
                                             <h5 class="text-center"><b>Попереднє замовлення</b></h5>
                                             <small style="text-align: left">
                                                 Можливість оформити замовлення таксі на бажаний час та день.
                                                 Зручно при поїздках до аеропортів та вокзалів Києва. Швидко, надійно та недорого.
                                                 Розрахуйте точну вартість на сайті.
                                             </small>
                                         </a>
                                     </li>

                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('driverReklama')}}">
                                             <h5 class="text-center"><b>Послуга "тверезий водій"</b></h5>
                                             <small style="text-align: left">
                                                 Професійний водій нашої служби акуратно та швидко пережене за вказаною адресою
                                                 або на найближче паркування Ваш автомобіль. Замовити на сайті
                                             </small>
                                         </a>
                                     </li>
                                 </div>
                             </div>
                             <div class="container-fluid">
                                 <div class="row">
                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('stationReklama')}}">
                                             <h5 class="text-center"><b>Таксі на вокзал</b></h5>
                                             <small style="text-align: left">
                                                 Можливість оформити замовлення таксі на бажаний час та день при поїздках до
                                                 вокзалів Києва  та області. Швидко, надійно та недорого. Розрахуйте точну вартість на сайті.
                                             </small>
                                         </a>
                                     </li>

                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('airportReklama')}}">
                                             <h5 class="text-center"><b>Таксі в аеропорти Бориспіль та Жуляни</b></h5>
                                             <small style="text-align: left">
                                                 Оцініть  найвигідніші ціни та якісний сервіс на поїздку в аеропорти міста Києва.
                                                 Розрахуйте вартість онлайн.
                                             </small>
                                         </a>
                                     </li>
                                 </div>
                             </div>
                             <div class="container-fluid">
                                 <div class="row">
                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('regionReklama')}}">
                                             <h5 class="text-center"><b>Дешеве обласне міжміське таксі</b></h5>
                                             <small style="text-align: left">
                                                 Великий парк комфортабельних автомобілів для міжміських поїздок.
                                                 Дізнайтесь на сайті.
                                             </small>
                                         </a>
                                     </li>

                                     <li class="col-lg-6 col-sm-12">
                                         <a href="{{route('tableReklama')}}">
                                             <h5 class="text-center"><b>Зустріч водієм таксі з табличкою</b></h5>
                                             <small style="text-align: left">
                                                 Водій зустріне вас з табличкою біля виходу з паспортного контролю
                                                 або біля виходу з вагона на вокзалі.
                                             </small>
                                         </a>
                                     </li>
                                 </div>
                             </div>
                         </ul>

                     </div>

                 </div>
                 <div class="container-fluid" style="margin: 10px">
                     <a href="{{route('homeCombo')}}"
                        target="_blank" style="text-decoration: none; color: black">
                         <h5 style="text-align: center; " class="gradient">
                             <b>Служба Таксі Лайт Юа – це завжди надійно, комфортно та вигідно. <br>
                                 Замовьте таксі прям зараз.</b>
                         </h5>
                     </a>
                 </div>

                 <div class="accordion" id="accordionExample">
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingOne">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                 На якому таксі дешевше?
                             </button>
                         </h2>
                         <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('homeCombo')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Таксі Лайт Юа - це завжди найнижча онлайн ціна таксі
                                         Києва та області. Як викликати таксі у місті Києві? Замовити
                                         найкращу службу таксі Києва та області онлайн на зручний час?
                                         За допомогою нашої служби Таксі Лайт Юа можна заздалегідь легко
                                         та швидко зробити замовлення вільного авто таксі на нашому сайті та
                                         зробити це дешевше онлайн. Швидше пересуватися Києвом -
                                         це тільки не пішки. Замовте українське таксі та дайте свою
                                         оцінки. Уклон у Таксі Лайт Юа онлайн ми робимо на якість.
                                         Працює підтримка 24/7.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingTwo">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                 Як зробити замовлення на убер - послуги з перевезення, кур'єрської
                                 доставки та отримати високу якість обслуговування?
                             </button>
                         </h2>
                         <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="#" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Служба Таксі Лайт Юа - це економ служба поїздок у Києві та по
                                         міжміську і це завжди швидко, доступно та недорого.
                                         Таксі Лайт Юа – це понад 10 років комфортні поїздки. Свій тариф
                                         дізнайтесь на сайті таксі та отримайте якісний сервіс
                                         професійних водіїв на комфортних, чистих та свіжих авто,
                                         ввічливих операторів та за низькими цінами швидше, ніж через дзвінок оператору.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingThree">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                 Яке таксі краще замовити у Києві та які є таксі?
                             </button>
                         </h2>
                         <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('homeCombo')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Служба Таксі Лайт Юа - це найкращий вибір завжди дешевого таксі,
                                         онлайн розрахунок вартості послуг завжди можна зробити на сайті швидше,
                                         ніж через дзвінок оператору.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingFour">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                 Коли таксі дешевше у Києві?
                             </button>
                         </h2>
                         <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('homeCombo')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Є періоди, коли через знижений попит на послугу перевізники
                                         ставлять мінімальні тарифи на поїздки до таксі. Це будні дні
                                         (крім п'ятниці) з 11.00 до 15.00, а також вихідні. Нестрокові
                                         подорожі краще планувати в цей час.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingFive">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                 Скільки коштує таксі в Києві зараз, яке таксі в Києві найдешевше
                                 і які розцінки на послуги таксі?
                             </button>
                         </h2>
                         <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('homeCombo')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Служба Таксі Лайт Юа – це поїздки містом від 40 грн.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingSix">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                 Яке таксі зараз працює у Києві у воєнний час?
                             </button>
                         </h2>
                         <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('callBackForm')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         У комендантську годину Ви можете залишити свій телефон на сайті
                                         служби Таксі Лайт Юа та наш оператор зв'яжеться з Вами у зручний для Вас час.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingSeven">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                 Як замовити таксі вигідно?
                             </button>
                         </h2>
                         <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('homeCombo')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p>
                                         За допомогою служби Таксі Лайт Юа
                                         Ви комфортно та швидко дістанетеся до будь-якої точки Києва та області.
                                     </p>
                                     <ul style="text-align: justify">
                                         <li>Вибирайте правильний час подачі</li>
                                         <li>Не переміщайтеся в годину пік пробками</li>
                                         <li>Вибирайте таксі з фіксованим тарифом</li>
                                         <li>Порівнюйте ціни в різних службах</li>
                                         <li>Замовляйте машину заздалегідь</li>
                                         <li>Правильно будуйте маршрут</li>
                                         <li>Не забувайте про безкоштовний час очікування</li>
                                         <li>Не їздити з приватними «бомбілами»</li>
                                         <li>Беріть із собою попутників</li>
                                         <li>Користуйтесь програмою лояльності</li>
                                         <li>Не ловіть авто на вулиці</li>
                                         <li>Дізнайтеся ціну маршруту заздалегідь</li>
                                     </ul>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingEight">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                 Ознайомтеся з додатковими послугами нашої служби таксі.
                             </button>
                         </h2>
                         <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="#poslugy" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Таксі Лайт Юа - це замовлення таксі та трансферів в аеропорт, на залізничний
                                         вокзал та автовокзал, а також зустріч із табличкою. Втомилися від
                                         водіння або не можете сісти за кермо - замовте послугу
                                         "тверезий водій" і ми акуратно та швидко доставимо Ваш
                                         автомобіль до місця стоянки.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                     <div class="accordion-item">
                         <h2 class="accordion-header" id="headingNine">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                 Пропонуємо роботу водіям у нашій службі таксі.
                             </button>
                         </h2>
                         <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#accordionExample">
                             <div class="accordion-body">
                                 <a href="{{route('callWorkForm')}}" target="_blank"
                                    style="text-decoration: none; color: black">
                                     <p style="text-align: justify">
                                         Залишіть телефон на сайті і зв'яжемося з Вами.
                                     </p>
                                 </a>
                             </div>
                         </div>
                     </div>
                 </div>

                 <news-component></news-component>

                 <a  href="{{route('homeCombo')}}" target="_blank" style="text-decoration: none;">
                     <blockquote class="blockquote-3">
                         <p> <?php echo $quitesArr[$rand] ?></p>
                         <cite>Цитата дня</cite>
                     </blockquote>
                 </a>
            </div>
        </div>

    </div>


        <script>
            let slideIndex = 0;
            showSlides();

            function showSlides() {
                let i;
                let slides = document.getElementsByClassName("mySlides");
                let dots = document.getElementsByClassName("dot");
                for (i = 0; i < slides.length; i++) {
                    slides[i].style.display = "none";
                }
                slideIndex++;
                if (slideIndex > slides.length) {slideIndex = 1}
                for (i = 0; i < dots.length; i++) {
                    dots[i].className = dots[i].className.replace(" active", "");
                }
                slides[slideIndex-1].style.display = "block";
                dots[slideIndex-1].className += " active";
                setTimeout(showSlides, 2000); // Change image every 5 seconds
            }
        </script>
@endsection
