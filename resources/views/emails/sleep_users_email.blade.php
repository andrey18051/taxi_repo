<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .changes {
            margin-bottom: 20px;
        }
        .change-item {
            margin-bottom: 10px;
        }
        .signature {
            text-align: right;
        }
    </style>
</head>
<body>


<div class="container">
    <div class="header">
        <h4>Глибоко поважне панство доброго часу доби.</h4>
        <h5>Інформуємо Вас про новини, нові функції та розвиток додатків.</h5>
    </div>
    <div class="changes">
         <p>
             Зробить прямо зараз заказ онлайн дешево водія з власним авто зі мобільного додатка "{{ $mes }}"
         </p>
        <p>
            Оновити програму можна <a href={{  $app_url }}>за посиланням</a>
        </p>


    </div>
    <div class="signature">
        <p style="text-align: justify;">
            Ми можемо вам допомогти у виготовленні шаблоних або індивідуальних додатків Андроід, телеграм ботів, сайтів. Отримати консультації по будь якому питанню стосуючих таксі в Україні. Очікуємо на ваші листи.
        </p>

        <p>З найкращими побажаннями</p>
        <a href="https://play.google.com/store/apps/dev?id=8830024160014473355">Керівник команди розробників</a>

    </div>
    <div class="header">
        <p>Вашу адресу отримано з відкритих джерел в інтернеті. Ви можете відписатися від розсилки нажавши нижче кнопку:</p>
        <a href="{{ $url }}" style="display: inline-block; background-color: #007bff; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 5px;">{{ $text_button }}</a>
    </div>
    <p style="text-align: left;">{{$uniqueNumber}}</p>
</div>

</body>
</html>

