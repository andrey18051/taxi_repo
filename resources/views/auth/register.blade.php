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
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

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
                                       class="form-control @error('user_phone') is-invalid @enderror" name="user_phone"
                                       value="{{ old('user_phone') }}" required autocomplete="user_phone" placeholder="+380936665544">

                                @error('user_phone')
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

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Зареєструватися') }}
                                </button>
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

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
