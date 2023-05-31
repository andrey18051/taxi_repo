<footer class="text-muted text-center text-small gradient">
    <div class="center gradient">
        <span style="color:black">Сьогодні:</span>
        <span style="color:black;; font-size:14px;">
              <script>
                   document.write(date+" ");
                   document.write(thismonth+ " "+thisyear+" "+"року"+" — "+ DayofWeek);
              </script>
              (<span id="clockdat" style="color:blue;"></span>)
                    <span>
                        🌡️
                        {{\App\Http\Controllers\WeatherController::temp()}}
                        ℃
                    </span>
        </span>
    </div>
    <p class="mb-1">&copy; 2022 Легке замовлення таксі </p>
    <ul class="list-inline">
        <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}" target="_blank">Конфіденційність</a></li>
        <li class="list-inline-item"><a href="{{route('homeCombo')}}" target="_blank">Розрахунок вартості</a></li>
        <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
        <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
    </ul>
    <a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/"
       target="_blank" title="Сторінка сайту у Facebook" style="text-decoration: none;">
        <img src="{{ asset('img/icons8-facebook-circled-24.png') }}">
    </a>
    <a href="https://www.linkedin.com/company/taxi-easy-ua/"
       target="_blank" title="Сторінка сайту у Linkedin" style="text-decoration: none;">
        <img src="{{ asset('img/icons8-linkedin-24.png') }}">
    </a>
    <a href="https://t.me/taxieasyua"
       target="_blank" title="Сторінка сайту у Telegram" style="text-decoration: none;">
        <img src="{{ asset('img/icons8-telegram-app-24.png') }}">
    </a>
</footer>
