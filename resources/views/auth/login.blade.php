@extends('layouts.taxiNewCombo')

@section('content')


<!-- Section: Design Block -->
<section class="">
    <!-- Jumbotron -->

    <div class="row justify-content-center">
        <div class="col-md-8">

                    <div class="card">
                        <div class="card-header">{{ __('Вхід') }}</div>

                        <div class="card-body">
                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="row mb-3">
                                    <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Адреса електронної пошти') }}</label>

                                    <div class="col-md-6">
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

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
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                        @error('password')
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6 offset-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                            <label class="form-check-label" for="remember">
                                                {{ __("Пам'ятай мене") }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-0">
                                    <div class="col-md-8 offset-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            {{ __('Логін') }}
                                        </button>

                                        @if (Route::has('password.request'))
                                            <a class="btn btn-link" href="{{ route('password.request') }}">
                                                {{ __('Забули свій пароль?') }}
                                            </a>
                                        @endif
                                    </div>
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

                                        <a href="{{ url('auth/github') }}" title="Авторизуватися через Linkedin">
                                            <img src="{{ asset('img/icons8-github-48.png') }}">
                                        </a>

                                        <a href="{{ url('auth/twitter') }}" title="Авторизуватися через Twitter">
                                            <img src="{{ asset('img/icons8-twitter-48.png') }}">
                                        </a>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <br>

</section>

@endsection
