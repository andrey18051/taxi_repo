@extends('layouts.taxiNewCombo')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset

    {{--@isset($params)

            {{dd($params)}}

    @endisset--}}

    <!-- Section: Design Block -->
    <section class="">
        <!-- Jumbotron -->
        <div class="text-center text-lg-start container" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="container">
                <div class="row align-items-center">

                    <div class="col-lg-6 ">
                        <p class="lead text-center gradient">
                            Анкета водія
                        </p>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('callWork') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="container">
                                    <div class="row">

                                        <!-- Phone input -->
                                        <div class="form-outline mb-2 col-12"  >
                                            <div class="row">

                                                <div class="col-12">
                                                    <input type="email" id="email" name="email"
                                                           placeholder="Email?" autocomplete="off"
                                                           @guest
                                                           @isset($params['email'])
                                                           value="{{$params['email']}}"
                                                           readonly
                                                           @endisset
                                                           @else
                                                           value="{{ Auth::user()->email}}"
                                                           readonly
                                                           @endguest
                                                           value="{{ old('email') }}"
                                                           class="form-control" required/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-outline mb-2 col-12" >
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="text" class="form-control" id="user_full_name"
                                                           name="user_full_name"
                                                           @guest
                                                           @isset($params['user_full_name'])
                                                           value="{{$params['user_full_name']}}"
                                                           readonly
                                                           @endisset
                                                           @else
                                                           value="{{ Auth::user()->name}}"
                                                           readonly
                                                           @endguest
                                                           value="{{ old('user_full_name') }}"
                                                           autocomplete="off" placeholder="Ваше ім'я?" required>
                                                </div>
                                                <div class="col-6">
                                                    <input type="number" id="time_work" name="time_work"
                                                           min="1" max="100"
                                                           @isset($params['time_work'])
                                                           value="{{$params['time_work']}}"
                                                           readonly
                                                           @endisset
                                                           value="{{ old('time_work') }}"
                                                           placeholder="Стаж (років)?" autocomplete="off"
                                                           class="form-control @error('time_work') is-invalid @enderror" style="text-align: center"
                                                           required/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <input type="text" class="form-control  @error('user_phone') is-invalid @enderror"
                                                   name="user_phone"
                                                   @guest
                                                   @isset($params['user_phone'])
                                                   value="{{$params['user_phone']}}"
                                                   readonly
                                                   @endisset
                                                   @else
                                                   value="{{ Auth::user()->user_phone }}"
                                                   readonly
                                                   @endguest
                                                   value="{{ old('user_phone') }}"
                                                   placeholder="Телефон? Приклад: +380936665544" required>
                                        </div>
                                        <br><br>
                                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                                    </div>

                                        <!-- Submit button -->
                                        <div class="row">
                                            @guest
                                                <button type="reset" class="btn btn-danger col-12" style="margin-top: 5px">
                                                    Очистити
                                                </button>
                                            @endguest

                                            <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                Надіслати
                                            </button>
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
        <!-- Jumbotron -->
    </section>

@endsection
