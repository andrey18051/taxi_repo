<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9KNVGMXW35"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-9KNVGMXW35');
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all" />
    <link rel="canonical" href="https://m.easy-order-taxi.site/" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="facebook-domain-verification" content="p8dbwrrn8d9oraatsor7slc8pql5dv" />
   <title>Служба Таксі &#128662 від 40 грн по місту (Києв та Київська область)</title>
    <meta name="description" content="Швидкі та надійні поїздки &#129523.  Подання таксі &#128662 за 4-6 хвилин. Працюємо більше 10 років. Доступні низькі тарифи &#127974. Замовлення на сайті та у смартфоні &#128241 комфортного таксі &#128662.  Кур'єрська доставка документів &#128462 та посилок &#128230.">

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">

    <!-- Global site tag (gtag.js) - Google Ads: 999615800 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-999615800"></script>

    <!-- Scripts-->
    <script>
        var months = new Array(13);
        months[1]="січня"; months[2]="лютого"; months[3]="березня"; months[4]="квітня"; months[5]="травня";
        months[6]="червня"; months[7]="липня"; months[8]="серпня"; months[9]="вересня"; months[10]="жовтня";
        months[11]="листопада"; months[12]="грудня";

        var time = new Date();
        var thismonth = months[time.getMonth() + 1];
        var date = time.getDate();
        var thisyear = time.getYear();
        var day = time.getDay() + 1;

        if (thisyear < 2000)
            thisyear = thisyear + 1900;
        if (day == 1) DayofWeek = "Неділя";
        if (day == 2) DayofWeek = "Понеділок";
        if (day == 3) DayofWeek = "Вівторок";
        if (day == 4) DayofWeek = "Середа";
        if (day == 5) DayofWeek = "Четвер";
        if (day == 6) DayofWeek = "П'ятниця";
        if (day == 7) DayofWeek = "Субота";

    </script>
    <script>
        setInterval(function() {
            var cd = new Date();
            var clockdat = document.getElementById("clockdat");
            clockdat.innerHTML = cd.toLocaleTimeString();
        }, 1000);
    </script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>
    <script type="text/javascript" src="{{ asset('js/jquery.cookie.js') }}"></script>
    <!-- Scripts copyPastAdd-->
    <script type="text/javascript">
        (function($) {
            $(function() {
                function addLink() {
                    var body_element = document.getElementsByTagName ('body') [0];
                    var html = "";
                    if (typeof window.getSelection != "undefined") {
                        var selection = window.getSelection();
                        if (selection.rangeCount) {
                            var container = document.createElement("div");
                            for (var i = 0, len = selection.rangeCount; i < len; ++i) {
                                container.appendChild(selection.getRangeAt(i).cloneContents());
                            }
                            html = container.innerHTML;
                        }
                    } else {
                        return;
                    }
                    if (html.toString().split(' ').length < 10) {
                        return;
                    }

                    var pagelink = "<br/><br/> Источник: <a href='" + document.location.href+ "'>"  +document.location.href+ "</a> © Таксі Лайт Юа";
                    var copytext = html + ' ' + pagelink;
                    var newdiv = document.createElement('div');
                    newdiv.style.position = 'absolute';
                    newdiv.style.left = '-99999px';
                    body_element.appendChild(newdiv);
                    newdiv.innerHTML = copytext;
                    selection.selectAllChildren(newdiv);
                    window.setTimeout(function() {
                        body_element.removeChild(newdiv);
                    },0);
                }
                document.oncopy = addLink;
            });
        })(jQuery);
    </script>
    <!-- Styles -->
    <link rel="stylesheet" href="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/base/jquery-ui.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/appAdd.css') }}" rel="stylesheet">
    <style type="text/css">

        /* Всплывающее окно
        * при загрузке сайта
        */
        /* базовый контейнер, фон затемнения*/
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            display: none;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: 999;
            -webkit-animation: fade .6s;
            -moz-animation: fade .6s;
            animation: fade .6s;
            overflow: auto;
        }
        /* модальный блок */
        .popup {
            top: 25%;
            left: 0;
            right: 0;
            font-size: 14px;
            margin: auto;
            width: 85%;
            min-width: 320px;
            max-width: 600px;
            position: absolute;
            padding: 15px 20px;
            border: 1px solid #383838;
            background: #fefefe;
            z-index: 1000;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            -ms-border-radius: 4px;
            border-radius: 4px;
            font: 14px/18px 'Tahoma', Arial, sans-serif;
            -webkit-box-shadow: 0 15px 20px rgba(0,0,0,.22),0 19px 60px rgba(0,0,0,.3);
            -moz-box-shadow: 0 15px 20px rgba(0,0,0,.22),0 19px 60px rgba(0,0,0,.3);
            -ms-box-shadow: 0 15px 20px rgba(0,0,0,.22),0 19px 60px rgba(0,0,0,.3);
            box-shadow: 0 15px 20px rgba(0,0,0,.22),0 19px 60px rgba(0,0,0,.3);
            -webkit-animation: fade .6s;
            -moz-animation: fade .6s;
            animation: fade .6s;
        }
        /* заголовки в модальном блоке */
        .popup h2, .popup h3 {
            margin: 0 0 1rem 0;
            font-weight: 300;
            line-height: 1.3;
            color: #009032;
            text-shadow: 1px 2px 4px #ddd;
        }
        /* кнопка закрытия */
        .close {
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            position: absolute;
            border: none;
            -webkit-border-radius: 50%;
            -moz-border-radius: 50%;
            -ms-border-radius: 50%;
            -o-border-radius: 50%;
            border-radius: 50%;
            background-color: rgba(0, 131, 119, 0.9);
            -webkit-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            -moz-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            cursor: pointer;
            outline: none;

        }
        .close:before {
            color: rgba(255, 255, 255, 0.9);
            content: "X";
            font-family:  Arial, Helvetica, sans-serif;
            font-size: 14px;
            font-weight: normal;
            text-decoration: none;
            text-shadow: 0 -1px rgba(0, 0, 0, 0.9);
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            transition: all 0.5s;
        }
        /* кнопка закрытия при наведении */
        .close:hover {
            background-color: rgba(252, 20, 0, 0.8);
        }
        /* изображения в модальном окне */
        .popup img {
            width: 100%;
            height: auto;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
        }
        /* миниатюры изображений */
        .pl-left,
        .pl-right {
            width: 25%;
            height: auto;
        }
        /* миниатюры справа */
        .pl-right {
            float: right;
            margin: 5px 0 5px 15px;
        }
        /* миниатюры слева */
        .pl-left {
            float: left;
            margin: 5px 18px 5px 0;
        }
        /* анимация при появлении блоков с содержанием */
        @-moz-keyframes fade {
            from { opacity: 0; }
            to { opacity: 1 }
        }
        @-webkit-keyframes fade {
            from { opacity: 0; }
            to { opacity: 1 }
        }
        @keyframes fade {
            from { opacity: 0; }
            to { opacity: 1 }
        }
    </style>
</head>
<body>


<div id="app">

    @include ('layouts.navigationCombo')
    <main class="py-4">

        @include ('layouts.messages')
        @yield('content')

        <div id="overlay">
            <div class="popup">
                <h5>Політика конфіденційності</h5>
                <p>Ми використовуємо файли cookies для надання найкрашого сервісу та безпеки Ваших даних.</p>
                <p>Оставаючись на сайті,  Ви погоджуєтесь
                    <a href="{{ route('taxi-gdbr') }}">на умови використання  особистой інформації.</a>
                </p>
                <button class="close" title="Закрыть" onclick="document.getElementById('overlay').style.display='none';"></button>
            </div>
        </div>
    </main>
</div>

<script>

       if ($.cookie('name') !== 'value' ) {

        var delay_popup = 10;
        setTimeout("document.getElementById('overlay').style.display='block'", delay_popup);
    }
    //создаем куки
    $.cookie('name', 'value', {
        expires: 1,
        path: '/'
    });

</script>

@include ('layouts.footerCombo')
</body>
</html>
