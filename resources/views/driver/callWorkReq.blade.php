@extends('layouts.logout21')

@section('content')

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
                                                    <input type="text" class="form-control" name="user_phone" value="{{$paramReq['user_phone']}}" required>
                                                </div>
                                                <br><br>
                                                <div class="col-12">
                                                    <input type="email" id="email" name="email" class="form-control" value="{{$paramReq['email']}}" required/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-outline mb-2 col-12" >
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="text" class="form-control" id="user_full_name" name="user_full_name" autocomplete="off" value="{{$paramReq['user_full_name']}}" required>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" id="time_work" name="time_work" value="{{$paramReq['time_work']}}" autocomplete="off" class="form-control" style="text-align: center"/>
                                                </div>
                                            </div>
                                        </div>

                                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                                    </div>

                                        <!-- Submit button -->
                                        <div class="row">
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
    <!-- Section: Design Block -->


@endsection
