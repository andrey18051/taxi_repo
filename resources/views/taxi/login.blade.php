@extends('layouts.taxiNewCombo')

@section('content')

<!-- Section: Design Block -->
<section class="">
    <!-- Jumbotron -->

    <div class="container text-center" style="background-color: hsl(0, 0%, 96%)">
        <br>
        <div class="container">
            <div class="row align-items-center">

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
                                             <input type="text" class="form-control" name="username"
                                                    pattern="[\+]\d{12}"
                                                    placeholder="+380936665544"
                                                    title="Формат вводу: +380936665544"
                                                    minlength="13"
                                                    maxlength="13"
                                                    required>
                                        </div>

                                        <!-- Password input -->
                                        <div class="form-outline mb-2">
                                            <input type="password" class="form-control" name="password" placeholder="Пароль? Мінімально 8 букв або цифр" id="password" required>
                                            <a href="{{route('restore-sms')}}" target="_blank">Забули пароль?</a>
                                        </div>

                                        <!-- Submit button -->
                                        <div class="container">
                                           <button type="submit" class="btn btn-primary btn-block mb-2 col-lg-5">
                                                Увійти
                                            </button>
                                            <a  class="btn btn-success btn-block mb-2 col-lg-5" target="_blank" href="{{route('registration-sms')}}">
                                                Зареєструватись
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                           {{-- <div class="flex items-center justify-end mt-4">

                                <a href="{{ url('auth/google') }}">

                                    <img src="https://developers.google.com/identity/images/btn_google_signin_dark_normal_web.png" style="margin-left: 3em;">

                                </a>

                            </div>--}}
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
