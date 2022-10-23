

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
                                    <img src="{{ asset('img/kiyv2.jpg') }}" style="width:95%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/UZ.png') }}" style="width:95%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/borispol.png') }}" style="width:95%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/sikorskogo.png') }}" style="width:95%">

                                </a>
                            </div>

                            <div class="mySlides fade">
                                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                                    <img src="{{ asset('img/auto.jpeg') }}" style="width:95%">

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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
                                    <div class="col-lg-4 col-sm-6 col-md-6">
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
        <div class="container-fluid">
            <div class="row">

                <div class="offset-lg-3 col-lg-9 col-sm-6">

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
