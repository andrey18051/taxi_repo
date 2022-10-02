@extends('layouts.newsList')

@section('content')
    <body>
    <div class="container">
        <a class="but_vart" href="http://taxi2012" target="_blank" title="Версія сайту для мобільних">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-phone" viewBox="0 0 16 16">
                <path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h6zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H5z"/>
                <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
        </a>
    </div>
    <div class="container">
        <a class="but_vart_vart" href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank" title="Розрахунок вартості">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
            </svg>
        </a>
    </div>
    <?php
    $connection = new PDO('mysql:host=localhost;dbname=taxi2012;charset=utf8', 'root', '');
    //   $connection = new PDO('mysql:host=127.0.0.1:3310;dbname=admin_taxi;charset=utf8', 'admin_taxi', 'MR5nTc9R8h');
    /**
     * Цитаты
     */
    $query = $connection->query("SELECT * FROM `quites`");
    $quites = $query->fetchAll();

    $i = -1;
    foreach ($quites as $item) {
        $i++;
        $quitesArr[$i] =  $item['name'];

    }

    $rand =  rand(0, $i);

    /**
     * Бегущая строка
     */

    $query = $connection->query("SELECT * FROM `orderwebs`");
    $quites_order = $query->fetchAll();

    $i_order = -1;
    foreach ($quites_order as $item) {
        $i_order++;
        $quitesArr_order[$i_order] =  $item['routefrom'] . " - " . $item['routeto'] . "-" . $item['web_cost'] . "грн " ;
    }
    ?>

    <br>
    <div class="container" style="text-align: center">
        <h2 class="gradient"><b>Київ та область</b></h2>
    </div>
    <p class="marquee gradient"><span>
            <?php
                $i_order_view = 0;
                do {
                $rand_order =  rand(0, $i_order);
                echo $quitesArr_order[$rand_order];
                $i_order_view++;
                }
            while ($i_order_view <= 3);
            ?></span>
    </p>
    <h3 class="gradient" style="text-align:center">Швидкі та надійні поїздки на комфортних авто.</h3>

    <news-component></news-component>


    <div style="text-align:center">
        <!--   <a href="https://easy-order-taxi.site/mainpage" class="gradient-button" target="_blank">Замовити таксі</a>-->
     <a href="http://taxi2012" class="gradient-button" target="_blank">Замовити таксі</a>
    </div>



    <blockquote class="blockquote-3">
        <p> <?php echo $quitesArr[$rand] ?></p>
        <cite>Цитата дня</cite>
    </blockquote>


    </body>


@endsection
