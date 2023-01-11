@extends('layouts.taxiNewCombo')

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Реєстрація') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __("Ім'я") }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ $newUser["name"] }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="user_phone" class="col-md-4 col-form-label text-md-end">{{ __("Телефон") }}</label>

                            <div class="col-md-6">
                                <input id="user_phone" type="text"
                                       class="form-control @error('user_phone') is-invalid @enderror"
                                       name="user_phone" value="{{ old('user_phone') }}" required autocomplete="user_phone"
                                       pattern="[\+]\d{12}"
                                       placeholder="+380936665544"
                                       title="Формат вводу: +380936665544"
                                       minlength="13"
                                       maxlength="13"
                                       @if($newUser['telegram_id'] == null)
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
                                       @endif
                                       autofocus>

                                @error('user_phone')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        @if($newUser['telegram_id'] == null)
                            <div class="row mb-3" >
                                <label for="confirm_code" class="col-md-4 col-form-label text-md-end">{{ __('Код зі смс') }}</label>

                                <div class="col-md-6">
                                    <input id="confirm_code" type="text" class="form-control"
                                           name="confirm_code" placeholder="Код зі смс"
                                           pattern="[0-9]*"
                                           placeholder="1234"
                                           title="Формат вводу: 1234"
                                           minlength="4"
                                           maxlength="4"
                                           autofocus
                                           value="{{ old('confirm_code') }}"
                                           @if($newUser['telegram_id'] == null)
                                           required
                                           @endif
                                           onblur="
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
                                                      }
                                                  }
                                            });">
                                </div>


                        </div>
                        @endif

                        @if($newUser['google_id'])
                            <input type="hidden" id="google_id" name="google_id" value="{{ $newUser['google_id'] }}">
                        @else
                            <input type="hidden" id="google_id" name="google_id" value="">
                        @endif
                        @if($newUser['facebook_id'])
                            <input type="hidden" id="facebook_id" name="facebook_id" value="{{ $newUser['facebook_id'] }}">
                        @else
                            <input type="hidden" id="facebook_id" name="facebook_id" value="">
                        @endif
                        @if($newUser['linkedin_id'])
                            <input type="hidden" id="linkedin_id" name="linkedin_id" value="{{ $newUser['linkedin_id'] }}">
                        @else
                            <input type="hidden" id="linkedin_id" name="linkedin_id" value="">
                        @endif
                        @if($newUser['github_id'])
                            <input type="hidden" id="github_id" name="github_id" value="{{ $newUser['github_id'] }}">
                        @else
                            <input type="hidden" id="github_id" name="github_id" value="">
                        @endif
                        @if($newUser['twitter_id'])
                            <input type="hidden" id="twitter_id" name="twitter_id" value="{{ $newUser['twitter_id'] }}">
                        @else
                            <input type="hidden" id="twitter_id" name="twitter_id" value="">
                        @endif
                        @if($newUser['telegram_id'])
                            <input type="hidden" id="telegram_id" name="telegram_id" value="{{ $newUser['telegram_id'] }}">
                        @else
                            <input type="hidden" id="telegram_id" name="telegram_id" value="">
                        @endif

                        <div id="confirm_area" style="display: block">
                            <div class="row mb-3">
                                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Адреса електронної пошти') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                           name="email" value="{{ $newUser['email'] }}" required autocomplete="email">

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
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                                           required autocomplete="new-password">

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
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                                           required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Зареєструватися') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
