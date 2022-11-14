@extends('layouts.taxiNewCombo')

@section('content')

<!-- Section: Design Block -->
<section class="">
    <!-- Jumbotron -->
    <div class="container text-center" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <div class="container">
            <div class="row align-items-center">
                <p>Реєстрація телефону для замовлення таксі</p>
                <div class="col-lg-6 ">
                    <p class="lead gradient">
                        Найкраща пропозиція<br />
                        <span class="text-primary">для вашої поїздки</span>
                    </p>
                </div>

                <div class="col-lg-6 ">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('profileApi') }}">
                            @csrf

                            <!-- 2 column grid layout with text inputs for the first and last names -->
                                <div class="container">
                                    <div class="row">

                                    <!-- Phone input -->
                                        <div class="form-outline mb-2">
                                             <input type="text" class="form-control" name="username" value="{{$phone}}" required>
                                        </div>

                                        <!-- Password input -->
                                        <div class="form-outline mb-2">
                                            <input type="password" class="form-control" name="password" placeholder="Пароль? Мінімально 7 букв або цифр" id="password" required>
                                            <a href="{{route('restore-sms-phone', $phone)}}" target="_blank">Забули пароль?</a>
                                        </div>

                                        <!-- Submit button -->
                                        <div class="container">
                                           <button type="submit" class="btn btn-primary btn-block mb-2 col-lg-5">
                                                Увійти
                                            </button>
                                            <a  class="btn btn-success btn-block mb-2 col-lg-5" target="_blank" href="{{route('registration-sms-phone', $phone)}}">
                                                Зареєструвати
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-phone-vibrate" viewBox="0 0 16 16">
                                                    <path d="M10 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h4zM6 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H6z"/>
                                                    <path d="M8 12a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM1.599 4.058a.5.5 0 0 1 .208.676A6.967 6.967 0 0 0 1 8c0 1.18.292 2.292.807 3.266a.5.5 0 0 1-.884.468A7.968 7.968 0 0 1 0 8c0-1.347.334-2.619.923-3.734a.5.5 0 0 1 .676-.208zm12.802 0a.5.5 0 0 1 .676.208A7.967 7.967 0 0 1 16 8a7.967 7.967 0 0 1-.923 3.734.5.5 0 0 1-.884-.468A6.967 6.967 0 0 0 15 8c0-1.18-.292-2.292-.807-3.266a.5.5 0 0 1 .208-.676zM3.057 5.534a.5.5 0 0 1 .284.648A4.986 4.986 0 0 0 3 8c0 .642.12 1.255.34 1.818a.5.5 0 1 1-.93.364A5.986 5.986 0 0 1 2 8c0-.769.145-1.505.41-2.182a.5.5 0 0 1 .647-.284zm9.886 0a.5.5 0 0 1 .648.284C13.855 6.495 14 7.231 14 8c0 .769-.145 1.505-.41 2.182a.5.5 0 0 1-.93-.364C12.88 9.255 13 8.642 13 8c0-.642-.12-1.255-.34-1.818a.5.5 0 0 1 .283-.648z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
    </div>
</section>
<!-- Section: Design Block -->


@endsection
