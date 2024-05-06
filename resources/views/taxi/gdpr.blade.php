@extends('layouts.taxiNewCombo')

@section('content')

    <div class="container-fluid">
        <div class="row">
            @include ('layouts.services')

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
                <div class="container" style="background-color: hsl(0, 0%, 96%)">
{{--                    <br>--}}
{{--                    <div class="container" style="text-align: center">--}}
{{--                        <a href="https://m.easy-order-taxi.site/taxi-privacy_policy">--}}
{{--                            <h1 class="gradient"><b>Privacy Policy</b></h1>--}}
{{--                        </a>--}}
{{--                    </div>--}}
{{--                    <div class="container">--}}
{{--                        <div class="row gx-lg-5 align-items-center">--}}
{{--                            <div class="text-center">--}}

{{--                                <h3>1. Collection and use of personal data</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    We value your trust and are committed to ensuring that your personal data is securely protected. This privacy policy explains how we collect, use and protect your personal data as part of your use of our app.--}}
{{--                                </p>--}}
{{--                                <h3>2. Aggregate data</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    To ensure the functionality of ordering a taxi in our application, we collect and store users' mobile phone numbers. These data are necessary to contact you and provide the taxi ordering service.--}}
{{--                                </p>--}}
{{--                                <h3>3. Data transmission</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    Your mobile phone numbers are shared with our dispatcher to find available cars to fulfill your taxi order. We guarantee that your personal data will be used exclusively within the framework of the taxi ordering service.--}}
{{--                                </p>--}}
{{--                                <h3>4. Consent to data processing</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    By using our application and providing us with your personal data, you consent to the collection, use and transfer of that data in accordance with this privacy policy.--}}
{{--                                </p>--}}
{{--                                <h3>5. Data protection</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    We take all necessary measures to ensure the security of your personal data and prevent unauthorized access to them.--}}
{{--                                </p>--}}
{{--                                <h3>6. Applicability of legislation</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    Our application complies with the General Data Protection Regulation <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0679">(GDPR)</a> of the European of the Union regarding the collection, use and processing of personal data.--}}

{{--                                </p>--}}
{{--                                <h3>7. Your rights</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    You have the right at any time to request access to your personal data, to make changes or to remove it from our database. To do this, contact us at taxi.easy.ua@gmail.com.--}}
{{--                                </p>--}}
{{--                                <h3>8. Consent and changes</h3>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    By using our app, you agree to the terms of our privacy policy. We reserve the right to make changes to this policy and you will be notified.--}}
{{--                                </p>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    You have the right at any time to request access to your personal data, to make changes or to remove it from our database. To do this, contact us at taxi.easy.ua@gmail.com.--}}
{{--                                </p>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    If you have any further questions or inquiries regarding your privacy and data, please contact us at the contact address below.--}}
{{--                                </p>--}}

{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    Effective date: 01/01/2023--}}
{{--                                </p>--}}
{{--                                <p class="lead" style="text-align: justify">--}}
{{--                                    Contact information: taxi.easy.ua@gmail.com--}}
{{--                                <p class="lead"> Please familiarize yourself with--}}
{{--                                    <a href="{{ route('taxi-umovy') }}">Terms of service.</a>--}}
{{--                                </p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                <div class="container" style="background-color: hsl(0, 0%, 96%)">
                    <br>
                    <div class="container" style="text-align: center">
                        <a href="https://m.easy-order-taxi.site/taxi-privacy_policy">
                            <h1 class="gradient"><b>Політика конфіденційності</b></h1>
                        </a>
                    </div>
                    <div class="container">
                        <div class="row gx-lg-5 align-items-center">
                            <div class="text-center">

                                <h3>1. Збір та використання особистих даних</h3>
                                <p class="lead" style="text-align: justify">
                                    Ми цінуємо вашу довірливість та прагнемо забезпечити надійний захист ваших особистих даних. Ця політика конфіденційності пояснює, як ми збираємо, використовуємо та захищаємо ваші особисті дані в рамках використання нашої програми а також інших додатків, автором якіх є Андрій Коржов.
                                </p>
                                <h3>2. Збірні дані</h3>
                                <p class="lead" style="text-align: justify">
                                    Для забезпечення функціональності замовлення таксі у нашому додатку, ми збираємо та зберігаємо номери мобільних телефонів користувачів. Ці дані необхідні для зв'язку з вами та забезпечення послуги замовлення таксі.
                                </p>
                                <h3>3. Передача даних</h3>
                                <p class="lead" style="text-align: justify">
                                    Ваші номери мобільних телефонів передаються нашому диспетчеру для пошуку доступних автомобілів для виконання замовлення таксі. Ми гарантуємо, що ваші особисті дані будуть використані виключно в рамках надання послуги замовлення таксі.
                                </p>
                                <h3>4. Згода на обробку даних</h3>
                                <p class="lead" style="text-align: justify">
                                    Використовуючи нашу програму та надаючи нам свої особисті дані, ви висловлюєте свою згоду на збір, використання та передачу цих даних відповідно до цієї політики конфіденційності.
                                </p>
                                <h3>5. Захист даних</h3>
                                <p class="lead" style="text-align: justify">
                                    Ми докладаємо всіх необхідних заходів для забезпечення безпеки ваших особистих даних та запобігання несанкціонованому доступу до них.
                                </p>
                                <h3>6. Застосовність законодавства</h3>
                                <p class="lead" style="text-align: justify">
                                    Наш додаток дотримується положень Загального регламенту захисту персональних даних <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0679">(GDPR)</a> Європейського Союзу щодо збору, використання та обробки особистих даних.

                                </p>
                                <h3>7. Ваші права</h3>
                                <p class="lead" style="text-align: justify">
                                    Ви маєте право в будь-який час запросити доступ до ваших особистих даних, внести зміни або видалити їх з нашої бази даних. Для цього зв'яжіться з нами за адресою taxi.easy.ua@gmail.com.
                                </p>
                                <h3>8. Згода та зміни</h3>
                                <p class="lead" style="text-align: justify">
                                    Використовуючи нашу програму, ви погоджуєтесь з умовами нашої політики конфіденційності. Ми залишаємо за собою право вносити зміни до цієї політики, про що ви будете повідомлені.
                                </p>
                                <p class="lead" style="text-align: justify">
                                    Ви маєте право в будь-який час запросити доступ до ваших особистих даних, внести зміни або видалити їх з нашої бази даних. Для цього зв'яжіться з нами за адресою taxi.easy.ua@gmail.com.
                                </p>
                                <p class="lead" style="text-align: justify">
                                    Якщо у вас є додаткові запитання або запити щодо вашої конфіденційності та даних, будь ласка, зв'яжіться з нами за вказаною нижче контактною адресою.
                                </p>

                                <p class="lead" style="text-align: justify">
                                    Дата набрання чинності: 01/01/2023
                                </p>
                                <p class="lead" style="text-align: justify">
                                    Контактна інформація: taxi.easy.ua@gmail.com
                                <p class="lead"> Будь ласка, ознайомтесь з
                                    <a href="{{ route('taxi-umovy') }}">Умовами користування сервісом.</a>
                                </p>
                            </div>
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
                    <br>
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
    </script>

@endsection
