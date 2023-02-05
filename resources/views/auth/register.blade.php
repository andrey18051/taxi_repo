@extends('layouts.taxiNewCombo')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Реєстрація') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf



                        <div class="row mb-3">
                            <label for="user_phone" class="col-md-4 col-form-label text-md-end"
                            id="label_user_phone">{{ __("Телефон") }}</label>

                            <div class="col-md-6">
                                <input id="user_phone" type="text"
                                       class="form-control @error('user_phone') is-invalid @enderror" name="user_phone"
                                       pattern="[\+]\d{12}"
                                       placeholder="+380936665544"
                                       title="Формат вводу: +380936665544"
                                       minlength="13"
                                       maxlength="13"
                                       @isset($phone)
                                       value="{{ $phone }}"
                                       @else
                                       value="{{ old('user_phone') }}" required autocomplete="user_phone"
                                       @endisset
                                       onblur="
                                        var route =  '/sendConfirmCode/' + this.value;
                                            $.ajax({
                                            url: route,         /* Куда пойдет запрос */
                                            method: 'get',             /* Метод передачи (post или get) */
                                            dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                                            success: function (data) {   /* функция которая будет выполнена после успешного запроса.  */
                                                if (data != 200) {
                                                  document.location.href = '/registerSmsFail';
                                                }
                                            }
                                        });"
                                        autofocus>

                                @error('user_phone')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div id="confirm_code_div">
                            <div class="row mb-3" >
                                <label for="confirm_code" class="col-md-4 col-form-label text-md-end">{{ __('Код зі смс') }}</label>

                                <div class="col-md-6">
                                    <input id="confirm_code" type="text" class="form-control"
                                           name="confirm_code" placeholder="Код зі смс"
                                           pattern="[0-9]*"
                                           title="Формат вводу: 1234"
                                           minlength="4"
                                           maxlength="4"
                                           autofocus
                                           value="{{ old('confirm_code') }}"
                                           required
                                           onchange="
                                            var route = '/approvedPhones/' +
                                                document.getElementById('user_phone').value + '/' +
                                                this.value;
                                            $.ajax({
                                                  url: route,         /* Куда пойдет запрос */
                                                  method: 'get',             /* Метод передачи (post или get) */
                                                  dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */

                                                  success: function (data) {   /* функция которая будет выполнена после успешного запроса.  */
                                                      if (data != 200)  {
                                                         if (data == 400) {
                                                                alert('Помілка введення кода підтвердження');
                                                         } else {
                                                                alert('Сталася помілка. Зверниться до оператора.');
                                                                        document.location.href = '/feedback';
                                                         }
                                                      } else {
                                                          document.getElementById('confirm_area').style.display='block';
                                                      }
                                                  }
                                            });">

                                </div>
                            </div>
                        </div>


                        <div id="confirm_area" style="display: none">

                            <div class="row mb-3">
                                <label for="name" class="col-md-4 col-form-label text-md-end">{{ __("Ім'я") }}</label>

                                <div class="col-md-6">
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name">

                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>



                            <div class="row mb-3">
                                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Адреса електронної пошти') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Пароль') }}</label>

                                <div class="col-md-6">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Підтвердьте пароль') }}</label>

                                <div class="col-md-6">
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>



                            <input type="hidden" id="google_id" name="google_id" value="">
                            <input type="hidden" id="facebook_id" name="facebook_id" value="">
                            <input type="hidden" id="linkedin_id" name="linkedin_id" value="">
                            <input type="hidden" id="github_id" name="github_id" value="">
                            <input type="hidden" id="twitter_id" name="twitter_id" value="">
                            <input type="hidden" id="telegram_id" name="telegram_id" value="">
                            <input type="hidden" id="viber_id" name="viber_id" value="">


                            <div class="row mb-0" id="submit_button">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Зареєструватися') }}
                                    </button>
                                </div>
                            </div>


                        </div>
                    </form>
                    <div class="col-md-8 offset-md-4 flex items-center justify-end mt-4">

                        <a href="{{ url('auth/google') }}" title="Авторизуватися через Google">
                            <img src="{{ asset('img/icons8-google-48.png') }}">
                        </a>

                        <a href="{{ url('auth/facebook') }}" title="Авторизуватися через Facebook">
                            <img src="{{ asset('img/icons8-facebook-circled-48.png') }}">
                        </a>

                        <a href="{{ url('auth/linkedin') }}" title="Авторизуватися через Linkedin">
                            <img src="{{ asset('img/icons8-linkedin-48.png') }}">
                        </a>

                        <a href="{{ url('auth/github') }}" title="Авторизуватися через Github">
                            <img src="{{ asset('img/icons8-github-48.png') }}">
                        </a>

                        <a href="{{ url('auth/twitter') }}" title="Авторизуватися через Twitter">
                            <img src="{{ asset('img/icons8-twitter-48.png') }}">
                        </a>
                        <a type="button"   href="{{ url('auth/telegram') }}" title="Авторизуватися через Telegram">
                            <img src="{{ asset('img/icons8-telegram-app-48.png') }}">
                        </a>
                        <a type="button"   href="viber://pa?chatURI=taxieasyua" title="Авторизуватися через Viber">
                            <img src="{{ asset('img/icons8-viber-48.png') }}">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
