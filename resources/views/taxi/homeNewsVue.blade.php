

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

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-sm-6 col-md-6">
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
                </div>

                <div class="col-lg-9 col-sm-6" >

                    <div class="row">

                        <ul class="older">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('orderReklama')}}">
                                                <h5 class="text-center"><b>Попереднє замовлення</b></h5>
                                                <small style="text-align: left">
                                                    Можливість оформити замовлення таксі на бажаний час та день.
                                                    Зручно при поїздках до аеропортів та вокзалів Києва. Швидко, надійно та недорого.
                                                    Розрахуйте точну вартість на сайті.
                                                </small>
                                            </a>
                                        </li>
                                    </div>
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('driverReklama')}}">
                                                <h5 class="text-center"><b>Послуга "тверезий водій"</b></h5>
                                                <small style="text-align: left">
                                                    Професійний водій нашої служби акуратно та швидко пережене за вказаною адресою
                                                    або на найближче паркування Ваш автомобіль. Замовити на сайті
                                                </small>
                                            </a>
                                        </li>
                                    </div>
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('stationReklama')}}">
                                                <h5 class="text-center"><b>Таксі на вокзал</b></h5>
                                                <small style="text-align: left">
                                                    Можливість оформити замовлення таксі на бажаний час та день при поїздках до
                                                    вокзалів Києва  та області. Швидко, надійно та недорого. Розрахуйте точну вартість на сайті.
                                                </small>
                                            </a>
                                        </li>
                                    </div>

                                </div>

                            </div>
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('airportReklama')}}">
                                                <h5 class="text-center"><b>Таксі в аеропорти Бориспіль та Жуляни</b></h5>
                                                <small style="text-align: left">
                                                    Оцініть  найвигідніші ціни та якісний сервіс на поїздку в аеропорти міста Києва.
                                                    Розрахуйте вартість онлайн.
                                                </small>
                                            </a>
                                        </li>
                                    </div>
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('regionReklama')}}">
                                                <h5 class="text-center"><b>Дешеве обласне міжміське таксі</b></h5>
                                                <small style="text-align: left">
                                                    Великий парк комфортабельних автомобілів для міжміських поїздок.
                                                    Дізнайтесь на сайті.
                                                </small>
                                            </a>
                                        </li>
                                    </div>
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <li>
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

                            </div>
                        </ul>

                        <ul class="olderOne">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-6 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}">

                                               <h4 class="text-center"> <b>Служба онлайн Таксі Лайт Юа - найкращий
                                                        сервіс організації поїздок на комфортабельних авто у Києві
                                                        та Київської області за доступними цінами</b></h4>
                                                <h5>
                                                    <i>На якому таксі дешевше?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Таксі Лайт Юа - це завжди найнижча онлайн ціна таксі
                                                    Києва та області. Як викликати таксі у місті Києві? Замовити
                                                    найкращу службу таксі Києва та області онлайн на зручний час?
                                                    За допомогою нашої служби Таксі Лайт Юа можна заздалегідь легко
                                                    та швидко зробити замовлення вільного авто таксі на нашому сайті та
                                                    зробити це дешевше онлайн. Швидше пересуватися Києвом -
                                                    це тільки не пішки. Замовте українське таксі та дайте свою
                                                    оцінки. Ухил у Таксі Лайт Юа онлайн ми робимо на якість.
                                                    Працює підтримка 24/7.
                                                </p>
                                                <h5>
                                                    <i>Як зробити замовлення на убер - послуги з перевезення, кур'єрської
                                                    доставки та отримати високу якість обслуговування?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба Таксі Лайт Юа - це економ служба поїздок у Києві та по
                                                    міжміську і це завжди швидко, доступно та недорого.
                                                    Таксі Лайт Юа – це понад 10 років комфортні поїздки. Свій тариф
                                                    дізнайтесь на сайті таксі та отримайте якісний сервіс
                                                    професійних водіїв на комфортних, чистих та свіжих авто,
                                                    ввічливих операторів та за низькими цінами швидше, ніж через дзвінок оператору.
                                                </p>

                                                <h5>
                                                    <i>Як зробити замовлення на убер - послуги з перевезення, кур'єрської
                                                    доставки та отримати високу якість обслуговування?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба  Такси Лайт Юа - это эконом служба поездок в Киеве и по
                                                    межгороду и это  всегда  быстро, доступно и  недорого.
                                                    Такси Лайт Юа - это более 10 лет комфортные поездки. Свой тариф
                                                    узнайте на сайте такси и получите качественный сервис
                                                    профессиональных водителей на комфортных, чистых и свежих авто,
                                                    вежливых операторов и  по низким ценам быстрее, чем через звонок оператору.

                                                </p>

                                                <h5>
                                                    <i>Яке таксі краще замовити у Києві та які є таксі?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба Таксі Лайт Юа - це найкращий вибір завжди дешевого таксі,
                                                    онлайн розрахунок вартості послуг завжди можна зробити на сайті швидше,
                                                    ніж через дзвінок оператору.
                                                </p>

                                                <h5>
                                                    <i>Коли таксі дешевше у Києві?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Є періоди, коли через знижений попит на послугу перевізники
                                                    ставлять мінімальні тарифи на поїздки до таксі. Це будні дні
                                                    (крім п'ятниці) з 11.00 до 15.00, а також вихідні. Нестрокові
                                                    подорожі краще планувати в цей час.
                                                </p>

                                                <h5>
                                                    <i>Скільки коштує таксі в Києві зараз, яке таксі в Києві найдешевше
                                                    і які розцінки на послуги таксі?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба Таксі Лайт Юа – це поїздки містом від 40 грн.
                                                </p>

                                                <h5>
                                                    <i>Яке таксі зараз працює у Києві у воєнний час?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    У комендантську годину Ви можете залишити свій телефон на сайті
                                                    служби Таксі Лайт Юа та наш оператор зв'яжеться з Вами у зручний для Вас час.
                                                </p>

                                                <h5>
                                                    <i>Як замовити таксі вигідно?</i>
                                                </h5>
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
                                                <br>
                                                <h5>
                                                    <i>Ознайомтеся з додатковими послугами нашої служби таксі.</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Таксі Лайт Юа - це замовлення таксі та трансферів в аеропорт, на залізничний
                                                    вокзал та автовокзал, а також зустріч із табличкою. Втомилися від
                                                    водіння або не можете сісти за кермо - замовте послугу
                                                    "тверезий водій" і ми акуратно та швидко доставимо Ваш
                                                    автомобіль до місця стоянки.
                                                </p>

                                                <h5>
                                                    <i>Пропонуємо роботу водіям у нашій службі таксі.</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Залишіть телефон на сайті і зв'яжемося з Вами.
                                                </p>
                                                <h5 style="text-align: center">
                                                    <b>Таксі Лайт Юа – це завжди надійно, комфортно та вигідно.</b>
                                                </h5>
                                            </a>
                                        </li>
                                    </div>
                                    <div class="col-lg-6 col-sm-12 col-md-12">
                                        <li>
                                            <a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}">

                                                <h4 class="text-center">
                                                    <b>Служба онлайн Такси Лайт Юа - наилучший
                                                        сервис организации поездок на комфортабельных авто в Киеве
                                                        и Киевской области по доступным ценам</b></h4>

                                                <h5>
                                                    <i>На каком такси дешевле?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Такси Лайт Юа - это всегда самая низкая  онлайн цена такси
                                                    Киева и области. Как вызвать такси в городе Киеве? Заказать
                                                    лучшую службу такси Киева и области онлайн на удобное время?
                                                    С помощью нашей службы Такси Лайт Юа можно предварительно легко
                                                    и быстро сделать заказ свободного  авто такси на нашем сайте и
                                                    сделать это  дешевле онлайн. Быстрее перемещаться по Киеву -
                                                    это только не пешком. Закажите украинское такси и дайте свою
                                                    оценку. Уклон в Такси Лайт Юа онлайн мы делаем на качество.
                                                    Работает поддержка 24/7.
                                                </p>
                                                <h5>
                                                    <i>Как сделать заказ на убер - услуги по перевозке, курьерской
                                                    доставки и получить высокое качество обслуживания?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба  Такси Лайт Юа - это эконом служба поездок в Киеве и по
                                                    межгороду и это  всегда  быстро, доступно и  недорого.
                                                    Такси Лайт Юа - это более 10 лет комфортные поездки. Свой тариф
                                                    узнайте на сайте такси и получите качественный сервис
                                                    профессиональных водителей на комфортных, чистых и свежих авто,
                                                    вежливых операторов и  по низким ценам быстрее, чем через звонок оператору.

                                                </p>

                                                <h5>
                                                    <i>Как сделать заказ на убер - услуги по перевозке, курьерской
                                                    доставки и получить высокое качество обслуживания?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба  Такси Лайт Юа - это эконом служба поездок в Киеве и по
                                                    межгороду и это  всегда  быстро, доступно и  недорого.
                                                    Такси Лайт Юа - это более 10 лет комфортные поездки. Свой тариф
                                                    узнайте на сайте такси и получите качественный сервис
                                                    профессиональных водителей на комфортных, чистых и свежих авто,
                                                    вежливых операторов и  по низким ценам быстрее, чем через звонок оператору.
                                                </p>

                                                <h5>
                                                    <i>Какое такси лучше заказать в Киеве и какие есть такси?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Служба Такси Лайт Юа - это наилучший выбор всегда дешевого такси,
                                                    онлайн расчет стоимости услуг всегда можно сделать на сайте быстрее чем через звонок оператору.
                                                </p>

                                                <h5>
                                                    <i>Когда такси дешевле в Киеве?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Есть периоды, когда из-за пониженного спроса на услугу перевозчики
                                                    ставят минимальные тарифы на поездки в такси. Это будние дни
                                                    (кроме пятницы) с 11:00 до 15:00, а также выходные. Несрочные поездки лучше планировать в это время

                                                </p>

                                                <h5>
                                                    <i>Сколько стоит такси в Киеве сейчас, какое такси в Киеве самое дешевое
                                                    и какие расценки на такси?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Такси Лайт Юа - это поездки по городу от 40 грн.
                                                </p>

                                                <h5>
                                                    <i>Какое такси работает в Киеве в военное время?</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    В комендантский час Вы можете оставить свой телефон на сайте
                                                    службы Такси Лайт Юа и наш оператор свяжется с Вами в удобное для Вас время.
                                                </p>

                                                <h5>
                                                    <i>Как заказать такси выгодно?</i>
                                                </h5>
                                                <p>
                                                    С помощью службы Такси Лайт Юа
                                                    Вы комфортно и быстро доберетесь до любой точки Киева и Киевской  области.
                                                </p>

                                                <ul style="text-align: justify">
                                                    <li>Выбирайте правильное время подачи</li>
                                                    <li>Не перемещайтесь в час пик по пробкам</li>
                                                    <li>Выбирайте такси с фиксированным тарифом</li>
                                                    <li>Сравнивайте цены в разных службах</li>
                                                    <li>Заказывайте машину заранее</li>
                                                    <li>Правильно стройте маршрут</li>
                                                    <li>Не забывайте про бесплатное время ожидания</li>
                                                    <li>Не ездить с частными «бомбилами»</li>
                                                    <li>Берите с собой попутчиков</li>
                                                    <li>Пользуйтесь  программой лояльности</li>
                                                    <li>Не ловите авто на улице</li>
                                                    <li>Узнайте цену маршрута заранее</li>
                                                </ul>
                                                <br>
                                                <h5>
                                                    <i>Ознакомьтесь с с дополнительными услугами нашей службы такси.</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Такси Лайт Юа - это заказ такси и трансферов в аэропорт, на жд
                                                    вокзал и автовокзал, а также встреча  с табличкой. Устали от
                                                    вождения или не можете сесть за руль - закажите услугу
                                                    “трезвый водитель”  и мы аккуратно и быстро доставим Ваш
                                                    автомобиль к месту стоянки.
                                                </p>

                                                <h5>
                                                    <i>Предлагаем работу водителям в нашей службе такси.</i>
                                                </h5>

                                                <p style="text-align: justify">
                                                    Оставьте телефон на сайте и мы свяжемся с Вами.
                                                </p>

                                                <h5 style="text-align: center">
                                                    <b>Такси Лайт Юа - это всегда надежно, комфортно и выгодно.</b>
                                                </h5>

                                            </a>
                                        </li>
                                    </div>
                                </div>
                            </div>
                        </ul>
                    </div>
                    <div class="row">
                        <news-component></news-component>
                        <div class="container">
                                <a  href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank" style="text-decoration: none;">
                                    <blockquote class="blockquote-3">
                                        <p> <?php echo $quitesArr[$rand] ?></p>
                                        <cite>Цитата дня</cite>
                                    </blockquote>
                                </a>
                            </div>
                    </div>
                </div>
            </div>



        </div>

    <div class="container">
        <a class="but_vart" href="https://easy-order-taxi.site" target="_blank" title="Версія сайту для мобільних"
           style="margin-top: 10px">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-phone" viewBox="0 0 16 16">
                <path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h6zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H5z"/>
                <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
        </a>
    </div>
    <div class="container">
        <a class="but_vart_vart" href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank" title="Розрахунок вартості"
           style="margin-top: 50px">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
            </svg>
        </a>
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
