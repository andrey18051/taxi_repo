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

            <div class="col-lg-9 col-sm-6 col-md-9" >

                <div class="container-fluid">
                    <a href="{{route('homeCombo')}}"
                       style="text-decoration: none" onclick="sessionStorage.clear();">
                        <h4 class="text-center text-primary gradient"> <b>Віджети для використання на сайті.</b></h4>
                    </a>

                </div>


                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Робота в таксі
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <iframe
                                                    src="https://m.easy-order-taxi.site/widgets/job" frameborder="0" marginheight=0 marginwidth=0 height="1000px"  width="600px">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="card"  style="margin-top: 5px">
                                    <div class="card-body">
                                        <div class="container">
                                            <div class="row">
                                                <a onclick="myFunction()" class="col-12 col-form-label text-md-end" title="Копіювати">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                                                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                                                        <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                                                    </svg>
                                                </a>
                                                <div class="col-md-12">
                                                    <textarea id="myInput" name="myInput" cols="3" class="form-control">
&lt;iframe src=&quot;https://m.easy-order-taxi.site/widgets/job&quot; frameborder=&quot;0&quot;
marginheight=0 marginwidth=0 height=&quot;1000px&quot;  width=&quot;600px&quot;&gt;&lt;/iframe&gt;
                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="card"style="margin-top: 5px; align-items: center">
                                    <div class="card-body">
                                        <div class="container">
                                            <div class="row gradient">
                                                <a  class="borderElement"
                                                    href="mailto:taxi.easy.ua@gmail.com">Переробка віджету на замовлення</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                               Резерв
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">

                            </div>
                        </div>
                    </div>
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingThree">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">--}}
{{--                                Яке таксі краще замовити у Києві та які є таксі?--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('homeCombo')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black" onclick="sessionStorage.clear();">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        Служба Таксі Лайт Юа - це найкращий вибір завжди дешевого таксі,--}}
{{--                                        онлайн розрахунок вартості послуг завжди можна зробити на сайті швидше,--}}
{{--                                        ніж через дзвінок оператору.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingFour">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">--}}
{{--                                Коли таксі дешевше у Києві?--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('homeCombo')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black" onclick="sessionStorage.clear();">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        Є періоди, коли через знижений попит на послугу перевізники--}}
{{--                                        ставлять мінімальні тарифи на поїздки до таксі. Це будні дні--}}
{{--                                        (крім п'ятниці) з 11.00 до 15.00, а також вихідні. Нестрокові--}}
{{--                                        подорожі краще планувати в цей час.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingFive">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">--}}
{{--                                Скільки коштує таксі в Києві зараз, яке таксі в Києві найдешевше--}}
{{--                                і які розцінки на послуги таксі?--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('homeCombo')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black" onclick="sessionStorage.clear();">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        Служба Таксі Лайт Юа – це поїздки містом від 40 грн.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingSix">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">--}}
{{--                                Яке таксі зараз працює у Києві у воєнний час?--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('callBackForm')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        У комендантську годину Ви можете залишити свій телефон на сайті--}}
{{--                                        служби Таксі Лайт Юа та наш оператор зв'яжеться з Вами у зручний для Вас час.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingSeven">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">--}}
{{--                                Як замовити таксі вигідно?--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('homeCombo')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black" onclick="sessionStorage.clear();">--}}
{{--                                    <p>--}}
{{--                                        За допомогою служби Таксі Лайт Юа--}}
{{--                                        Ви комфортно та швидко дістанетеся до будь-якої точки Києва та області.--}}
{{--                                    </p>--}}
{{--                                    <ul style="text-align: justify">--}}
{{--                                        <li>Вибирайте правильний час подачі</li>--}}
{{--                                        <li>Не переміщайтеся в годину пік пробками</li>--}}
{{--                                        <li>Вибирайте таксі з фіксованим тарифом</li>--}}
{{--                                        <li>Порівнюйте ціни в різних службах</li>--}}
{{--                                        <li>Замовляйте машину заздалегідь</li>--}}
{{--                                        <li>Правильно будуйте маршрут</li>--}}
{{--                                        <li>Не забувайте про безкоштовний час очікування</li>--}}
{{--                                        <li>Не їздити з приватними «бомбілами»</li>--}}
{{--                                        <li>Беріть із собою попутників</li>--}}
{{--                                        <li>Користуйтесь програмою лояльності</li>--}}
{{--                                        <li>Не ловіть авто на вулиці</li>--}}
{{--                                        <li>Дізнайтеся ціну маршруту заздалегідь</li>--}}
{{--                                    </ul>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingEight">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">--}}
{{--                                Ознайомтеся з додатковими послугами нашої служби таксі.--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="#poslugy" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        Таксі Лайт Юа - це замовлення таксі та трансферів в аеропорт, на залізничний--}}
{{--                                        вокзал та автовокзал, а також зустріч із табличкою. Втомилися від--}}
{{--                                        водіння або не можете сісти за кермо - замовте послугу--}}
{{--                                        "тверезий водій" і ми акуратно та швидко доставимо Ваш--}}
{{--                                        автомобіль до місця стоянки.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="accordion-item">--}}
{{--                        <h2 class="accordion-header" id="headingNine">--}}
{{--                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">--}}
{{--                                Пропонуємо роботу водіям у нашій службі таксі.--}}
{{--                            </button>--}}
{{--                        </h2>--}}
{{--                        <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#accordionExample">--}}
{{--                            <div class="accordion-body">--}}
{{--                                <a href="{{route('callWorkForm')}}" target="_blank"--}}
{{--                                   style="text-decoration: none; color: black">--}}
{{--                                    <p style="text-align: justify">--}}
{{--                                        Залишіть телефон на сайті і зв'яжемося з Вами.--}}
{{--                                    </p>--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                </div>



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

        function myFunction() {
            /* Get the text field */
            var copyText = document.getElementById("myInput");

            /* Select the text field */
            copyText.select();

            /* Copy the text inside the text field */
            document.execCommand("copy");

            /* Alert the copied text */
            alert("Copied the text: " + copyText.value);
        }
    </script>

@endsection

