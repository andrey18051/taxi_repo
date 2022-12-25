<div style="margin-bottom: 0px;">
    <a  href="{{route('home-news')}}" target="_blank" title="Повна версія сайту"
        style="z-index: 101; position: fixed; margin-top: 0px; right: 0px; ">
        <img src="{{ asset('img/icons8-computer-64.png') }}" style="width:30px;">
    </a>
    <a    href="{{route('homeCombo')}}" target="_blank" title="Розрахунок вартості"
          style="z-index: 101; position: fixed; margin-top: 31px; right: 0px">
        <img src="{{ asset('img/icons8-money-bag-94.png') }}" style="width:30px;">
    </a>
    <a    href="{{route('homeMapCombo')}}"
          target="_blank" title="Пошук по Google maps"
          style="z-index: 101; position: fixed; margin-top: 62px; right: 0px">
        <img src="{{ asset('img/icons8-google-maps-64.png') }}" style="width:30px;">
    </a>
    <a    target="_blank" title="Екстрена допомога"
          href="{{ route('callBackForm') }}"
          style="z-index: 101; position: fixed; margin-top: 93px; right: 0px">
        <img src="{{ asset('img/icons8-phone-64.png') }}" style="width:30px;">
    </a>
    <a    target="_blank" title="Надислати листа"
          href="{{ route('feedback') }}"
          style="z-index: 101; position: fixed; margin-top: 124px; right: 0px">
        <img src="{{ asset('img/icons8-mail-48.png') }}" style="width:30px;">
    </a>
    @guest
        <a     href="{{ route('login-taxi-info',
                ['info' => 'Зареєструйтесь через іконку Телеграм для використання функцій Чат-Бота.'])  }}"
              target="_blank" title="Віртуальний помічник"
              style="z-index: 101; position: fixed; margin-top: 155px; right: 0px">
            <img src="{{asset('img/icons8-chat-48.png')}}" style="width:30px;">
        </a>
    @else
        @if (Auth::user()->telegram_id)
            <a    href="{{ route('telegramBot') }}"
                  target="_blank" title="Віртуальний помічник"
                  style="z-index: 101; position: fixed; margin-top: 155px; right: 0px">
                <img src="{{asset('img/icons8-chat-48.png')}}" style="width:30px;">
            </a>
        @endif
    @endauth
</div>


<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <div>
            <a class="navbar-brand gradient" href="{{ url('/') }}" target="_blank">
                {{ config('app.name', 'Laravel') }}
                <img src="{{ asset('img/logo.jpg') }}" style="width: 100px; height: auto">
            </a>
        </div>

        <div class="container" style="text-align: center">
            <div class="container" style="text-align: center">
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
            </div>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"  data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto">
                <!-- Authentication Links -->
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login-taxi') }}" title="{{ __('Вхід') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                                    <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                </svg>
                            </a>
                        </li>
                    @endif

                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}" title="{{ __('Реєстрація') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-r-circle" viewBox="0 0 16 16">
                                    <path d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8Zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0ZM5.5 4.002h3.11c1.71 0 2.741.973 2.741 2.46 0 1.138-.667 1.94-1.495 2.24L11.5 12H9.98L8.52 8.924H6.836V12H5.5V4.002Zm1.335 1.09v2.777h1.549c.995 0 1.573-.463 1.573-1.36 0-.913-.596-1.417-1.537-1.417H6.835Z"/>
                                </svg>
                            </a>
                        </li>
                    @endif
                @else

                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            @if(Auth::user()->email == 'andrey18051@gmail.com')
                                <a class="dropdown-item" href="{{route('home-admin')}}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                    </svg>
                                    {{ "Панель Суперадміна" }}
                                </a>
                            @endif
                            <a class="dropdown-item" href="{{route('profile')}}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                </svg>
                                {{ "Особистий кабинет" }}
                            </a>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-door-open" viewBox="0 0 16 16">
                                    <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1z"/>
                                    <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117zM11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5zM4 1.934V15h6V1.077l-6 .857z"/>
                                </svg>
                                {{ "Вихід" }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>


